<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WATemplate;
use App\Http\Requests\WATemplateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
     * Store a newly created resource in storage.
     */
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

    /**
     * Update the specified resource in storage.
     * NOTE: gunakan $id eksplisit untuk menghindari mismatch binding.
     */
    public function update(WATemplateRequest $request, $id)
    {
        $data = $request->validated();

        /** @var WATemplate $template */
        $template = WATemplate::findOrFail($id);

        $payload = [
            'name'      => $data['name'],
            'body'      => $data['body'],
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $template->is_active,
            'variables' => $this->extractVariables($data['body']),
        ];

        // Transaksi + save di instance -> paling stabil
        DB::transaction(function () use ($template, $payload) {
            $template->fill($payload);
            $template->save();
        });

        return redirect()
            ->route('whatsapp.templates.index')
            ->with('success', 'Template updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     * NOTE: gunakan $id eksplisit untuk menghindari mismatch binding.
     */
    public function destroy($id)
    {
        /** @var WATemplate $template */
        $template = WATemplate::findOrFail($id);

        $deleted = false;
        DB::transaction(function () use ($template, &$deleted) {
            $deleted = $template->delete(); // boolean
        });

        return redirect()
            ->route('whatsapp.templates.index')
            ->with('success', $deleted ? 'Template deleted successfully.' : 'Delete failed.');
    }

    /**
     * Extract variables like {{name}} (tolerant to spaces / dot / dash / underscore).
     * Return unique variable names without curly braces.
     */
    private function extractVariables(string $body): array
    {
        preg_match_all('/\{\{\s*([A-Za-z0-9_.-]+)\s*\}\}/', $body, $matches);
        return array_values(array_unique($matches[1] ?? []));
    }
}
