<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Lead;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Tampilkan daftar subscription.
     */
    public function index(Request $request)
    {
        $query = Subscription::query()->with(['lead', 'lead.owner']);

        // Filter pencarian (berdasarkan nama/email lead atau plan)
        if ($request->filled('search')) {
            $search = '%' . $request->string('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('plan', 'like', $search)
                  ->orWhereHas('lead', function ($l) use ($search) {
                      $l->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search);
                  });
            });
        }

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        // Filter cycle
        if ($request->filled('cycle')) {
            $query->where('cycle', $request->string('cycle'));
        }

        $subscriptions = $query->latest()->paginate(10)->withQueryString();
        $leads = Lead::orderBy('name')->get();

        return view('subscriptions.index', compact('subscriptions', 'leads'));
    }

    /**
     * Simpan subscription baru (atau update jika untuk lead yang sama sudah ada).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'lead_id'    => 'required|exists:leads,id',
            'plan'       => 'required|string|max:255',
            'amount'     => 'required|numeric|min:0',
            'cycle'      => 'required|in:monthly,yearly',
            'start_date' => 'required|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'status'     => 'nullable|in:active,paused,cancelled',
        ]);

        // Default status ke active jika tidak dikirim
        $data['status'] = $data['status'] ?? 'active';

        // Hindari duplikasi untuk lead yang sama
        $subscription = Subscription::updateOrCreate(
            ['lead_id' => $data['lead_id']],
            $data
        );

        activity()->performedOn($subscription)
            ->causedBy(auth()->user())
            ->log("Membuat/Update subscription untuk lead #{$subscription->lead_id}");

        return redirect()->route('subscriptions.index')
            ->with('success', 'Subscription berhasil disimpan.');
    }

    /**
     * Update subscription.
     */
    public function update(Request $request, Subscription $subscription)
    {
        $data = $request->validate([
            'plan'       => 'required|string|max:255',
            'amount'     => 'required|numeric|min:0',
            'cycle'      => 'required|in:monthly,yearly',
            'start_date' => 'required|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'status'     => 'required|in:active,paused,cancelled',
        ]);

        $subscription->update($data);

        activity()->performedOn($subscription)
            ->causedBy(auth()->user())
            ->log("Memperbarui subscription #{$subscription->id}");

        return redirect()->route('subscriptions.index')
            ->with('success', 'Subscription berhasil diperbarui.');
    }

    /**
     * Hapus subscription.
     */
    public function destroy(Subscription $subscription)
    {
        $id = $subscription->id;
        $subscription->delete();

        activity()->causedBy(auth()->user())
            ->log("Menghapus subscription #{$id}");

        return redirect()->route('subscriptions.index')
            ->with('success', 'Subscription berhasil dihapus.');
    }
}
