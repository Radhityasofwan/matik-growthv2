<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WATemplate;
use App\Http\Requests\WATemplateRequest; // <-- Import WATemplateRequest
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class TemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $templates = WATemplate::latest()->paginate(10);
        return view('whatsapp.templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new resource.
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
        $data['variables'] = $this->extractVariables($data['body']);

        WATemplate::create($data);

        return redirect()->route('whatsapp.templates.index')->with('success', 'Template created successfully.');
    }


    /**
     * Show the form for editing the specified resource.
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
        $data['variables'] = $this->extractVariables($data['body']);

        $template->update($data);

        return redirect()->route('whatsapp.templates.index')->with('success', 'Template updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WATemplate $template)
    {
        $template->delete();
        return redirect()->route('whatsapp.templates.index')->with('success', 'Template deleted successfully.');
    }

    /**
     * Extracts variables like {{name}} from the template body.
     */
    private function extractVariables(string $body): array
    {
        // Regex to find all occurrences of {{variable}}
        preg_match_all('/\{\{(\w+)\}\}/', $body, $matches);

        // Return the unique variable names found
        return array_values(array_unique($matches[1]));
    }
}

