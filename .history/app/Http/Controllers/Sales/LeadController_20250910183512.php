<?php

namespace App\Http\Controllers\Sales;

use App\Events\TrialCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\LeadRequest;
use App\Models\Lead;
use App\Models\User; // <-- PENTING: Menambahkan model User
use Illuminate\Http\Request;

class LeadController extends Controller
{
    /**
     * Menampilkan daftar semua lead.
     * Method ini juga mengirimkan daftar user untuk form modal.
     */
    public function index(Request $request)
    {
        // Memulai query dengan eager loading relasi 'owner' untuk optimasi
        $query = Lead::query()->with('owner');

        // Logika untuk filter pencarian
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('email', 'like', $searchTerm);
            });
        }

        // Logika untuk filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $leads = $query->latest()->paginate(10)->withQueryString();

        // --- INI PERBAIKANNYA ---
        // Mengambil semua data user dari database
        $users = User::orderBy('name')->get();

        // Mengirimkan data 'leads' dan 'users' ke view
        return view('sales.leads.index', compact('leads', 'users'));
    }

    /**
     * Menampilkan form untuk membuat lead baru.
     * Catatan: Metode ini tidak lagi digunakan jika Anda hanya menggunakan modal.
     */
    public function create()
    {
        $lead = new Lead();
        $users = User::orderBy('name')->get();
        return view('sales.leads.form', compact('lead', 'users'));
    }


    /**
     * Menyimpan data lead baru ke database.
     */
    public function store(LeadRequest $request)
    {
        $lead = Lead::create($request->validated());

        // Jika lead baru statusnya 'trial', jalankan event TrialCreated.
        if ($lead->status === 'trial') {
            TrialCreated::dispatch($lead);
        }

        activity()
            ->performedOn($lead)
            ->causedBy(auth()->user())
            ->log("Membuat lead: {$lead->name}");

        return redirect()->route('leads.index')->with('success', 'Lead berhasil dibuat.');
    }

    /**
     * Menampilkan detail dari satu lead.
     */
    public function show(Lead $lead)
    {
        $activities = $lead->activities()->latest()->paginate(10);
        return view('sales.leads.show', compact('lead', 'activities'));
    }

    /**
     * Menampilkan form untuk mengedit lead.
     * Catatan: Metode ini tidak lagi digunakan jika Anda hanya menggunakan modal.
     */
    public function edit(Lead $lead)
    {
        $users = User::orderBy('name')->get();
        return view('sales.leads.form', compact('lead', 'users'));
    }

    /**
     * Memperbarui data lead di database.
     */
    public function update(LeadRequest $request, Lead $lead)
    {
        $lead->update($request->validated());

        activity()
            ->performedOn($lead)
            ->causedBy(auth()->user())
            ->log("Memperbarui lead: {$lead->name}");

        return redirect()->route('leads.index')->with('success', 'Lead berhasil diperbarui.');
    }

    /**
     * Menghapus data lead dari database.
     */
    public function destroy(Lead $lead)
    {
        $leadName = $lead->name;
        $lead->delete();

        activity()
            ->causedBy(auth()->user())
            ->log('Menghapus lead: ' . $leadName);

        return redirect()->route('leads.index')->with('success', 'Lead berhasil dihapus.');
    }
}
