<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Menampilkan daftar semua langganan dengan fitur filter, search, dan sort.
     */
    public function index(Request $request)
    {
        $query = Subscription::query()->with('lead');

        // Filtering
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('cycle')) {
            $query->where('cycle', $request->cycle);
        }

        // Searching
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->whereHas('lead', function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm);
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // --- INI PERBAIKANNYA ---
        // Mengambil nilai per_page dari request, dengan nilai default 10
        $perPage = $request->input('per_page', 10);

        $subscriptions = $query->paginate($perPage)->withQueryString();
        // --- AKHIR PERBAIKAN ---

        return view('subscriptions.index', compact('subscriptions', 'request'));
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
        activity()->performedOn($subscription->lead)->causedBy(auth()->user())->log("Memperbarui langganan untuk {$subscription->lead->name}");
        return redirect()->route('subscriptions.index')->with('success', 'Subscription updated successfully.');
    }

    /**
     * Menghapus langganan.
     */
    public function destroy(Subscription $subscription)
    {
        $leadName = $subscription->lead->name;
        $subscription->delete();
        activity()->causedBy(auth()->user())->log('Menghapus langganan untuk ' . $leadName);
        return redirect()->route('subscriptions.index')->with('success', 'Subscription deleted successfully.');
    }
}

