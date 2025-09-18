@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    use Illuminate\Support\Str;

    $stats          = $stats ?? [];
    $chartData      = $chartData ?? ['categories'=>[], 'leads'=>[], 'messages'=>[]];
    $statusDonut    = $statusDonut ?? ['labels'=>[], 'series'=>[]];
    $subSummary     = $subSummary ?? ['total'=>0,'active'=>0,'paused'=>0,'cancelled'=>0,'mrr'=>0];
    $taskSummary    = $taskSummary ?? ['open'=>0,'in_progress'=>0,'done'=>0,'mineToday'=>collect()];
    $trialsSoon     = $trialsSoon ?? collect();
    $recentActivities = $recentActivities ?? collect();

    $connected = $stats['connectedSenders'] ?? null;
    $active    = $stats['activeSenders'] ?? 0;
    $total     = $stats['totalSenders'] ?? 0;

    $senderLabelCount = is_null($connected) ? "{$active} / {$total}" : "{$connected} / {$total}";
    $senderLabelDesc  = is_null($connected)
        ? (($active === $total && $total > 0) ? 'Semua sender diaktifkan' : 'Sebagian sender nonaktif')
        : (($connected === $total && $total > 0) ? 'Semua sesi tersambung' : (($total - $connected) . ' sesi belum tersambung'));

    $leadsChange = ($stats['leadsThisWeek'] ?? 0) - ($stats['leadsPreviousWeek'] ?? 0);
    $sentChange  = ($stats['messagesSentLast7Days'] ?? 0) - ($stats['messagesSentPrevious7Days'] ?? 0);
    $sentPct     = ($stats['messagesSentPrevious7Days'] ?? 0) > 0 ? ($sentChange / $stats['messagesSentPrevious7Days']) * 100 : 0;
    $replyChange = ($stats['replyRate'] ?? 0) - ($stats['replyRatePrevious'] ?? 0);
@endphp

<!-- HEADER + STAT CARDS (dipotong, sama persis dengan versi Anda sebelumnya) -->
<!-- ... kode stat cards dan daftar lain tetap sama ... -->

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script type="application/json" id="dashboard-chart-data">@json($chartData)</script>
<script type="application/json" id="dashboard-status-donut">@json($statusDonut)</script>

<script>
/**
 * ==== THEME COLOR UTILS ====
 * Ambil warna langsung dari kelas DaisyUI dengan fallback default.
 */
function getCssColor(cls, prop='color', fallback='#999') {
    const el = document.createElement('span');
    el.className = cls;
    el.style.display = 'none';
    document.body.appendChild(el);
    const style = getComputedStyle(el)[prop];
    el.remove();
    return style || fallback;
}
function snapshotTheme() {
    return {
        primary:   getCssColor('text-primary','color','#3B82F6'),
        secondary: getCssColor('text-secondary','color','#06B6D4'),
        accent:    getCssColor('text-accent','color','#F472B6'),
        info:      getCssColor('text-info','color','#0EA5E9'),
        success:   getCssColor('text-success','color','#22C55E'),
        warning:   getCssColor('text-warning','color','#F59E0B'),
        error:     getCssColor('text-error','color','#EF4444'),
        base:      getCssColor('text-base-content','color','#374151'),
        bg:        getCssColor('bg-base-100','backgroundColor','#fff')
    }
}
function isDarkModeFromBg(bg) {
    const m = bg.match(/\d+/g); if (!m) return false;
    const [r,g,b] = m.map(Number);
    const luma = 0.2126*r/255 + 0.7152*g/255 + 0.0722*b/255;
    return luma < 0.5;
}

document.addEventListener('alpine:init', () => {
    // AREA CHART
    Alpine.data('apexChart', () => ({
        chart:null,isLoading:true,theme:null,
        init(){
            const data = JSON.parse(document.getElementById('dashboard-chart-data').textContent||'{}');
            const render=()=>{this.isLoading=false;this.theme=snapshotTheme();this.$nextTick(()=>this.draw(data));};
            if(window.ApexCharts)render();else document.addEventListener('DOMContentLoaded',render);
            new MutationObserver(()=>this.draw(data))
              .observe(document.documentElement,{attributes:true,attributeFilter:['data-theme','class']});
        },
        draw(data){
            const el=document.getElementById('main-chart');
            if(!el||!window.ApexCharts)return;
            if(this.chart){this.chart.destroy();this.chart=null;}
            if(!data.categories?.length){el.innerHTML=`<div class="flex items-center justify-center h-full text-base-content/60">Data tidak tersedia.</div>`;return;}
            const t=snapshotTheme(),dark=isDarkModeFromBg(t.bg);
            const opt={
                series:[{name:'Leads Baru',data:data.leads||[]},{name:'Pesan Terkirim',data:data.messages||[]}],
                chart:{type:'area',height:'100%',background:'transparent',toolbar:{show:false}},
                colors:[t.primary,t.accent],
                dataLabels:{enabled:false},
                stroke:{curve:'smooth',width:3},
                fill:{type:'gradient',gradient:{opacityFrom:0.6,opacityTo:0.1,stops:[0,95,100]}},
                xaxis:{type:'datetime',categories:data.categories,labels:{style:{colors:t.base}}},
                yaxis:{labels:{style:{colors:t.base}}},
                tooltip:{theme:dark?'dark':'light',shared:true,intersect:false,x:{format:'dd MMM yyyy'}},
                grid:{borderColor:'rgba(0,0,0,0.1)',strokeDashArray:4},
                legend:{labels:{colors:t.base},position:'top',horizontalAlign:'right'}
            };
            this.chart=new ApexCharts(el,opt);this.chart.render();
        }
    }));

    // DONUT CHART
    Alpine.data('donutChart', () => ({
        chart:null,isLoading:true,theme:null,
        init(){
            const data=JSON.parse(document.getElementById('dashboard-status-donut').textContent||'{}');
            const render=()=>{this.isLoading=false;this.theme=snapshotTheme();this.$nextTick(()=>this.draw(data));};
            if(window.ApexCharts)render();else document.addEventListener('DOMContentLoaded',render);
            new MutationObserver(()=>this.draw(data))
              .observe(document.documentElement,{attributes:true,attributeFilter:['data-theme','class']});
        },
        draw(data){
            const el=document.getElementById('status-donut');
            if(!el||!window.ApexCharts)return;
            if(this.chart){this.chart.destroy();this.chart=null;}
            if(!data.series?.length||data.series.every(v=>!v)){el.innerHTML=`<div class="flex items-center justify-center h-full text-base-content/60">Tidak ada data status.</div>`;return;}
            const t=snapshotTheme(),dark=isDarkModeFromBg(t.bg);
            const colors=[t.primary,t.secondary,t.accent,t.info,t.success,t.warning,t.error];
            const opt={
                series:data.series,labels:data.labels,
                chart:{type:'donut',height:'100%',background:'transparent'},
                colors:colors,
                legend:{position:'bottom',labels:{colors:t.base}},
                dataLabels:{enabled:true,formatter:v=>`${v.toFixed(1)}%`},
                plotOptions:{pie:{donut:{size:'70%',labels:{show:true,total:{show:true,label:'Total',formatter:w=>w.globals.seriesTotals.reduce((a,b)=>a+b,0)}}}}},
                tooltip:{theme:dark?'dark':'light',y:{formatter:v=>`${v} leads`}}
            };
            this.chart=new ApexCharts(el,opt);this.chart.render();
        }
    }));
});
</script>
@endpush
