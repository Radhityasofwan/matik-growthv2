<?php

namespace App\Http\Controllers;

use App\Http\Requests\WahaSenderRequest;
use App\Models\WahaSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WahaSenderController extends Controller
{
    /** Index: halaman tabel atau JSON ringan untuk dropdown */
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->boolean('json')) {
            $q = WahaSender::query();

            $q->orderByDesc('is_default');
            if (Schema::hasColumn('waha_senders', 'name')) $q->orderBy('name');
            else $q->orderBy('id');

            $cols = ['id', 'is_active', 'is_default'];
            if (Schema::hasColumn('waha_senders', 'session_name')) {
                $cols[] = DB::raw('session_name as session');
            } elseif (Schema::hasColumn('waha_senders', 'session')) {
                $cols[] = 'session';
            }
            if (Schema::hasColumn('waha_senders', 'name'))        $cols[] = 'name';
            if (Schema::hasColumn('waha_senders', 'number'))      $cols[] = 'number';
            if (Schema::hasColumn('waha_senders', 'display_name'))$cols[] = 'display_name';

            $senders = $q->get($cols);
            return response()->json(['data' => $senders]);
        }

        $q = WahaSender::query()->orderByDesc('is_default');
        if (Schema::hasColumn('waha_senders', 'name')) $q->orderBy('name');
        else $q->orderBy('id');

        $senders = $q->paginate(15);
        return view('waha_senders.index', compact('senders'));
    }

    /** CREATE — tanpa session/number dari form */
    public function store(WahaSenderRequest $request)
    {
        $data = $request->validated();

        // is_default & is_active
        $isDefault = (bool)($data['is_default'] ?? false);
        $base = [
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'display_name'=> $data['name'],  // tampil ramah
            'is_default'  => $isDefault,
            'is_active'   => false,          // BELUM aktif sebelum QR connect
        ];

        // Isi placeholder yang UNIK agar lolos NOT NULL & UNIQUE di kolom session/session_name
        $placeholder = 'pending-'.Str::uuid();

        if (Schema::hasColumn('waha_senders', 'session')) {
            $base['session'] = $placeholder;
        }
        if (Schema::hasColumn('waha_senders', 'session_name')) {
            $base['session_name'] = $placeholder;
        }
        if (Schema::hasColumn('waha_senders', 'number')) {
            $base['number'] = ''; // kosong dulu sampai QR connected
        }

        DB::transaction(function () use ($isDefault, $base) {
            if ($isDefault) {
                WahaSender::where('is_default', true)->update(['is_default' => false]);
            }
            WahaSender::create($base);
        });

        return redirect()
            ->route('waha-senders.index')
            ->with('success', 'Nomor pengirim berhasil ditambahkan. Silakan Scan / Connect untuk verifikasi.');
    }

    /** UPDATE — hanya nama/desc/is_default. TIDAK mengubah session/number. */
    public function update(WahaSenderRequest $request, WahaSender $wahaSender)
    {
        $data = $request->validated();

        $payload = [
            'name'         => $data['name'],
            'description'  => $data['description'] ?? null,
            'display_name' => $data['name'],
        ];

        $isDefault = (bool)($data['is_default'] ?? false);

        DB::transaction(function () use ($isDefault, $payload, $wahaSender) {
            if ($isDefault) {
                WahaSender::where('id','!=',$wahaSender->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
            $payload['is_default'] = $isDefault;
            $wahaSender->update($payload);
        });

        return redirect()
            ->route('waha-senders.index')
            ->with('success', 'Nomor pengirim berhasil diperbarui.');
    }

    public function destroy(Request $request, WahaSender $wahaSender)
    {
        $wahaSender->delete();
        return redirect()
            ->route('waha-senders.index')
            ->with('success', 'Nomor pengirim berhasil dihapus.');
    }

    public function toggleActive(Request $request, WahaSender $wahaSender)
    {
        $wahaSender->is_active = ! $wahaSender->is_active;
        $wahaSender->save();

        return response()->json([
            'message' => 'Status pengirim diperbarui.',
            'data' => $wahaSender->only(['id','is_active']),
        ]);
    }

    public function setDefault(Request $request, WahaSender $wahaSender)
    {
        DB::transaction(function () use ($wahaSender) {
            WahaSender::where('is_default', true)->update(['is_default' => false]);
            $wahaSender->is_default = true;
            // default sebaiknya aktif secara logis setelah connect, tapi flag-kan aktif
            $wahaSender->save();
        });

        return response()->json([
            'message' => 'Pengirim default diperbarui.',
            'data' => $wahaSender->only(['id','is_default','is_active']),
        ]);
    }

    /*** --- QR endpoints (dipanggil dari view) --- ***/

    // GET /waha-senders/{sender}/qr-status
    public function qrStatus(WahaSender $wahaSender)
    {
        // Pastikan service WahaController/Service kamu mengembalikan:
        // { success:true, data:{ state:'CONNECTED|PAIRING|QR', qr:'data:image/png;base64,...' } }
        try {
            $svc = app(\App\Services\WahaService::class);
            $resp = $svc->sessionStatus($wahaSender); // implementasikan di service
            return response()->json($resp ?? ['success'=>false,'message'=>'Tidak dapat mengambil status.']);
        } catch (\Throwable $e) {
            return response()->json(['success'=>false, 'message'=>$e->getMessage()], 500);
        }
    }

    // POST /waha-senders/{sender}/qr-start
    public function qrStart(WahaSender $wahaSender)
    {
        try {
            $svc = app(\App\Services\WahaService::class);
            $resp = $svc->sessionStart($wahaSender); // implementasikan di service
            return response()->json($resp ?? ['success'=>false,'message'=>'Gagal memulai sesi.'], $resp?200:502);
        } catch (\Throwable $e) {
            return response()->json(['success'=>false, 'message'=>$e->getMessage()], 500);
        }
    }

    // POST /waha-senders/{sender}/qr-logout
    public function qrLogout(WahaSender $wahaSender)
    {
        try {
            $svc = app(\App\Services\WahaService::class);
            $resp = $svc->sessionLogout($wahaSender); // implementasikan di service
            return response()->json($resp ?? ['success'=>false,'message'=>'Gagal logout.'], $resp?200:502);
        } catch (\Throwable $e) {
            return response()->json(['success'=>false, 'message'=>$e->getMessage()], 500);
        }
    }
    public function statusBatch(Request $request)
{
    // Ambil param ids: "1,2,3" atau array ["1","2","3"]
    $ids = collect($request->query('ids', []))
        ->when(is_string($request->query('ids', '')), function ($c) use ($request) {
            return collect(preg_split('/[,\s]+/', (string)$request->query('ids', ''), -1, PREG_SPLIT_NO_EMPTY));
        })
        ->filter()
        ->unique()
        ->values();

    if ($ids->isEmpty()) {
        return response()->json(['success' => false, 'message' => 'Parameter ids kosong'], 422);
    }

    $senders = WahaSender::query()->whereIn('id', $ids)->get();
    if ($senders->isEmpty()) {
        return response()->json(['success' => true, 'data' => []]);
    }

    $svc  = app(\App\Services\WahaService::class);
    $data = [];

    foreach ($senders as $s) {
        try {
            $st = $svc->sessionStatus($s); // bentuk baku: success, connected, state, qr, error, raw
            $data[] = [
                'id'        => $s->id,
                'connected' => $st['connected'],
                'state'     => $st['state'],
                // QR sengaja tidak dimasukkan di batch untuk payload ringan; aktifkan jika butuh:
                // 'qr'     => $st['qr'],
            ];
        } catch (\Throwable $e) {
            $data[] = [
                'id'        => $s->id,
                'connected' => null,
                'state'     => null,
            ];
        }
    }

    return response()->json(['success' => true, 'data' => $data]);
    }
}
