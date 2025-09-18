<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WAMessage;
use Illuminate\Http\Request;

class LogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $logs = WAMessage::with('lead') // Eager load the lead relationship
            ->latest() // Order by the newest messages first
            ->paginate(20); // Paginate the results

        return view('whatsapp.logs.index', compact('logs'));
    }
}
