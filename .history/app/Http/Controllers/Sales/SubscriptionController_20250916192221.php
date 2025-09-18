<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Notifications\GenericDbNotification;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Subscription::query()->with('lead');

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('cycle'))  $query->where('cycle', $request->cycle);

        if ($request->filled('search')) {
            $term = '%'.$request->search.'%';
            $query->whereHas('lead', fn($q) => $q->where('name','like',$term));
        }

        $sortBy = $request->get('sort_by','created_at');
        $dir    = $request->get('sort_direction','desc');
        $query->orderBy($sortBy, $dir);

        $perPage = (int) $request->input('per_page', 10);
        $subscriptions = $query->paginate($perPage)->withQueryString();

        return view('subscriptions.index', compact('subscriptions','request'));
    }

    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'plan'       => 'required|string|max:255',
            'amount'     => 'required|numeric|min:0',
            'cycle'      => 'required|in:monthly,yearly',
            'status'     => 'required|in:active,paused,cancelled',
            'start_date' => 'required|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $subscription->update($validated);

        activity()->performedOn($subscription->lead)->causedBy($request->user())
            ->log("Memperbarui langganan untuk {$subscription->lead->name}");

        // 📣 notif aktor + owner lead (jika ada)
        $request->user()?->notify(new GenericDbNotification(
            'Subscription Diperbarui',
            "Langganan untuk {$subscription->lead->name} telah diperbarui.",
            route('subscriptions.index')
        ));
        if ($subscription->lead->owner) {
            $subscription->lead->owner->notify(new GenericDbNotification(
                'Langganan Diperbarui',
                "Langganan {$subscription->lead->name} diperbarui.",
                route('leads.show', $subscription->lead)
            ));
        }

        return redirect()->route('subscriptions.index')->with('success', 'Subscription updated successfully.');
    }

    public function destroy(Request $request, Subscription $subscription)
    {
        $leadName = $subscription->lead->name;
        $lead     = $subscription->lead;

        $subscription->delete();

        activity()->causedBy($request->user())->log("Menghapus langganan untuk {$leadName}");

        // 📣 notif
        $request->user()?->notify(new GenericDbNotification(
            'Subscription Dihapus',
            "Langganan untuk {$leadName} telah dihapus.",
            route('subscriptions.index')
        ));
        if ($lead->owner) {
            $lead->owner->notify(new GenericDbNotification(
                'Langganan Dihapus',
                "Langganan {$leadName} dihapus.",
                route('leads.show', $lead)
            ));
        }

        return redirect()->route('subscriptions.index')->with('success', 'Subscription deleted successfully.');
    }
}
