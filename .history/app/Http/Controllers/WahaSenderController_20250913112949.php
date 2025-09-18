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
        // Tentukan kolom aman
        $cols = ['id', 'number', 'session', 'is_active', 'is_default'];
        if (Schema::hasColumn('waha_senders', 'name')) {
            array_splice($cols, 1, 0, 'name');
        }

        $query = WahaSender::query()
            ->orderByDesc('is_default')
            ->when(
                Schema::hasColumn('waha_senders', 'name'),
                fn ($q) => $q->orderBy('name'),
                fn ($q) => $q->orderBy('id')
            );

        if ($request->wantsJson()) {
            $senders = $query->get($cols);
            return response()->json(['data' => $senders]);
        }

        $senders = $query->paginate(15, $cols);
        return view('waha_senders.index', compact('senders'));
    }

    /** Simpan baru */
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

    /** Update */
    public function update(WahaSenderRequest $request, WahaSender $wahaSender)
    {
        $data = $this->normalize($request->validated(), $wahaSender->id);

        DB::transaction(function () use ($data, $wahaSender) {
            if (!empty($data['is_default'])) {
                WahaSender::where('id', '!=', $wahaSender->id)
                    ->where('is_default', true)->update(['is_default' => false]);
            }
            $wahaSender->update($data);
        });

        return $this->ok($request, 'Nomor pengirim berhasil diperbarui.');
    }

    /** Hapus */
    public function destroy(Request $request, WahaSender $wahaSender)
    {
        $wahaSender->delete();
        return $this->ok($request, 'Nomor pengirim berhasil dihapus.');
    }

    /** Toggle aktif/nonaktif (AJAX) */
    public function toggleActive(Request $request, WahaSender $wahaSender)
    {
        $wahaSender->is_active = ! $wahaSender->is_active;
        $wahaSender->save();

        return response()->json([
            'message' => 'Status pengirim diperbarui.',
            'data' => $wahaSender->only(['id','is_active']),
        ]);
    }

    /** Set default (AJAX) */
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
        $data['is_active']  = (bool)($data['is_active'] ?? true);
        $data['is_default'] = (bool)($data['is_default'] ?? false);
        if (!empty($data['number'])) {
            $data['number'] = preg_replace('/\D+/', '', $data['number']);
        }
        return $data;
    }

    private function ok(Request $request, string $msg)
    {
        // pakai kolom aman untuk response JSON juga
        $cols = ['id', 'number', 'session', 'is_active', 'is_default'];
        if (Schema::hasColumn('waha_senders', 'name')) {
            array_splice($cols, 1, 0, 'name');
        }

        if ($request->wantsJson()) {
            $senders = WahaSender::orderByDesc('is_default')
                ->when(
                    Schema::hasColumn('waha_senders', 'name'),
                    fn ($q) => $q->orderBy('name'),
                    fn ($q) => $q->orderBy('id')
                )
                ->get($cols);
            return response()->json(['message' => $msg, 'data' => $senders]);
        }

        return redirect()->route('waha-senders.index')->with('success', $msg);
    }
}
