<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WATemplate;
use App\Http\Requests\WATemplateRequest;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
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

        return view('whatsapp.templates.index', compact('templates', 'q', 'status'));
    }

    /**
     * Show the form for creating a new resource. (tidak dipakai: modal di index)
     */
    public function create()
    {
        $template = new WATemplate();
        return view('whatsapp.templates.form', compact('template'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(WATemplateRequest $request)
    {
        $data = $request->validated();

        $data['is_active'] = array_key_exists('is_active', $data)
            ? (bool) $data['is_active']
            : true;

        // Simpan variabel ter-normalisasi (tanpa kurung ganda)
        $data['variables'] = $this->extractVariables($data['body']);

        WATemplate::create($data);

        return redirect()
            ->route('whatsapp.templates.index')
            ->with('success', 'Template created successfully.');
    }

    /**
     * Show the form for editing the specified resource. (tidak dipakai: modal di index)
     */
    public function edit(WATemplate $template)
    {
        return view('whatsapp.templates.form', compact('template'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(WATemplateRequest $request, WATemplate $template)
    {
        $data = $request->validated();

        $data['is_active'] = array_key_exists('is_active', $data)
            ? (bool) $data['is_active']
            : $template->is_active;

        // Re-ekstrak & normalisasi variabel dari body baru
        $data['variables'] = $this->extractVariables($data['body']);

        $template->update($data);

        return redirect()
            ->route('whatsapp.templates.index')
            ->with('success', 'Template updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WATemplate $template)
    {
        // Tidak menggunakan soft deletes, jadi langsung delete
        $template->delete();

        return redirect()
            ->route('whatsapp.templates.index')
            ->with('success', 'Template deleted successfully.');
    }

    /**
     * Extract variables like {{name}} (tolerant to spaces / dot / dash / underscore).
     * Return unique variable names WITHOUT curly braces.
     */
    private function extractVariables(string $body): array
    {
        // match: {{name}}, {{ name }}, {{first-name}}, {{user.email}}
        preg_match_all('/\{\{\s*([A-Za-z0-9_.-]+)\s*\}\}/', $body, $matches);
        $vars = array_values(array_unique($matches[1] ?? []));
        return $vars;
    }
}
