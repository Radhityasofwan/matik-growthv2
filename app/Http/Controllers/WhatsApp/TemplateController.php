<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WATemplate;
use App\Http\Requests\WATemplateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

// konteks preview
use App\Models\Lead;
use App\Models\Subscription;

// notif
use App\Notifications\GenericDbNotification;

class TemplateController extends Controller
{
    public function index(Request $request)
    {
        $q      = trim((string) $request->get('q', ''));
        $status = $request->get('status', 'all'); // all|active|inactive

        $templates = WATemplate::query()
            ->when($q !== '', fn ($query) => $query->where(function ($qb) use ($q) {
                $qb->where('name', 'like', "%{$q}%")
                   ->orWhere('body', 'like', "%{$q}%")
                   ->orWhereJsonContains('variables', $q);
            }))
            ->when($status === 'active', fn($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn($q) => $q->where('is_active', false))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // ===== KONTEKS PREVIEW (aman terhadap variasi kolom) =====
        $user = $request->user();
        $lead = Lead::query()->latest('id')->first();
        $subscription = $lead
            ? Subscription::query()->where('lead_id', $lead->id)->latest('id')->first()
            : null;

        $leadRegistered = $lead?->registered_at ?? $lead?->created_at ?? null;
        $leadExpiry     = $lead?->expiry_date ?? $lead?->end_date ?? ($subscription?->end_date);

        $previewContext = [
            'lead' => [
                'status'        => $lead?->status ?? null,
                'store_name'    => $lead?->store_name ?? $lead?->nama_toko ?? $lead?->toko ?? null,
                'phone'         => $lead?->phone ?? null,
                'email'         => $lead?->email ?? null,
                'registered_at' => $leadRegistered ? Carbon::parse($leadRegistered)->format('d M Y') : null,
                'expiry_date'   => $leadExpiry ? Carbon::parse($leadExpiry)->format('d M Y') : null,
            ],
            'owner' => [
                'name' => ($lead?->owner_name ?? $lead?->pemilik ?? null) ?: ($user?->name ?? null),
            ],
        ];
        // =========================================================

        return view('whatsapp.templates.index', compact('templates','q','status','previewContext'));
    }

    public function create()
    {
        $template = new WATemplate();
        return view('whatsapp.templates.form', compact('template'));
    }

    public function store(WATemplateRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = array_key_exists('is_active', $data) ? (bool)$data['is_active'] : true;
        $data['variables'] = $this->extractVariables($data['body']);

        $tpl = WATemplate::create($data);

        // 📣 notif
        $request->user()?->notify(new GenericDbNotification(
            'Template WA Dibuat',
            "Template \"{$tpl->name}\" berhasil dibuat.",
            route('whatsapp.templates.index')
        ));

        return redirect()->route('whatsapp.templates.index')->with('success', 'Template created successfully.');
    }

    public function update(WATemplateRequest $request, $whatsapp_template)
    {
        $data = $request->validated();
        $template = WATemplate::findOrFail($whatsapp_template);

        $payload = [
            'name'      => $data['name'],
            'body'      => $data['body'],
            'is_active' => array_key_exists('is_active', $data) ? (bool)$data['is_active'] : $template->is_active,
            'variables' => $this->extractVariables($data['body']),
        ];

        DB::transaction(function () use ($template, $payload) {
            $template->fill($payload)->save();
        });

        // 📣 notif
        $request->user()?->notify(new GenericDbNotification(
            'Template WA Diperbarui',
            "Template \"{$template->name}\" telah diperbarui.",
            route('whatsapp.templates.index')
        ));

        return redirect()->route('whatsapp.templates.index')->with('success', 'Template updated successfully.');
    }

    public function destroy(Request $request, $whatsapp_template)
    {
        $template = WATemplate::findOrFail($whatsapp_template);
        $name = $template->name;

        $deleted = false;
        DB::transaction(function () use ($template, &$deleted) {
            $deleted = (bool) $template->delete();
        });

        // 📣 notif
        $request->user()?->notify(new GenericDbNotification(
            'Template WA Dihapus',
            "Template \"{$name}\" telah dihapus.",
            route('whatsapp.templates.index')
        ));

        return redirect()->route('whatsapp.templates.index')->with('success', $deleted ? 'Template deleted successfully.' : 'Delete failed.');
    }

    private function extractVariables(string $body): array
    {
        preg_match_all('/\{\{\s*([A-Za-z0-9_.-]+)\s*\}\}/', $body, $m);
        return array_values(array_unique($m[1] ?? []));
    }
}
