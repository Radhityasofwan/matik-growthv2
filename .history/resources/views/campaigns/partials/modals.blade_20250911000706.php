{{-- resources/views/campaigns/partials/modals.blade.php --}}
@php
    // Default-kan variabel agar aman dipakai di halaman mana pun
    /** @var \Illuminate\Support\Collection|\App\Models\Lead[] $leads */
    $leads = $leads ?? collect();

    /** @var \Illuminate\Support\Collection|\App\Models\User[] $users */
    $users = $users ?? collect();

    /** @var \Illuminate\Support\Collection|\App\Models\WATemplate[] $whatsappTemplates */
    $whatsappTemplates = $whatsappTemplates ?? collect();
@endphp

<!-- Create Lead Modal -->
<div id="create_lead_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <form action="{{ route('leads.store') }}" method="POST">
            @csrf
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg">Add New Lead</h3>

            <div class="mt-4 space-y-4">
                <div>
                    <label class="label"><span class="label-text">Name</span></label>
                    <input type="text" name="name" placeholder="Enter full name" value="{{ old('name') }}" class="input input-bordered w-full" required />
                </div>

                <div>
                    <label class="label"><span class="label-text">Email</span></label>
                    <input type="email" name="email" placeholder="Enter email" value="{{ old('email') }}" class="input input-bordered w-full" required />
                </div>

                <div>
                    <label class="label"><span class="label-text">WhatsApp Number</span></label>
                    <input type="text" name="phone" placeholder="e.g., 6281234567890" value="{{ old('phone') }}" class="input input-bordered w-full" />
                </div>

                <div>
                    <label class="label"><span class="label-text">Status</span></label>
                    <select name="status" class="select select-bordered w-full" required>
                        <option value="trial">Trial</option>
                        <option value="active">Active</option>
                        <option value="converted">Converted</option>
                        <option value="churn">Churn</option>
                    </select>
                </div>

                <div>
                    <label class="label"><span class="label-text">Owner</span></label>
                    <select name="owner_id" class="select select-bordered w-full" required>
                        @forelse($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @empty
                            <option disabled>No users</option>
                        @endforelse
                    </select>
                </div>
            </div>

            <div class="modal-action mt-6">
                <a href="#" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Lead</button>
            </div>
        </form>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>

<!-- Edit Lead Modals -->
@if($leads->isNotEmpty())
    @foreach ($leads as $lead)
        <div id="edit_lead_modal_{{ $lead->id }}" class="modal">
            <div class="modal-box w-11/12 max-w-2xl">
                <form action="{{ route('leads.update', $lead) }}" method="POST">
                    @csrf @method('PATCH')
                    <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
                    <h3 class="font-bold text-lg">Edit Lead: {{ $lead->name }}</h3>

                    <div class="mt-4 space-y-4">
                        <!-- Lead Details -->
                        <input type="text" name="name" value="{{ old('name', $lead->name) }}" class="input input-bordered w-full" required />
                        <input type="email" name="email" value="{{ old('email', $lead->email) }}" class="input input-bordered w-full" required />
                        <input type="text" name="phone" placeholder="e.g., 6281234567890" value="{{ old('phone', $lead->phone) }}" class="input input-bordered w-full" />

                        <select name="status" class="select select-bordered w-full status-selector" data-lead-id="{{ $lead->id }}" required>
                            <option value="trial" @selected(old('status', $lead->status) == 'trial')>Trial</option>
                            <option value="active" @selected(old('status', $lead->status) == 'active')>Active</option>
                            <option value="converted" @selected(old('status', $lead->status) == 'converted')>Converted</option>
                            <option value="churn" @selected(old('status', $lead->status) == 'churn')>Churn</option>
                        </select>

                        <select name="owner_id" class="select select-bordered w-full" required>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(old('owner_id', $lead->owner_id) == $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>

                        <!-- Subscription Form - Hidden by default -->
                        <div id="subscription_form_{{ $lead->id }}" class="hidden mt-6 pt-4 border-t border-gray-200 dark:border-gray-600 space-y-4">
                            <h4 class="font-semibold text-md">Subscription Details</h4>

                            <div>
                                <label class="label"><span class="label-text">Plan Name</span></label>
                                <input type="text" name="plan" placeholder="e.g., Premium" value="{{ old('plan', $lead->subscription->plan ?? '') }}" class="input input-bordered w-full" />
                            </div>

                            <div>
                                <label class="label"><span class="label-text">Amount (Rp)</span></label>
                                <input type="number" name="amount" placeholder="e.g., 150000" value="{{ old('amount', $lead->subscription->amount ?? '') }}" class="input input-bordered w-full" />
                            </div>

                            <div>
                                <label class="label"><span class="label-text">Billing Cycle</span></label>
                                <select name="cycle" class="select select-bordered w-full">
                                    <option value="monthly" @selected(old('cycle', $lead->subscription->cycle ?? '') == 'monthly')>Monthly</option>
                                    <option value="yearly" @selected(old('cycle', $lead->subscription->cycle ?? '') == 'yearly')>Yearly</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="label"><span class="label-text">Start Date</span></label>
                                    <input type="date" name="start_date" value="{{ old('start_date', optional($lead->subscription->start_date ?? null)->format('Y-m-d') ?? now()->format('Y-m-d')) }}" class="input input-bordered w-full" />
                                </div>
                                <div>
                                    <label class="label"><span class="label-text">End Date (Optional)</span></label>
                                    <input type="date" name="end_date" value="{{ old('end_date', optional($lead->subscription->end_date ?? null)->format('Y-m-d')) }}" class="input input-bordered w-full" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-action mt-6">
                        <a href="#" class="btn btn-ghost">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Lead</button>
                    </div>
                </form>
            </div>
            <a href="#" class="modal-backdrop">Close</a>
        </div>
    @endforeach
@endif

<!-- Single WhatsApp Template Modal -->
<div id="whatsapp_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
        <h3 class="font-bold text-lg">Choose WhatsApp Template</h3>
        <p class="py-2 text-sm text-gray-500">
            Select a message to send to <strong id="wa-lead-name"></strong>.
        </p>

        <div class="mt-4 space-y-2">
            <select id="wa-template-selector" class="select select-bordered w-full">
                <option disabled selected>-- Select a template --</option>
                @forelse ($whatsappTemplates as $template)
                    <option value="{{ $template->body }}">{{ $template->name }}</option>
                @empty
                    <option disabled>No WhatsApp templates</option>
                @endforelse
            </select>

            <textarea id="wa-message-preview" class="textarea textarea-bordered w-full h-32" placeholder="Message preview will appear here..."></textarea>
        </div>

        <div class="modal-action mt-6">
            <a href="#" class="btn btn-ghost">Cancel</a>
            <a id="wa-send-button" href="#" target="_blank" class="btn btn-success btn-disabled">Send via WhatsApp</a>
        </div>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>

<!-- Bulk WhatsApp Template Modal -->
<div id="bulk_whatsapp_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
        <h3 class="font-bold text-lg">Choose Bulk WhatsApp Template</h3>
        <p class="py-2 text-sm text-gray-500">
            This message will be sent to the selected leads. Use the @{{name}} placeholder.
        </p>

        <div class="mt-4 space-y-2">
            <select id="bulk-wa-template-selector" class="select select-bordered w-full">
                <option disabled selected>-- Select a template --</option>
                @forelse ($whatsappTemplates as $template)
                    <option value="{{ $template->body }}">{{ $template->name }}</option>
                @empty
                    <option disabled>No WhatsApp templates</option>
                @endforelse
            </select>

            <textarea id="bulk-wa-message-preview" class="textarea textarea-bordered w-full h-32" placeholder="Message preview with placeholder @{{name}} will appear here..."></textarea>
        </div>

        <div class="modal-action mt-6">
            <a href="#" class="btn btn-ghost">Cancel</a>
            <button id="bulk-wa-send-button" class="btn btn-success btn-disabled">
                Send to Selected (<span id="bulk-selected-count">0</span>)
            </button>
        </div>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>
