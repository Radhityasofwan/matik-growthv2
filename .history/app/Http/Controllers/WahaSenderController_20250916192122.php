<?php

namespace App\Http\Controllers;

use App\Http\Requests\WahaSenderRequest;
use App\Models\WahaSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Notifications\GenericDbNotification;

class WahaSenderController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->boolean('json')) {
            $q = WahaSender::query();
            $q->orderByDesc('is_default');
            if (Schema::hasColumn('waha_senders', 'name')) $q->orderBy('name'); else $q->orderBy('id');

            $cols = ['id', 'is_active', 'is_default'];
            if (Schema::hasColumn('waha_senders','session_name'))      $cols[] = DB::raw('session_name as session');
            elseif (Schema::hasColumn('waha_senders','session'))       $cols[] = 'session';
            if (Schema::hasColumn('waha_senders','name'))              $cols[] = 'name';
            if (Schema::hasColumn('waha_senders','number'))            $cols[] = 'number';
            if (Schema::hasColumn('waha_senders','display_name'))      $cols[] = 'display_name';

            $senders = $q->get($cols);
            return response()->json(['data' => $senders]);
        }

        $q = WahaSender::query()->orderByDesc('is_default');
        if (Schema::hasColumn('waha_senders','name')) $q->orderBy('name'); else $q->orderBy('id');

        $senders = $q->paginate(15);
        return view('waha_senders.index', compact('senders'));
    }

    /** CREATE — tanpa session/number dari form (pakai placeholder) */
    public function store(WahaSenderRequest $request)
    {
        $data = $request->validated();
        $isDefault   = (bool)($data['is_default'] ?? false);
        $placeholder = 'pending-'.Str::uuid();

        $base = [
            'name'         => $data['name'],
            'description'  => $data['description'] ?? null,
            'display_name' => $data['name'],
            'is_default'   => $isDefault,
            'is_active'    => false,
        ];
        if (Schema::hasColumn('waha_senders','session'))      $base['session']      = $placeholder;
        if (Schema::hasColumn('waha_senders','session_name')) $base['session_name'] = $placeholder;
        if (Schema::hasColumn('waha_senders','number'))       $base['number']       = '';

        $sender = null;
        DB::transaction(function () use ($isDefault, $base, &$sender) {
            if ($isDefault) WahaSender::where('is_default', true)->update(['is_default' => false]);
            $sender = WahaSender::create($base);

            // set session key kanonik
            $controller = app(self::class);
            $canonical  = $controller->canonicalSessionKey($sender);
            $updates    = [];
            if (Schema::hasColumn('waha_senders','session'))      $updates['session']      = $canonical;
            if (Schema::hasColumn('waha_senders','session_name')) $updates['session_name'] = $canonical;
            if ($updates) $sender->update($updates);
        });

        // 📣 notif
        $request->user()?->notify(new GenericDbNotification(
            'Sender Ditambahkan',
            "Pengirim \"{$sender->name}\" berhasil dibuat. Lanjutkan proses Scan/Connect.",
            route('waha-senders.index')
        ));

        return redirect()->route('waha-senders.index')
            ->with('success', 'Nomor pengirim berhasil ditambahkan. Silakan Scan / Connect untuk verifikasi.');
    }

    /** UPDATE */
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
                WahaSender::where('id','!=',$wahaSender->id)->where('is_default',true)->update(['is_default'=>false]);
            }
            $payload['is_default'] = $isDefault;
            $wahaSender->update($payload);
        });

        // 📣 notif
        $request->user()?->notify(new GenericDbNotification(
            'Sender Diperbarui',
            "Pengirim \"{$wahaSender->name}\" telah diperbarui.",
            route('waha-senders.index')
        ));

        return redirect()->route('waha-senders.index')->with('success', 'Nomor pengirim berhasil diperbarui.');
    }

    public function destroy(Request $request, WahaSender $wahaSender)
    {
        $name = $wahaSender->name;
        $wahaSender->delete();

        // 📣 notif
        $request->user()?->notify(new GenericDbNotification(
            'Sender Dihapus',
            "Pengirim \"{$name}\" telah dihapus.",
            route('waha-senders.index')
        ));

        return redirect()->route('waha-senders.index')->with('success', 'Nomor pengirim berhasil dihapus.');
    }

    public function toggleActive(Request $request, WahaSender $wahaSender)
    {
        $wahaSender->is_active = ! $wahaSender->is_active;
        $wahaSender->save();

        // 📣 notif
        $request->user()?->notify(new GenericDbNotification(
            'Status Sender',
            "Pengirim \"{$wahaSender->name}\" ".($wahaSender->is_active ? 'diaktifkan' : 'dinonaktifkan').'.',
            route('waha-senders.index')
        ));

        return response()->json(['message'=>'Status pengirim diperbarui.','data'=>$wahaSender->only(['id','is_active'])]);
    }

    public function setDefault(Request $request, WahaSender $wahaSender)
    {
        DB::transaction(function () use ($wahaSender) {
            WahaSender::where('is_default', true)->update(['is_default' => false]);
            $wahaSender->is_default = true;
            $wahaSender->save();
        });

        // 📣 notif
        $request->user()?->notify(new GenericDbNotification(
            'Sender Default',
            "\"{$wahaSender->name}\" dijadikan default.",
            route('waha-senders.index')
        ));

        return response()->json(['message'=>'Pengirim default diperbarui.','data'=>$wahaSender->only(['id','is_default','is_active'])]);
    }

    /** ===================== QR / SESSION ===================== */

    public function qrStatus(WahaSender $wahaSender)
    {
        try {
            $svc  = app(\App\Services\WahaService::class);
            $resp = $svc->sessionStatus($wahaSender);

            $done = ['CONNECTED','READY','WORKING','OPEN','AUTHENTICATED','ONLINE','LOGGED_IN','RUNNING'];
            if (($resp['success'] ?? false)
                && (($resp['connected'] ?? false) === true || in_array(strtoupper((string)($resp['state'] ?? '')),$done,true))) {
                $changed = false;
                if (!$wahaSender->is_active) { $wahaSender->is_active = true; $changed = true; }

                $me = $svc->sessionMe($wahaSender);
                if ($me['success']) {
                    if (Schema::hasColumn('waha_senders','number') && empty($wahaSender->number) && !empty($me['number'])) {
                        $wahaSender->number = $me['number']; $changed = true;
                    }
                    if (Schema::hasColumn('waha_senders','display_name') && !empty($me['display_name'])) {
                        $wahaSender->display_name = $me['display_name']; $changed = true;
                    }
                }
                if ($changed) $wahaSender->save();
            }

            if (($resp['success'] ?? false)
                && strtoupper((string)($resp['state'] ?? '')) === 'SCAN_QR_CODE'
                && empty($resp['qr'])) {
                $resp['qr_url'] = route('waha-senders.qr-image', $wahaSender);
            }

            return response()->json($resp ?? ['success'=>false,'message'=>'Tidak dapat mengambil status.']);
        } catch (\Throwable $e) {
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function qrStart(WahaSender $wahaSender)
    {
        try {
            $canonical = $this->canonicalSessionKey($wahaSender);
            $changed = false;
            if (Schema::hasColumn('waha_senders','session') && $wahaSender->session !== $canonical) {
                $wahaSender->session = $canonical; $changed = true;
            }
            if (Schema::hasColumn('waha_senders','session_name') && $wahaSender->session_name !== $canonical) {
                $wahaSender->session_name = $canonical; $changed = true;
            }
            if ($changed) $wahaSender->save();

            $svc  = app(\App\Services\WahaService::class);
            $resp = $svc->qrStart($wahaSender) ?? $svc->sessionStart($wahaSender);

            if (($resp['success'] ?? false)
                && strtoupper((string)($resp['state'] ?? '')) === 'SCAN_QR_CODE'
                && empty($resp['qr'])) {
                $resp['qr_url'] = route('waha-senders.qr-image', $wahaSender);
            }

            return response()->json($resp ?? ['success'=>false,'message'=>'Gagal memulai sesi.'], ($resp['success'] ?? false) ? 200 : 502);
        } catch (\Throwable $e) {
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function qrLogout(WahaSender $wahaSender)
    {
        try {
            $svc  = app(\App\Services\WahaService::class);
            $resp = $svc->sessionLogout($wahaSender);
            return response()->json($resp ?? ['success'=>false,'message'=>'Gagal logout.'], ($resp['success'] ?? false) ? 200 : 502);
        } catch (\Throwable $e) {
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function qrImage(WahaSender $wahaSender)
    {
        $svc = app(\App\Services\WahaService::class);
        $img = $svc->qrImageBinary($wahaSender);

        if ($img['success'] && !empty($img['body'])) {
            return response($img['body'], 200)
                ->header('Content-Type', $img['ctype'] ?: 'image/png')
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }
        return response('QR not ready', 404);
    }

    public function authRequestCode(WahaSender $wahaSender)
    {
        try {
            $svc  = app(\App\Services\WahaService::class);
            $resp = $svc->requestAuthCode($wahaSender);

            if (($resp['success'] ?? false) && empty($resp['code'])) {
                $resp['success'] = false;
                $resp['error']   = $resp['error'] ?? 'Kode tidak tersedia.';
            }
            return response()->json($resp, ($resp['success'] ?? false) ? 200 : 502);
        } catch (\Throwable $e) {
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    /** Helpers */
    protected function canonicalSessionKey(WahaSender $s): string
    {
        $existing = $s->session ?? $s->session_name ?? null;
        if (is_string($existing)
            && preg_match('/^[A-Za-z0-9._\-]{3,}$/', $existing)
            && !str_starts_with($existing, 'pending-')) {
            return $existing;
        }
        $slug = Str::slug($s->name ?: 'sender', '-'); if ($slug === '') $slug = 'sender';
        return "sender-{$s->id}-{$slug}";
    }

    public function statusBatch(Request $request)
    {
        $ids = collect(explode(',', (string)$request->query('ids', '')))
            ->map(fn($x) => (int)trim($x))->filter(fn($x) => $x > 0)->values();

        if ($ids->isEmpty()) return response()->json(['data'=>[]]);

        $svc = app(\App\Services\WahaService::class);
        $senders = WahaSender::query()->whereIn('id',$ids)->get();

        $rows = [];
        foreach ($senders as $s) {
            try {
                $st = $svc->sessionStatus($s);
                $rows[] = ['id'=>$s->id,'connected'=>$st['connected'] ?? null,'state'=>$st['state'] ?? null];
            } catch (\Throwable $e) {
                $rows[] = ['id'=>$s->id,'connected'=>null,'state'=>null,'error'=>$e->getMessage()];
            }
        }
        return response()->json(['data'=>$rows]);
    }
}
