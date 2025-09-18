<?php

namespace App\Http\Controllers;

use App\Http\Requests\WahaSenderRequest;
use App\Models\WahaSender;
use App\Services\WahaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WahaSenderController extends Controller
{
    /** Index: halaman tabel atau JSON ringan untuk dropdown */
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->boolean('json')) {
            $q = WahaSender::query();
            $q->orderByDesc('is_default');
            if (Schema::hasColumn('waha_senders', 'name')) $q->orderBy('name'); else $q->orderBy('id');

            $cols = ['id','is_active','is_default'];
            if (Schema::hasColumn('waha_senders','session_name')) $cols[] = DB::raw('session_name as session');
            elseif (Schema::hasColumn('waha_senders','session'))  $cols[] = 'session';
            if (Schema::hasColumn('waha_senders','name'))         $cols[] = 'name';
            if (Schema::hasColumn('waha_senders','number'))       $cols[] = 'number';
            if (Schema::hasColumn('waha_senders','description'))  $cols[] = 'description';

            $senders = $q->get($cols);
            return response()->json(['data'=>$senders]);
        }

        $q = WahaSender::query()->orderByDesc('is_default');
        if (Schema::hasColumn('waha_senders','name')) $q->orderBy('name'); else $q->orderBy('id');
        $senders = $q->paginate(15);

        return view('waha_senders.index', compact('senders'));
    }

    public function store(WahaSenderRequest $request)
    {
        $data = $this->normalize($request->validated());

        DB::transaction(function () use ($data) {
            if (!empty($data['is_default'])) {
                WahaSender::where('is_default', true)->update(['is_default' => false]);
            }
            WahaSender::create($data);
        });

        return $this->ok($request, 'Nomor pengirim berhasil ditambahkan.');
    }

    public function update(WahaSenderRequest $request, WahaSender $wahaSender)
    {
        $data = $this->normalize($request->validated(), $wahaSender->id);

        DB::transaction(function () use ($data, $wahaSender) {
            if (!empty($data['is_default'])) {
                WahaSender::where('id','!=',$wahaSender->id)
                    ->where('is_default', true)->update(['is_default' => false]);
            }
            $wahaSender->update($data);
        });

        return $this->ok($request, 'Nomor pengirim berhasil diperbarui.');
    }

    public function destroy(Request $request, WahaSender $wahaSender)
    {
        $wahaSender->delete();
        return $this->ok($request, 'Nomor pengirim berhasil dihapus.');
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
            $wahaSender->is_active = true; // default selalu aktif (setelah connect)
            $wahaSender->save();
        });

        return response()->json([
            'message' => 'Pengirim default diperbarui.',
            'data' => $wahaSender->only(['id','is_default','is_active']),
        ]);
    }

    /** ======== QR / Status / Start Session ======== */

    // Tampilkan QR / status. Jika belum punya session → buat otomatis.
    public function qr(Request $request, WahaSender $wahaSender, WahaService $waha)
    {
        // Pastikan punya nama session
        $session = $this->ensureSessionName($wahaSender);

        // Cek state
        $state = $waha->sessionState($wahaSender);
        if ($state && ($state['connected'] ?? false)) {
            if (!$wahaSender->is_active) {
                $wahaSender->is_active = true;
                $wahaSender->save();
            }
            return response()->json([
                'status'  => 'CONNECTED',
                'message' => 'Perangkat sudah terhubung.',
                'qr'      => null,
                'raw'     => $state['raw'] ?? $state,
                'session' => $session,
            ]);
        }

        // Ambil QR
        $qr = $waha->sessionQr($wahaSender);
        if ($qr && !empty($qr['dataUri'])) {
            return response()->json([
                'status'  => 'SCAN',
                'message' => 'Scan QR di perangkat WhatsApp Anda.',
                'qr'      => $qr['dataUri'],
                'raw'     => $qr['raw'] ?? null,
                'session' => $session,
            ]);
        }

        // Belum ada QR → start session
        $start = $waha->startSession($wahaSender);

        return response()->json([
            'status'  => 'PENDING',
            'message' => 'Session belum aktif. Mencoba memulai session, lalu coba lagi.',
            'qr'      => null,
            'raw'     => ['state'=>$state, 'start'=>$start],
            'session' => $session,
        ], 202);
    }

    // Start / restart session (mis. tombol “Start / Restart Session”)
    public function restart(Request $request, WahaSender $wahaSender, WahaService $waha)
    {
        $session = $this->ensureSessionName($wahaSender);
        $start = $waha->startSession($wahaSender);
        return response()->json([
            'message' => 'Perintah start/restart session dikirim.',
            'session' => $session,
            'data'    => $start,
        ]);
    }

    /** ================= Helpers ================= */

    private function normalize(array $data, ?int $ignoreId = null): array
    {
        $hasSession      = Schema::hasColumn('waha_senders', 'session');
        $hasSessionName  = Schema::hasColumn('waha_senders', 'session_name');
        $hasDisplayName  = Schema::hasColumn('waha_senders', 'display_name');

        // Default: BELUM aktif sampai berhasil connect
        $data['is_active']  = (bool)($data['is_active'] ?? false);
        $data['is_default'] = (bool)($data['is_default'] ?? false);

        // Map session -> session_name (kalau user isi manual)
        if (isset($data['session'])) {
            if ($hasSessionName) { $data['session_name'] = $data['session']; }
            if (!$hasSession)    { unset($data['session']); }
        }

        // Bersihkan nomor
        if (!empty($data['number'])) {
            $data['number'] = preg_replace('/\D+/', '', $data['number']);
        }

        // display_name opsional
        if ($hasDisplayName) {
            if (!isset($data['display_name']) || $data['display_name'] === '') {
                $candidates = [
                    $data['name']         ?? null,
                    $data['number']       ?? null,
                    $data['session']      ?? null,
                    $data['session_name'] ?? null,
                ];
                foreach ($candidates as $c) {
                    if (!empty($c)) { $data['display_name'] = $c; break; }
                }
                if (empty($data['display_name'])) $data['display_name'] = 'Sender';
            }
        } else {
            unset($data['display_name']);
        }

        return $data;
    }

    // Pastikan sender punya nama session; jika belum → buat otomatis & simpan
    private function ensureSessionName(WahaSender $s): string
    {
        $hasSession     = Schema::hasColumn('waha_senders','session');
        $hasSessionName = Schema::hasColumn('waha_senders','session_name');

        $current = $s->session ?? $s->session_name;
        if ($current) return $current;

        $base = strtolower(preg_replace('/[^a-z0-9]+/i','-', $s->name ?: ($s->number ?: 'sender')));
        $base = trim($base, '-') ?: 'sender';
        $candidate = $base;

        $i = 1;
        while (
            WahaSender::where(function($q) use ($candidate){
                $q->where('session', $candidate)->orWhere('session_name', $candidate);
            })->where('id','!=',$s->id)->exists()
        ) {
            $candidate = $base.'-'.$i++;
        }

        if (
