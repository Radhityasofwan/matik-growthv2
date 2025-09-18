<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WATemplate;
use App\Http\Requests\WATemplateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

// ambil data untuk konteks preview
use App\Models\Lead;
use App\Models\Subscription;

class TemplateController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $status = $request->get('status', 'all'); // all|active|inactive

        $templates = WATemplate::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qb) use ($q) {
                    $qb->where('name', 'like', "%{$q}%")
                        ->orWhere('body', 'like', "%{$q}%")
                        ->orWhereJsonContains('variables', $q);
                });
            })
            ->when($status === 'active', fn($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn($q) => $q->where('is_active', false))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // ===== KONTEKS PREVIEW: hanya variabel yang diminta =====
        $user = auth()->user();
        $lead = Lead::query()->latest('id')->first();
        $subscription = null;
        if ($lead) {
            $subscription = Subscription::query()
                ->where('lead_id', $lead->id)
                ->latest('id')
                ->first();
        }

        // tanggal daftar (prioritas: lead.registered_at -> lead.created_at)
        $leadRegistered = null;
        if ($lead) {
            $leadRegistered = $lead->registered_at ?? $lead->created_at ?? null;
        }

        // tanggal habis (prioritas: lead.expiry_date -> lead.end_date -> subscription.end_date)
        $leadExpiry = null;
        if ($lead) {
            $leadExpiry = $lead->expiry_date
                ?? $lead->end_date
                ?? ($subscription?->end_date);
        }

        $previewContext = [
            'lead' => [
                // status lead
                'status'        => $lead?->status ?? null,

                // nama toko (berbagai kemungkinan kolom)
                'store_name'    => $lead?->store_name
                                    ?? $lead?->nama_toko
                                    ?? $lead?->toko
                                    ?? null,

                // kontak
                'phone'         => $lead?->phone ?? null,
                'email'         => $lead?->email ?? null,

                // tanggal daftar/habis (format manusiawi)
                'registered_at' => $leadRegistered ? Carbon::parse($leadRegistered)->format('d M Y') : null,
                'expiry_date'   => $leadExpiry ? Carbon::parse($leadExpiry)->format('d M Y') : null,
            ],

            // nama owner (prioritas: field owner di lead; fallback: user login)
            'owner' => [
                'name' => ($lead?->owner_name ?? $lead?->pemilik ?? null) ?: ($user?->name ?? null),
            ],
        ];
        // =========================================================

        return view('whatsapp.templates.index', compact('templates', 'q', 'status', 'previewContext'));
    }

    public function create()
    {
        $template = new WATemplate();
        return view('whatsapp.templates.form', compact('template'));
    }

    public function store(WATemplateRequest $request)
    {
        $data = $request->validated();

        $data['is_active'] = array_key_exists('is_active', $data)
            ? (bool) $data['is_active']
            : true;

        $data['variables'] = $this->extractVariables($data['body']);

        WATemplate::create($data);

        return redirect()
            ->route('whatsapp.templates.index')
            ->with('success', 'Template created successfully.');
    }

    public function update(WATemplateRequest $request, $whatsapp_template)
    {
        $data = $request->validated();
        $template = WATemplate::findOrFail($whatsapp_template);

        $payload = [
            'name'      => $data['name'],
            'body'      => $data['body'],
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $template->is_active,
            'variables' => $this->extractVariables($data['body']),
        ];

        DB::transaction(function () use ($template, $payload) {
            $template->fill($payload);
            $template->save();
        });

        return redirect()
            ->route('whatsapp.templates.index')
            ->with('success', 'Template updated successfully.');
    }

    public function destroy($whatsapp_template)
    {
        $template = WATemplate::findOrFail($whatsapp_template);

        $deleted = false;
        DB::transaction(function () use ($template, &$deleted) {
            $deleted = (bool) $template->delete();
        });

        return redirect()
            ->route('whatsapp.templates.index')
            ->with('success', $deleted ? 'Template deleted successfully.' : 'Delete failed.');
    }

    private function extractVariables(string $body): array
    {
        preg_match_all('/\{\{\s*([A-Za-z0-9_.-]+)\s*\}\}/', $body, $matches);
        return array_values(array_unique($matches[1] ?? []));
    }
}
