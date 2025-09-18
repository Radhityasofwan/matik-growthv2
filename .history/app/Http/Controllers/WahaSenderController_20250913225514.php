<?php

namespace App\Http\Controllers;

use App\Http\Requests\WahaSenderRequest;
use App\Models\WahaSender;
use App\Services\WahaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WahaSenderController extends Controller
{
    public function __construct(protected WahaService $waha) {}

    /** Index (HTML) */
    public function index(Request $request)
    {
        $q = WahaSender::query()->orderByDesc('is_default')->orderBy('name');
        $senders = $q->paginate(15);
        return view('waha_senders.index', compact('senders'));
    }

    /** Store: hanya name, description, is_default; aktif = false, session = null */
    public function store(WahaSenderRequest $request)
    {
        $data = $request->validated();
        $row = null;

        DB::transaction(function () use ($data, &$row) {
            if (!empty($data['is_default'])) {
                WahaSender::where('is_default', true)->update(['is_default'=>false]);
            }
            $row = WahaSender::create([
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'display_name'=> $data['name'],
                'is_default'  => (bool)($data['is_default'] ?? false),
                'is_active'   => false,  // verifikasi via QR dulu
                'session'     => null,
                'session_name'=> null,
                'number'      => null,
            ]);
        });

        return back()->with('success', 'Sender ditambahkan. Silakan klik "Scan / Connect" untuk verifikasi.');
    }

    /** Update: tetap minimal */
    public function update(WahaSenderRequest $request, WahaSender $wahaSender)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $wahaSender) {
            if (!empty($data['is_default'])) {
                WahaSender::where('id','!=',$wahaSender->id)->where('is_default',true)->update(['is_default'=>false]);
                $wahaSender->is_default = true;
            } else {
                $wahaSender->is_default = (bool)($data['is_default'] ?? false);
            }
            $wahaSender->name         = $data['name'];
            $wahaSender->display_name = $data['name'];
            $wahaSender->description  = $data['description'] ?? null;
            $wahaSender->save();
        });

        return back()->with('success', 'Sender diperbarui.');
    }

    public function destroy(WahaSender $wahaSender)
    {
        $wahaSender->delete();
        return back()->with('success', 'Sender dihapus.');
    }

    public function toggleActive(WahaSender $wahaSender)
    {
        $wahaSender->is_active = ! $wahaSender->is_active;
        $wahaSender->save();
        return response()->json(['message'=>'Status diperbarui','data'=>$wahaSender->only('id','is_active')]);
    }

    public function setDefault(WahaSender $wahaSender)
    {
        DB::transaction(function () use ($wahaSender) {
            WahaSender::where('is_default', true)->update(['is_default'=>false]);
            $wahaSender->is_default = true;
            $wahaSender->save();
        });
        return response()->json(['message'=>'Default diperbarui']);
    }

    /* ---------- QR FLOW ---------- */

    /** Mulai sesi di WAHA (agar QR tersedia). */
    public function qrStart(WahaSender $wahaSender)
    {
        // tentukan label sesi (jika belum ada): slug nama
        $session = $wahaSender->session ?? $wahaSender->session_name ?? Str::slug($wahaSender->name) ?: ('sender-'.$wahaSender->id);
        $ok = $this->waha->ensureStarted($session);

        // simpan nama sesi di DB bila sebelumnya null
        if (empty($wahaSender->session) && empty($wahaSender->session_name)) {
            $wahaSender->session = $session;
            $wahaSender->save();
        }

        return response()->json([
            'success' => $ok,
            'message' => $ok ? 'Start OK' : 'Start mungkin gagal, namun akan tetap dipolling.',
        ], $ok ? 200 : 202);
    }

    /** Poll status; bila CONNECTED → tandai aktif & pastikan session terset. */
    public function qrStatus(WahaSender $wahaSender)
    {
        $session = $wahaSender->session ?? $wahaSender->session_name ?? Str::slug($wahaSender->name) ?: ('sender-'.$wahaSender->id);
        $st = $this->waha->qrStatus($session);

        if (strtoupper($st['state']) === 'CONNECTED') {
            if (!$wahaSender->is_active || empty($wahaSender->session)) {
                $wahaSender->is_active = true;
                $wahaSender->session   = $wahaSender->session ?: $session;
                $wahaSender->save();
            }
        }

        return response()->json(['success'=>true, 'data'=>$st]);
    }

    public function qrLogout(WahaSender $wahaSender)
    {
        $session = $wahaSender->session ?? $wahaSender->session_name ?? Str::slug($wahaSender->name) ?: ('sender-'.$wahaSender->id);
        $ok = $this->waha->logout($session);

        if ($ok) {
            $wahaSender->is_active = false;
            $wahaSender->save();
        }

        return response()->json(['success'=>$ok]);
    }
}
