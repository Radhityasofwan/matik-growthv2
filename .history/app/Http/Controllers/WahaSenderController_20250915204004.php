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

        /** CREATE — hanya nama/desc/is_default. Session/number diisi nanti saat Scan/Connect */
        public function store(WahaSenderRequest $request)
        {
            $data = $request->validated();

            $isDefault   = (bool)($data['is_default'] ?? false);
            $placeholder = 'pending-'.Str::uuid(); // aman untuk NOT NULL + UNIQUE

            $base = [
                'name'         => $data['name'],
                'description'  => $data['description'] ?? null,
                'display_name' => $data['name'],
                'is_default'   => $isDefault,
                'is_active'    => false,
            ];

            // SELALU isi placeholder agar lolos NOT NULL/UNIQUE
            if (Schema::hasColumn('waha_senders', 'session')) {
                $base['session'] = $placeholder;
            }
            if (Schema::hasColumn('waha_senders', 'session_name')) {
                $base['session_name'] = $placeholder;
            }
            if (Schema::hasColumn('waha_senders', 'number')) {
                $base['number'] = ''; // akan terisi setelah connect
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
    try {
        $svc  = app(\App\Services\WahaService::class);
        $resp = $svc->sessionStatus($wahaSender);

        // Jika sedang minta scan & belum ada QR, kirim URL proxy agar FE bisa render gambar
        if (($resp['success'] ?? false)
            && strtoupper((string)($resp['state'] ?? '')) === 'SCAN_QR_CODE'
            && empty($resp['qr'])) {
            $resp['qr_url'] = route('waha-senders.qr-image', $wahaSender);
        }

        return response()->json($resp ?? ['success'=>false,'message'=>'Tidak dapat mengambil status.']);
    } catch (\Throwable $e) {
        return response()->json(['success'=>false, 'message'=>$e->getMessage()], 500);
    }
    }


    // POST /waha-senders/{sender}/qr-start
    public function qrStart(WahaSender $wahaSender)
    {
    try {
        // pastikan session key kanonik
        $canonical = $this->canonicalSessionKey($wahaSender);
        $changed = false;
        if (\Schema::hasColumn('waha_senders', 'session') && $wahaSender->session !== $canonical) { $wahaSender->session = $canonical; $changed = true; }
        if (\Schema::hasColumn('waha_senders', 'session_name') && $wahaSender->session_name !== $canonical) { $wahaSender->session_name = $canonical; $changed = true; }
        if ($changed) $wahaSender->save();

        $svc  = app(\App\Services\WahaService::class);
        $resp = $svc->qrStart($wahaSender);

        // siapkan qr_url fallback
        if (($resp['success'] ?? false)
            && strtoupper((string)($resp['state'] ?? '')) === 'SCAN_QR_CODE'
            && empty($resp['qr'])) {
            $resp['qr_url'] = route('waha-senders.qr-image', $wahaSender);
        }

        $code = ($resp && ($resp['success'] ?? false)) ? 200 : 502;
        return response()->json($resp ?? ['success'=>false,'message'=>'Gagal memulai sesi.'], $code);
    } catch (\Throwable $e) {
        return response()->json(['success'=>false, 'message'=>$e->getMessage()], 500);
    }
    }


    // POST /waha-senders/{sender}/qr-logout
    public function qrLogout(WahaSender $wahaSender)
    {
        try {
            $svc = app(\App\Services\WahaService::class);
            $resp = $svc->sessionLogout($wahaSender);
            return response()->json($resp ?? ['success'=>false,'message'=>'Gagal logout.'], $resp?200:502);
        } catch (\Throwable $e) {
            return response()->json(['success'=>false, 'message'=>$e->getMessage()], 500);
        }
    }

    // === Batch status (dipanggil dari Blade broadcast) ===
    public function statusBatch(Request $request)
    {
        // dukung ids="1,2,3" atau array
        $ids = collect($request->query('ids', []))
            ->when(is_string($request->query('ids', '')), function ($c) use ($request) {
                return collect(preg_split('/[,\s]+/', (string)$request->query('ids', ''), -1, PREG_SPLIT_NO_EMPTY));
            })
            ->filter()->unique()->values();

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
                $st = $svc->sessionStatus($s);
                $data[] = [
                    'id'        => $s->id,
                    'connected' => $st['connected'],
                    'state'     => $st['state'],
                ];
            } catch (\Throwable $e) {
                $data[] = ['id'=>$s->id, 'connected'=>null, 'state'=>null];
            }
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    /* ========================= Helpers ========================= */

    /**
     * Buat session key kanonik & stabil untuk dipakai di WAHA.
     * Format: sender-{id}-{slug(nama)} -> dibersihkan ke [A-Za-z0-9._-], max 64 char.
     */
    protected function canonicalSessionKey(WahaSender $s): string
    {
        $basis = $s->name ?: $s->display_name ?: 'sender';
        // slug drainasi dasar
        $slug  = Str::slug($basis, '-');
        $raw   = "sender-{$s->id}" . ($slug ? "-{$slug}" : '');

        // bersihkan ke set aman (A-Za-z0-9._-)
        $clean = preg_replace('/[^A-Za-z0-9._-]+/', '-', $raw);
        // rapikan strip ganda
        $clean = trim(preg_replace('/-+/', '-', $clean), '-');
        // batas panjang 64
        return substr($clean ?: "sender-{$s->id}", 0, 64);
    }

    // GET /waha-senders/{sender}/qr-image  (nama route: waha-senders.qr-image)
    public function qrImage(WahaSender $wahaSender)
    {
    $svc = app(\App\Services\WahaService::class);
    $img = $svc->qrImageBinary($wahaSender);

    if ($img['success']) {
        return response($img['body'], 200)->header('Content-Type', $img['ctype'] ?: 'image/png');
    }
    // 204 agar <img> tidak menampilkan error broken, FE akan mencoba ulang pada polling berikutnya
    return response('', 204);
    }
}
