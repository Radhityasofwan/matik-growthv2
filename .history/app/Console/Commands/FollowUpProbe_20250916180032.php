<?php

namespace App\Console\Commands;

use App\Http\Controllers\WahaController;
use App\Jobs\SendLeadFollowUpJob;
use App\Models\Lead;
use App\Models\LeadFollowUpRule;
use App\Models\User;
use App\Models\WahaSender;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class FollowUpProbe extends Command
{
    protected $signature = 'followup:probe
        {--lead-id= : Pakai lead existing (jika tidak, dibuat lead uji baru)}
        {--owner-wa=6281288844813 : Nomor WA owner untuk tes}
        {--simulate=success : success|fail (hasil mock kirim WA ke lead)}';

    protected $description = 'Probe end-to-end: kirim follow-up ke lead & notif owner, lalu cetak hasilnya ringkas.';

    public function handle(): int
    {
        $simulate = strtolower((string)$this->option('simulate')) === 'fail' ? 'fail' : 'success';
        $ownerWa  = preg_replace('/\D+/', '', (string)$this->option('owner-wa')) ?: '6281288844813';

        /* ===== 0) Mock WahaController => kontrol sukses/gagal ===== */
        app()->bind(WahaController::class, function () use ($simulate) {
            return new class($simulate) {
                public function __construct(private string $simulate) {}
                public function sendMessage()
                {
                    if ($this->simulate === 'fail') {
                        return response()->json([
                            'success'    => false,
                            'status'     => 'FAILED',
                            'message_id' => null,
                            'path'       => '/mock-fail',
                        ], 500);
                    }
                    return response()->json([
                        'success'    => true,
                        'status'     => 'OK',
                        'message_id' => 'FAKE-'.Str::random(6),
                        'path'       => '/mock-success',
                    ], 200);
                }
            };
        });

        /* ===== 1) OWNER ===== */
        /** @var User $owner */
        $owner = User::firstOrCreate(
            ['email' => 'owner.probe@matik.test'],
            ['name' => 'Owner Probe', 'password' => bcrypt(Str::random(12))]
        );
        $owner->wa_number = $ownerWa;
        $owner->save();

        /* ===== 1b) SENDER (lengkapi kolom session_* & display_name) ===== */
        $sessionVal  = 'sender-probe';
        $numberVal   = '6285166321841';
        $nameVal     = 'Sender Probe';

        $senderAttrs = [
            'name'       => $nameVal,
            'number'     => $numberVal,
            'is_active'  => true,
            'is_default' => true,
        ];

        // Isi semua varian kolom session yang tersedia
        foreach (['session_name', 'session', 'sessionId', 'session_key'] as $col) {
            if (Schema::hasColumn('waha_senders', $col)) {
                $senderAttrs[$col] = $sessionVal;
            }
        }

        // 🔧 Perbaikan inti: beberapa skema mewajibkan display_name (NOT NULL)
        if (Schema::hasColumn('waha_senders', 'display_name')) {
            $senderAttrs['display_name'] = $nameVal;
        }
        // Tambahan aman: jika ada kolom wid, isi dengan angka nomor
        if (Schema::hasColumn('waha_senders', 'wid')) {
            $senderAttrs['wid'] = preg_replace('/\D+/', '', $numberVal);
        }

        // Cari berdasarkan kolom session yang tersedia agar unik
        $senderQuery = WahaSender::query();
        if (Schema::hasColumn('waha_senders', 'session_name')) {
            $sender = $senderQuery->firstOrNew(['session_name' => $sessionVal]);
        } elseif (Schema::hasColumn('waha_senders', 'session')) {
            $sender = $senderQuery->firstOrNew(['session' => $sessionVal]);
        } elseif (Schema::hasColumn('waha_senders', 'session_key')) {
            $sender = $senderQuery->firstOrNew(['session_key' => $sessionVal]);
        } elseif (Schema::hasColumn('waha_senders', 'sessionId')) {
            $sender = $senderQuery->firstOrNew(['sessionId' => $sessionVal]);
        } else {
            $sender = $senderQuery->firstOrNew(['name' => $nameVal]);
        }

        $sender->forceFill($senderAttrs)->saveQuietly();

        /* ===== Template (opsional) ===== */
        $tplClass = class_exists(\App\Models\WATemplate::class) ? \App\Models\WATemplate::class
                   : (class_exists(\App\Models\Template::class) ? \App\Models\Template::class : null);
        $tpl = $tplClass
            ? $tplClass::firstOrCreate(['name' => 'TPL Probe'], ['body' => 'Halo {{name}}, ini uji dari {{owner_name}}.'])
            : null;

        /* ===== 2) Lead uji atau existing ===== */
        $leadId = $this->option('lead-id');
        if ($leadId) {
            /** @var Lead $lead */
            $lead = Lead::findOrFail((int)$leadId);
            if (!$lead->phone)    { $lead->phone    = '62895412144456'; }
            if (!$lead->owner_id) { $lead->owner_id = $owner->id; }
            $lead->save();
        } else {
            $lead = Lead::create([
                'name'       => 'Lead Probe '.Str::upper(Str::random(4)),
                'email'      => 'lead.probe+'.Str::lower(Str::random(5)).'@mail.test',
                'phone'      => '62895412144456',
                'store_name' => 'Toko Probe',
                'company'    => 'Perusahaan Probe',
                'owner_id'   => $owner->id,
                'created_at' => now()->subHours(2),
            ]);
        }

        /* ===== 3) Rule lead minimal agar eligible segera ===== */
        /** @var LeadFollowUpRule $rule */
        $rule = LeadFollowUpRule::create([
            'lead_id'     => $lead->id,
            'condition'   => 'no_chat',
            'days_after'  => 0,
            'template_id' => $tpl?->id,
            'sender_id'   => $sender->id,
            'is_active'   => true,
        ]);

        /* ===== 4) Bersihkan activity sebelumnya utk lead ini ===== */
        Activity::where('subject_type', Lead::class)->where('subject_id', $lead->id)->delete();

        /* ===== 5) Jalankan job sinkron utk rule ini ===== */
        (new SendLeadFollowUpJob($rule->id))->handle();

        /* ===== 6) Ambil log terbaru utk lead (lead & owner) ===== */
        $logs = Activity::where('subject_type', Lead::class)
            ->where('subject_id', $lead->id)
            ->latest('id')->take(10)->get();

        $leadLog  = $logs->firstWhere('log_name', 'follow_up');                 // ke LEAD
        $ownerLog = $logs->firstWhere('log_name', 'follow_up_notify_owner');    // ke OWNER (jalur saat ini)

        $this->line('--- PROBE RESULT ---');
        $this->info('Lead   : #'.$lead->id.' '.$lead->name.' ('.$lead->phone.')');
        $this->info('Owner  : #'.$owner->id.' '.$owner->name.' ('.$owner->wa_number.')');
        $this->line('Rule   : #'.$rule->id.' condition='.$rule->condition.' days_after='.$rule->days_after);
        $this->line('');

        $okLead  = (bool)$leadLog;
        $okOwner = (bool)$ownerLog;

        $this->line('Lead log    : '.($okLead ? 'OK' : 'MISSING'));
        if ($okLead) {
            $this->line('  number : '.(string)data_get($leadLog, 'properties.number'));
            $this->line('  http   : '.(string)data_get($leadLog, 'properties.http'));
            $this->line('  status : '.(string)data_get($leadLog, 'properties.status'));
            $snippet = (string) data_get($leadLog, 'properties.text', '');
            $this->line('  text   : '.Str::limit($snippet, 80));
        }

        $this->line('Owner log   : '.($okOwner ? 'OK' : 'MISSING'));
        if ($okOwner) {
            $this->line('  number : '.(string)data_get($ownerLog, 'properties.number'));
            $this->line('  http   : '.(string)data_get($ownerLog, 'properties.http'));
            $this->line('  status : '.(string)data_get($ownerLog, 'properties.status'));
        }

        $numbersDifferent = $okLead && $okOwner
            ? data_get($leadLog, 'properties.number') !== data_get($ownerLog, 'properties.number')
            : false;

        $this->line('');
        $this->info('numbers_different = '.($numbersDifferent ? 'true' : 'false'));

        return ($okLead && $okOwner && $numbersDifferent) ? self::SUCCESS : self::FAILURE;
    }
}
