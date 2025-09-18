<?php

namespace App\Http\Controllers;

use App\Http\Requests\WahaSenderRequest;
use App\Models\WahaSender;
use App\Services\WahaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class WahaSenderController extends Controller
{
    protected WahaService $waha;

    public function __construct(WahaService $waha)
    {
        $this->waha = $waha;
    }

    /** Index: halaman tabel atau JSON ringan untuk dropdown */
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->boolean('json')) {
            $q = WahaSender::query();

            // Order aman
            $q->orderByDesc('is_default');
            if (Schema::hasColumn('waha_senders', 'name')) {
                $q->orderBy('name');
            } else {
                $q->orderBy('id');
            }

            // Pilih kolom aman + alias session_name -> session
            $cols = ['id', 'is_active', 'is_default'];
            if (Schema::hasColumn('waha_senders', 'session_name')) {
                $cols[] = DB::raw('session_name as session');
            } elseif (Schema::hasColumn('waha_senders', 'session')) {
                $cols[] = 'session';
            }
            if (Schema::hasColumn('waha_senders', 'name'))         $cols[] = 'name';
            if (Schema::hasColumn('waha_senders', 'number'))       $cols[] = 'number';
            if (Schema::hasColumn('waha_senders', 'display_name')) $cols[] = 'display_name';

            $senders = $q->get($cols);
            return response()->json(['data' => $senders]);
        }

        $q = WahaSender::query()->orderByDesc('is_default');
        if (Schema::hasColumn('waha_senders', 'name')) {
            $q->orderBy('name');
        } else {
            $q->orderBy('id');
        }
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
                WahaSender::where('id', '!=', $wahaSender->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
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
            'data'    => $wahaSender->only(['id', 'is_active']),
        ]);
    }

    public function setDefault(Request $request, WahaSender $wahaSender)
    {
        DB::transaction(function () use ($wahaSender) {
            WahaSender::where('is_default', true)->update(['is_default' => false]);
            $wahaSender->is_default = true;
            $wahaSender->is_active  = true; // default selalu aktif
            $wahaSender->save();
        });

        return response()->json([
            'message' => 'Pengirim default diperbarui.',
            'data'    => $wahaSender->only(['id', 'is_default', 'is_active']),
        ]);
    }

    /* ================= QR / SESSION (Scan & Status) ================= */

    /** Mulai sesi (request ke WAHA untuk menampilkan QR) */
    public function qrStart(WahaSender $wahaSender)
    {
        $session = $this->senderSession($wahaSender);
        $r = $this->waha->startSession($session);

        if ($r['ok'] ?? false) {
            // Opsional: tandai nonaktif saat belum connected (UI bisa ubah berdasarkan status)
            if (!$wahaSender->is_active) {
                $wahaSender->is_active = true; // izinkan proses koneksi
                $wahaSender->save();
            }

            return response()->json([
                'success' => true,
                'data'    => $r,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal memulai sesi (Start failed: ' . ($r['code'] ?? '502') . ').',
            'data'    => $r,
        ], 502);
    }

    /** Cek status sesi + ambil QR (dipoll oleh UI) */
    public function qrStatus(WahaSender $wahaSender)
    {
        $session = $this->senderSession($wahaSender);
        $r = $this->waha->sessionStatus($session);

        if ($r['ok'] ?? false) {
            // Ketika sudah CONNECTED, pastikan aktif = true
            if (($r['state'] ?? '') === 'CONNECTED' && !$wahaSender->is_active) {
                $wahaSender->is_active = true;
                $wahaSender->save();
            }

            return response()->json([
                'success' => true,
                'data'    => $r,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Tidak dapat mengambil status/QR dari WAHA.',
            'data'    => $r,
        ], 502);
    }

    /** Logout / disconnect sesi di WAHA (opsional di UI) */
    public function qrLogout(WahaSender $wahaSender)
    {
        $session = $this->senderSession($wahaSender);

        $paths = [
            "/api/sessions/{$session}/logout",
            "/api/session/{$session}/logout",
            "/api/{$session}/logout",
        ];

        $ok = false;
        $status = null;
        $body = null;

        foreach ($paths as $p) {
            try {
                $res = $this->http()->post($this->wahaUrl($p));
                $status = $res->status();
                if ($res->successful()) {
                    $ok = true;
                    $body = $res->json() ?? $res->body();
                    break;
                }
            } catch (\Throwable $e) {
                // continue
            }
        }

        if ($ok) {
            // tandai nonaktif setelah logout
            $wahaSender->is_active = false;
            $wahaSender->save();

            return response()->json([
                'success' => true,
                'data'    => ['status' => $status, 'raw' => $body],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal logout dari WAHA.',
        ], 502);
    }

    /** Alias: beberapa UI mungkin memanggil restart */
    public function restart(WahaSender $wahaSender)
    {
        return $this->qrStart($wahaSender);
    }

    /** (Opsional) Route GET /waha-senders/{id}/qr: arahkan kembali ke index */
    public function qr(WahaSender $wahaSender)
    {
        return redirect()->route('waha-senders.index');
    }

    /* ================= Helpers ================= */

    private function normalize(array $data, ?int $ignoreId = null): array
    {
        $hasSession      = Schema::hasColumn('waha_senders', 'session');
        $hasSessionName  = Schema::hasColumn('waha_senders', 'session_name');
        $hasDisplayName  = Schema::hasColumn('waha_senders', 'display_name');

        // Boolean
        $data['is_active']  = (bool)($data['is_active'] ?? true);
        $data['is_default'] = (bool)($data['is_default'] ?? false);

        // Map session -> session_name (isi keduanya kalau dua-duanya ada).
        // Bila session kosong (form readonly), fallback ke 'default' agar startSession bisa jalan.
        if (isset($data['session'])) {
            $sess = trim((string)$data['session']);
            if ($sess === '') $sess = 'default';

            if ($hasSessionName) { $data['session_name'] = $sess; }
            if ($hasSession)     { $data['session']      = $sess;  }
            if (!$hasSession && $hasSessionName) {
                unset($data['session']); // hindari kolom tak ada
            }
        }

        // Bersihkan nomor
        if (!empty($data['number'])) {
            $d = preg_replace('/\D+/', '', $data['number']);
            if (str_starts_with($d, '0')) $d = '62' . substr($d, 1);
            $data['number'] = $d;
        }

        // display_name auto jika kolomnya ada
        if ($hasDisplayName) {
            if (!isset($data['display_name']) || $data['display_name'] === '') {
                $candidates = [
                    $data['name']         ?? null,
                    $data['number']       ?? null,
                    $data['session_name'] ?? ($data['session'] ?? null),
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

    private function ok(Request $request, string $msg)
    {
        if ($request->wantsJson() || $request->boolean('json')) {
            $q = WahaSender::query()->orderByDesc('is_default');
            if (Schema::hasColumn('waha_senders', 'name')) $q->orderBy('name'); else $q->orderBy('id');

            $cols = ['id', 'is_active', 'is_default'];
            if (Schema::hasColumn('waha_senders', 'session_name')) {
                $cols[] = DB::raw('session_name as session');
            } elseif (Schema::hasColumn('waha_senders', 'session')) {
                $cols[] = 'session';
            }
            if (Schema::hasColumn('waha_senders', 'name'))         $cols[] = 'name';
            if (Schema::hasColumn('waha_senders', 'number'))       $cols[] = 'number';
            if (Schema::hasColumn('waha_senders', 'display_name')) $cols[] = 'display_name';

            $senders = $q->get($cols);
            return response()->json(['message' => $msg, 'data' => $senders]);
        }
        return redirect()->route('waha-senders.index')->with('success', $msg);
    }

    private function senderSession(WahaSender $s): string
    {
        return (string) (
            $s->session_name
            ?? $s->session
            ?? 'default'
        );
    }

    /** mini client untuk logout fallback */
    private function http()
    {
        $client = Http::acceptJson()
            ->timeout(30)
            ->withHeaders([
                'x-api-key'  => (string) config('services.waha.key'),
                'User-Agent' => (string) env('WAHA_UA', 'Matik Growth Hub'),
            ]);

        if ((bool) env('WAHA_INSECURE', false)) {
            $client = $client->withoutVerifying();
        }
        return $client;
    }

    private function wahaUrl(string $path): string
    {
        $base = rtrim((string) config('services.waha.url'), '/');
        $p = str_starts_with($path, '/') ? $path : "/{$path}";
        return $base . $p;
    }
}
