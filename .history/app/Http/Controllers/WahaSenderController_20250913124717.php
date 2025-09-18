<?php

namespace App\Http\Controllers;

use App\Http\Requests\WahaSenderRequest;
use App\Models\WahaSender;
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

            // Urutan aman
            $q->orderByDesc('is_default');
            if (Schema::hasColumn('waha_senders', 'name')) {
                $q->orderBy('name');
            } else {
                $q->orderBy('id');
            }

            // Kolom aman: utamakan session_name sebagai session
            $cols = ['id', 'is_active', 'is_default'];
            if (Schema::hasColumn('waha_senders', 'session_name')) {
                $cols[] = DB::raw('session_name as session');
            } elseif (Schema::hasColumn('waha_senders', 'session')) {
                $cols[] = 'session';
            }
            if (Schema::hasColumn('waha_senders', 'name'))   $cols[] = 'name';
            if (Schema::hasColumn('waha_senders', 'number')) $cols[] = 'number';

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
            $wahaSender->is_active = true; // default selalu aktif
            $wahaSender->save();
        });

        return response()->json([
            'message' => 'Pengirim default diperbarui.',
            'data' => $wahaSender->only(['id','is_default','is_active']),
        ]);
    }

    /** Helpers */
    private function normalize(array $data, ?int $ignoreId = null): array
    {
        $hasSession      = Schema::hasColumn('waha_senders', 'session');
        $hasSessionName  = Schema::hasColumn('waha_senders', 'session_name');

        $data['is_active']  = (bool)($data['is_active'] ?? true);
        $data['is_default'] = (bool)($data['is_default'] ?? false);

        // Selalu set session_name dari input 'session'; kalau kolom 'session' juga ada, set keduanya.
        if (isset($data['session'])) {
            if ($hasSessionName) {
                $data['session_name'] = $data['session'];
            }
            if (!$hasSession) {
                // tidak ada kolom 'session' → jangan kirim key 'session'
                unset($data['session']);
            }
        }

        // Bersihkan nomor
        if (!empty($data['number'])) {
            $data['number'] = preg_replace('/\D+/', '', $data['number']);
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
            if (Schema::hasColumn('waha_senders', 'name'))   $cols[] = 'name';
            if (Schema::hasColumn('waha_senders', 'number')) $cols[] = 'number';

            $senders = $q->get($cols);
            return response()->json(['message' => $msg, 'data' => $senders]);
        }
        return redirect()->route('waha-senders.index')->with('success', $msg);
    }
}
