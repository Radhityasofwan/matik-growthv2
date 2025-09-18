<?php

namespace App\Http\Controllers;

use App\Http\Requests\WahaSenderRequest;
use App\Models\WahaSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class WahaSenderController extends Controller
{
    /* =========================================================
     |  INDEX (HTML atau JSON ringan untuk dropdown)
     * ========================================================= */
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->boolean('json')) {
            $q = WahaSender::query();

            // Urutkan aman
            $q->orderByDesc('is_default');
            if (Schema::hasColumn('waha_senders', 'name')) {
                $q->orderBy('name');
            } else {
                $q->orderBy('id');
            }

            // Kolom aman (alias session_name -> session bila ada)
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
        if (Schema::hasColumn('waha_senders', 'name')) {
            $q->orderBy('name');
        } else {
            $q->orderBy('id');
        }

        $senders = $q->paginate(15);

        return view('waha_senders.index', compact('senders'));
    }

    /* =========================================================
     |  STORE
     * ========================================================= */
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

    /* =========================================================
     |  UPDATE
     * ========================================================= */
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

    /* =========================================================
     |  DESTROY
     * ========================================================= */
    public function destroy(Request $request, WahaSender $wahaSender)
    {
        $wahaSender->delete();

        return $this->ok($request, 'Nomor pengirim berhasil dihapus.');
    }

    /* =========================================================
     |  TOGGLE ACTIVE
     * ========================================================= */
    public function toggleActive(Request $request, WahaSender $wahaSender)
    {
        $wahaSender->is_active = ! $wahaSender->is_active;
        $wahaSender->save();

        return response()->json([
            'message' => 'Status pengirim diperbarui.',
            'data'    => $wahaSender->only(['id', 'is_active']),
        ]);
    }

    /* =========================================================
     |  SET DEFAULT
     * ========================================================= */
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

    /* =========================================================
     |  OPSIONAL: QR Helpers (cek/start/logout) untuk WAHA
     |  – Tidak akan error walau belum dipetakan di routes.
     |  – Dipakai bila nanti Anda ingin tombol Scan QR di halaman.
     * ========================================================= */

    /** Cek keberadaan sesi via ping ringan ke /api/sendText (akan 422 jika sesi tidak ada) */
    public function qrStatus(WahaSender $wahaSender)
    {
        [$base, $http] = $this->wahaClient();
        $session = $this->resolveSession($wahaSender);

        $resp = $http->post($base . '/api/sendText', [
            'chatId'  => '0@c.us',
            'text'    => 'ping',
            'session' => $session,
        ]);

        $ok     = $resp->successful();
        $status = $resp->status();
        $body   = $this->safeJson($resp->body());

        $exists = $ok || ($status !== 422); // 422 biasanya "Session \"xxx\" does not exist"

        return response()->json([
            'success'     => true,
            'session'     => $session,
            'exists'      => $exists,
            'http_status' => $status,
            'raw'         => $body,
        ]);
    }

    /** Mulai sesi & minta QR: coba beberapa endpoint umum WAHA */
    public function qrStart(WahaSender $wahaSender)
    {
        [$base, $http] = $this->wahaClient();
        $session = $this->resolveSession($wahaSender);

        $candidates = [
            // format baru (tanpa path session)
            ['POST', '/api/start', ['session' => $session]],
            // format lama (dengan path session)
            ['POST', "/api/{$this->safe($session)}/start", []],
        ];

        foreach ($candidates as [$m, $path, $payload]) {
            $res = $http->{strtolower($m)}($base . $path, $payload);
            if ($res->successful()) {
                return response()->json([
                    'success' => true,
                    'session' => $session,
                    'data'    => $res->json(),
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal memulai sesi / meminta QR dari WAHA.',
        ], 502);
    }

    /** Logout / tutup sesi */
    public function qrLogout(WahaSender $wahaSender)
    {
        [$base, $http] = $this->wahaClient();
        $session = $this->resolveSession($wahaSender);

        $candidates = [
            ['POST', '/api/logout', ['session' => $session]],
            ['POST', "/api/{$this->safe($session)}/logout", []],
            ['POST', "/api/{$this->safe($session)}/close", []],
        ];

        foreach ($candidates as [$m, $path, $payload]) {
            $res = $http->{strtolower($m)}($base . $path, $payload);
            if ($res->successful()) {
                return response()->json([
                    'success' => true,
                    'session' => $session,
                    'data'    => $res->json(),
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal logout/menutup sesi di WAHA.',
        ], 502);
    }

    /* =========================================================
     |  HELPERS
     * ========================================================= */
    private function normalize(array $data, ?int $ignoreId = null): array
    {
        $hasSession      = Schema::hasColumn('waha_senders', 'session');
        $hasSessionName  = Schema::hasColumn('waha_senders', 'session_name');
        $hasDisplayName  = Schema::hasColumn('waha_senders', 'display_name');

        // Boolean aman
        $data['is_active']  = (bool)($data['is_active'] ?? true);
        $data['is_default'] = (bool)($data['is_default'] ?? false);

        // Map session -> session_name bila kolomnya ada
        if (isset($data['session'])) {
            if ($hasSessionName) {
                $data['session_name'] = $data['session'];
            }
            if (!$hasSession && $hasSessionName) {
                unset($data['session']);
            }
        }

        // Bersihkan nomor
        if (!empty($data['number'])) {
            $data['number'] = preg_replace('/\D+/', '', $data['number']);
        }

        // Isi display_name bila kolomnya ada
        if ($hasDisplayName) {
            if (!isset($data['display_name']) || $data['display_name'] === '') {
                $candidates = [
                    $data['name'] ?? null,
                    $data['number'] ?? null,
                    $data['session'] ?? null,
                    $data['session_name'] ?? null,
                ];
                foreach ($candidates as $c) {
                    if (!empty($c)) {
                        $data['display_name'] = $c;
                        break;
                    }
                }
                if (empty($data['display_name'])) {
                    $data['display_name'] = 'Sender';
                }
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
            if (Schema::hasColumn('waha_senders', 'name')) {
                $q->orderBy('name');
            } else {
                $q->orderBy('id');
            }

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

            return response()->json(['message' => $msg, 'data' => $senders]);
        }

        return redirect()
            ->route('waha-senders.index')
            ->with('success', $msg);
    }

    /* ---------- WAHA low-level HTTP client ---------- */

    private function wahaClient(): array
    {
        $base  = rtrim((string) config('services.waha.url'), '/'); // contoh: https://waha.matik.id
        $key   = (string) config('services.waha.key');
        $ua    = (string) env('WAHA_UA', 'Matik Growth Hub');
        $insec = (bool) env('WAHA_INSECURE', false);

        $http = Http::acceptJson()
            ->timeout(30)
            ->withHeaders([
                'x-api-key'  => $key,      // WAHA (Node/Express) menerima lowercase header
                'User-Agent' => $ua,
            ]);

        if ($insec) {
            $http = $http->withoutVerifying();
        }

        return [$base, $http];
    }

    private function resolveSession(WahaSender $sender): string
    {
        foreach (['session', 'session_name', 'sessionId', 'session_key'] as $f) {
            if (!empty($sender->{$f})) {
                return (string) $sender->{$f};
            }
        }
        return (string) ($sender->session ?? 'default');
    }

    private function safe(string $s): string
    {
        return rawurlencode($s);
    }

    private function safeJson(string $body)
    {
        try {
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            return $body;
        }
    }
}
