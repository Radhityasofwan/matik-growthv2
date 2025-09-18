<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller; // <-- INI PERBAIKANNYA
use App\Models\Subscription;
use App\Models\Lead;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Menampilkan daftar semua langganan dengan fitur filter, search, dan sort.
     */
    public function index(Request $request)
    {
        $query = Subscription::query()->with('lead');

        // 1. Filtering
        if ($request->filled('plan')) {
            $query->where('plan', 'like', '%' . $request->plan . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('cycle')) {
            $query->where('cycle', $request->cycle);
        }

        // 2. Searching (berdasarkan nama lead)
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->whereHas('lead', function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm);
            });
        }

        // 3. Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $subscriptions = $query->paginate(15)->withQueryString();

        return view('subscriptions.index', compact('subscriptions', 'request'));
    }

    /**
     * Menyimpan langganan baru. (Untuk pembuatan manual)
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'lead_id' => 'required|exists:leads,id|unique:subscriptions,lead_id',
            'plan' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'cycle' => 'required|in:monthly,yearly',
            'status' => 'required|in:active,paused,cancelled',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        Subscription::create($validatedData);

        return redirect()->route('subscriptions.index')->with('success', 'Subscription created successfully.');
    }

    /**
     * Memperbarui langganan yang ada.
     */
    public function update(Request $request, Subscription $subscription)
    {
        $validatedData = $request->validate([
            'plan' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'cycle' => 'required|in:monthly,yearly',
            'status' => 'required|in:active,paused,cancelled',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $subscription->update($validatedData);

        return redirect()->route('subscriptions.index')->with('success', 'Subscription updated successfully.');
    }

    /**
     * Menghapus langganan.
     */
    public function destroy(Subscription $subscription)
    {
        $subscription->delete();
        return redirect()->route('subscriptions.index')->with('success', 'Subscription deleted successfully.');
    }
}
