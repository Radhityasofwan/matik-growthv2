<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WATemplate;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = WATemplate::latest()->paginate(10);
        return view('whatsapp.templates.index', compact('templates'));
    }

    public function create()
    {
        return view('whatsapp.templates.form');
    }



    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:wa_templates,name',
            'body' => 'required|string',
        ]);

        // Ekstrak variabel dari body, e.g., {{name}}
        preg_match_all('/{{(.*?)}}/', $validated['body'], $matches);
        $variables = !empty($matches[0]) ? array_unique($matches[0]) : null;

        WATemplate::create($validated + ['variables' => $variables]);

        return redirect()->route('whatsapp.templates.index')->with('success', 'Template created successfully.');
    }

    public function edit(WATemplate $template)
    {
        return view('whatsapp.templates.form', ['template' => $template]);
    }

    public function update(Request $request, WATemplate $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:wa_templates,name,' . $template->id,
            'body' => 'required|string',
        ]);

        preg_match_all('/{{(.*?)}}/', $validated['body'], $matches);
        $variables = !empty($matches[0]) ? array_unique($matches[0]) : null;

        $template->update($validated + ['variables' => $variables]);

        return redirect()->route('whatsapp.templates.index')->with('success', 'Template updated successfully.');
    }

    public function destroy(WATemplate $template)
    {
        $template->delete();
        return redirect()->route('whatsapp.templates.index')->with('success', 'Template deleted successfully.');
    }
}
