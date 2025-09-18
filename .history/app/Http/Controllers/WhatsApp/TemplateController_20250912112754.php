<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WATemplate;
use App\Http\Requests\WATemplateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Tambahan: ambil data DB untuk isi preview
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

        // ====== BANGUN PREVIEW CONTEXT DARI DB ======
        $user = auth()->user();
        $lead = Lead::query()->latest('id')->first(); // contoh lead terbaru
        $subscription = Subscription::query()->with('lead')->latest('id')->first();

        // Derivasi nama depan/belakang
        $baseName = $lead->name ?? $user->name ?? null;
        $firstName = null;
        $lastName  = null;
        if ($baseName) {
            $parts = preg_split('/\s+/', trim($baseName));
            if ($parts && count($parts) > 0) {
                $firstName = $parts[0];
                if (count($parts) > 1) {
                    $lastName = $parts[count($parts) - 1];
                }
            }
        }

        $previewContext = [
            'app' => [
                'name' => config('app.name'),
                'url'  => config('app.url'),
            ],
            'company' => [
                // ganti sesuai kebutuhan bila ada table settings/perusahaan
                'name'  => config('app.name'),
                'email' => $user->email ?? null,
                'phone' => method_exists($user, 'getAttribute') ? ($user->getAttribute('phone') ?? null) : null,
                'website' => config('app.url'),
            ],
            'user' => [
                'name'  => $user->name  ?? null,
                'email' => $user->email ?? null,
                'phone' => method_exists($user, 'getAttribute') ? ($user->getAttribute('phone') ?? null) : null,
            ],
            'lead' => [
                'name'        => $lead->name  ?? null,
                'first_name'  => $firstName,
                'last_name'   => $lastName,
                'email'       => $lead->email ?? null,
                'phone'       => $lead->phone ?? null,
                'company'     => method_exists($lead, 'getAttribute') ? ($lead->getAttribute('company') ?? null) : null,
                'city'        => method_exists($lead, 'getAttribute') ? ($lead->getAttribute('city') ?? null) : null,
            ],
            'subscription' => [
                'plan'      => $subscription->plan   ?? null,
                'amount'    => $subscription ? ('Rp ' . number_format($subscription->amount ?? 0, 0, ',', '.')) : null,
                'amount_raw'=> $subscription->amount ?? null,
                'cycle'     => $subscription->cycle  ?? null,
                'end_date'  => $subscription && $subscription->end_date ? $subscription->end_date->format('d M Y') : null,
            ],
            'date' => [
                'today' => now()->format('d M Y'),
                'now'   => now()->format('d M Y H:i'),
                'iso'   => now()->toIso8601String(),
            ],
        ];
        // ============================================

        return view('whatsapp.templates.index', compact('templates', 'q', 'status', 'previewContext'));
    }

    public function create()
    {
        // tidak dipakai (create via modal di index)
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

    /**
     * Ambil variabel {{name}} toleran spasi/titik/dash/underscore → kembalikan nama bersih.
     */
    private function extractVariables(string $body): array
    {
        preg_match_all('/\{\{\s*([A-Za-z0-9_.-]+)\s*\}\}/', $body, $matches);
        return array_values(array_unique($matches[1] ?? []));
    }
}
