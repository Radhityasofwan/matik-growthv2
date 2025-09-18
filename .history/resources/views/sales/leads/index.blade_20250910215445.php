@extends('layouts.app')

@section('title', 'Leads - Matik Growth Hub')

@section('content')
<div class="container mx-auto px-6 py-8">

    <!-- Alerts -->
    @if (session('success'))
        <div class="alert alert-success shadow-lg mb-6">
            <div>
                <svg xmlns="http://www.w.3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error shadow-lg mb-6">
            <div>
                <svg xmlns="http://www.w.3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span><strong>Terdapat kesalahan!</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></span>
            </div>
        </div>
    @endif

    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-medium">Leads</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Track and manage your potential customers.</p>
        </div>
        <a href="#create_lead_modal" class="btn btn-primary mt-4 sm:mt-0">Add Lead</a>
    </div>

    <!-- Filters -->
    <div class="mt-6">
        <form action="{{ route('leads.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="text" name="search" placeholder="Search by name or email..." value="{{ request('search') }}" class="input input-bordered w-full">
            <select name="status" class="select select-bordered w-full">
                <option value="">All Statuses</option>
                <option value="trial" @selected(request('status') == 'trial')>Trial</option>
                <option value="active" @selected(request('status') == 'active')>Active</option>
                <option value="converted" @selected(request('status') == 'converted')>Converted</option>
                <option value="churn" @selected(request('status') == 'churn')>Churn</option>
            </select>
            <button type="submit" class="btn btn-secondary w-full md:w-auto">Filter</button>
        </form>
    </div>

    @if($leads->isEmpty() && !request()->query())
        <!-- Empty State -->
        <div class="text-center py-20">
             <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No leads yet</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by adding your first lead.</p>
            <div class="mt-6">
                <a href="#create_lead_modal" class="btn btn-primary">New Lead</a>
            </div>
        </div>
    @else
        <!-- Quick Actions Bar -->
        <div id="quick-actions-bar" class="hidden bg-gray-100 dark:bg-gray-700 border dark:border-gray-600 px-4 py-2 rounded-lg my-4 flex items-center justify-between transition-all duration-300">
            <div><span id="selected-count" class="font-bold">0</span> leads selected.</div>
            <div>
                <a href="#bulk_whatsapp_modal" id="bulk-whatsapp-trigger" class="btn btn-sm btn-success">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-whatsapp mr-2" viewBox="0 0 16 16"><path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/></svg>Send Bulk WhatsApp</a>
            </div>
        </div>

        <!-- Leads Table -->
        <div class="mt-2 overflow-x-auto">
            <div class="inline-block min-w-full shadow-md rounded-lg overflow-hidden">
                <table class="min-w-full leading-normal">
                     <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600"><input type="checkbox" id="select-all-checkbox" class="checkbox checkbox-sm" /></th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Name</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Status</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Owner</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Created At</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800">
                        @forelse ($leads as $lead)
                        <tr id="lead-row-{{ $lead->id }}" data-phone="{{ $lead->phone }}" data-name="{{ $lead->name }}">
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700"><input type="checkbox" class="checkbox checkbox-sm lead-checkbox" value="{{ $lead->id }}" data-phone="{{ $lead->phone }}" data-name="{{ $lead->name }}"></td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                <p class="text-gray-900 dark:text-white font-semibold">{{ $lead->name }}</p>
                                <p class="text-gray-600 dark:text-gray-400">{{ $lead->email }}</p>
                                <p class="text-gray-500 dark:text-gray-500 mt-1">{{ $lead->phone ?? 'No phone number' }}</p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm"><span class="badge @switch($lead->status) @case('trial') badge-info @break @case('active') badge-success @break @case('converted') badge-accent @break @case('churn') badge-error @break @endswitch">{{ ucfirst($lead->status) }}</span></td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">{{ $lead->owner?->name ?? 'Unassigned' }}</td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">{{ $lead->created_at->format('M d, Y') }}</td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm text-right">
                                <div class="flex items-center justify-end space-x-3">
                                    @if($lead->phone)<a href="#whatsapp_modal" class="text-green-500 hover:text-green-700" onclick="openWhatsAppModal({{ $lead->id }})"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16"><path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/></svg></a>@endif
                                    <a href="#edit_lead_modal_{{ $lead->id }}" class="text-indigo-600 hover:text-indigo-900" onclick="prepareEditModal({{ $lead->id }})">Edit</a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-gray-500">No leads found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-5 py-5 bg-white dark:bg-gray-800 border-t">{{ $leads->withQueryString()->links() }}</div>
            </div>
        </div>
    @endif
</div>

<!-- Modals -->

<!-- Create Lead Modal -->
<div id="create_lead_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <form action="{{ route('leads.store') }}" method="POST">
            @csrf
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg">Add New Lead</h3>
            <div class="mt-4 space-y-4">
                <div><label class="label"><span class="label-text">Name</span></label><input type="text" name="name" placeholder="Enter full name" value="{{ old('name') }}" class="input input-bordered w-full" required /></div>
                <div><label class="label"><span class="label-text">Email</span></label><input type="email" name="email" placeholder="Enter email" value="{{ old('email') }}" class="input input-bordered w-full" required /></div>
                <div><label class="label"><span class="label-text">WhatsApp Number</span></label><input type="text" name="phone" placeholder="e.g., 6281234567890" value="{{ old('phone') }}" class="input input-bordered w-full" /></div>
                <div><label class="label"><span class="label-text">Status</span></label><select name="status" class="select select-bordered w-full" required><option value="trial">Trial</option> <option value="active">Active</option> <option value="converted">Converted</option> <option value="churn">Churn</option></select></div>
                <div><label class="label"><span class="label-text">Owner</span></label><select name="owner_id" class="select select-bordered w-full" required>@forelse($users as $user) <option value="{{ $user->id }}">{{ $user->name }}</option> @empty <option disabled>No users</option> @endforelse</select></div>
            </div>
            <div class="modal-action mt-6"><a href="#" class="btn btn-ghost">Cancel</a><button type="submit" class="btn btn-primary">Save Lead</button></div>
        </form>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>

<!-- Edit Lead Modals -->
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
                    @foreach($users as $user) <option value="{{ $user->id }}" @selected(old('owner_id', $lead->owner_id) == $user->id)>{{ $user->name }}</option> @endforeach
                </select>

                <!-- Subscription Form - Hidden by default -->
                <div id="subscription_form_{{ $lead->id }}" class="hidden mt-6 pt-4 border-t border-gray-200 dark:border-gray-600 space-y-4">
                    <h4 class="font-semibold text-md">Subscription Details</h4>
                    <div><label class="label"><span class="label-text">Plan Name</span></label><input type="text" name="plan" placeholder="e.g., Premium" value="{{ old('plan', $lead->subscription->plan ?? '') }}" class="input input-bordered w-full" /></div>
                    <div><label class="label"><span class="label-text">Amount (Rp)</span></label><input type="number" name="amount" placeholder="e.g., 150000" value="{{ old('amount', $lead->subscription->amount ?? '') }}" class="input input-bordered w-full" /></div>
                    <div><label class="label"><span class="label-text">Billing Cycle</span></label><select name="cycle" class="select select-bordered w-full"><option value="monthly" @selected(old('cycle', $lead->subscription->cycle ?? '') == 'monthly')>Monthly</option><option value="yearly" @selected(old('cycle', $lead->subscription->cycle ?? '') == 'yearly')>Yearly</option></select></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="label"><span class="label-text">Start Date</span></label><input type="date" name="start_date" value="{{ old('start_date', $lead->subscription->start_date ?? now()->format('Y-m-d')) }}" class="input input-bordered w-full" /></div>
                        <div><label class="label"><span class="label-text">End Date (Optional)</span></label><input type="date" name="end_date" value="{{ old('end_date', $lead->subscription->end_date ?? '') }}" class="input input-bordered w-full" /></div>
                    </div>
                </div>
            </div>
            <div class="modal-action mt-6"><a href="#" class="btn btn-ghost">Cancel</a><button type="submit" class="btn btn-primary">Update Lead</button></div>
        </form>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>
@endforeach

<!-- Single WhatsApp Template Modal -->
<div id="whatsapp_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
        <h3 class="font-bold text-lg">Choose WhatsApp Template</h3>
        <p class="py-2 text-sm text-gray-500">Select a message to send to <strong id="wa-lead-name"></strong>.</p>
        <div class="mt-4 space-y-2">
            <select id="wa-template-selector" class="select select-bordered w-full">
                <option disabled selected>-- Select a template --</option>
                @foreach ($whatsappTemplates as $template)
                    <option value="{{ $template->body }}">{{ $template->name }}</option>
                @endforeach
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
        <p class="py-2 text-sm text-gray-500">This message will be sent to the selected leads. Use the @{{name}} placeholder.</p>
        <div class="mt-4 space-y-2">
            <select id="bulk-wa-template-selector" class="select select-bordered w-full">
                <option disabled selected>-- Select a template --</option>
                 @foreach ($whatsappTemplates as $template)
                    <option value="{{ $template->body }}">{{ $template->name }}</option>
                @endforeach
            </select>
            <textarea id="bulk-wa-message-preview" class="textarea textarea-bordered w-full h-32" placeholder="Message preview with placeholder @{{name}} will appear here..."></textarea>
        </div>
        <div class="modal-action mt-6">
            <a href="#" class="btn btn-ghost">Cancel</a>
            <button id="bulk-wa-send-button" class="btn btn-success btn-disabled">Send to Selected (<span id="bulk-selected-count">0</span>)</button>
        </div>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Subscription Form Toggle ---
    function toggleSubscriptionForm(leadId, status) {
        const form = document.getElementById(`subscription_form_${leadId}`);
        if (form) {
            form.classList.toggle('hidden', status !== 'converted');
        }
    }

    window.prepareEditModal = function(leadId) {
        const statusSelector = document.querySelector(`#edit_lead_modal_${leadId} .status-selector`);
        if(statusSelector) {
            toggleSubscriptionForm(leadId, statusSelector.value);
        }
    }

    document.querySelectorAll('.status-selector').forEach(selector => {
        selector.addEventListener('change', function() {
            toggleSubscriptionForm(this.dataset.leadId, this.value);
        });
    });

    // --- Single WA Modal ---
    let currentLeadId = null;
    const waTemplateSelector = document.getElementById('wa-template-selector');
    const waMessagePreview = document.getElementById('wa-message-preview');
    const waSendButton = document.getElementById('wa-send-button');

    window.openWhatsAppModal = function(leadId) {
        currentLeadId = leadId;
        const leadRow = document.getElementById(`lead-row-${leadId}`);
        document.getElementById('wa-lead-name').textContent = leadRow.dataset.name;
        waTemplateSelector.selectedIndex = 0;
        waMessagePreview.value = '';
        waSendButton.classList.add('btn-disabled');
    }

    waTemplateSelector.addEventListener('change', function() {
        if (!this.value) return;
        const leadRow = document.getElementById(`lead-row-${currentLeadId}`);
        const leadName = leadRow.dataset.name;
        const leadPhone = leadRow.dataset.phone;
        const finalMessage = this.value.replace(/\{\{name\}\}/g, leadName).replace(/\{\{nama_pelanggan\}\}/g, leadName);
        waMessagePreview.value = finalMessage;
        waSendButton.href = `https://wa.me/${leadPhone}?text=${encodeURIComponent(finalMessage)}`;
        waSendButton.classList.remove('btn-disabled');
    });

    // --- Bulk Actions ---
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const leadCheckboxes = document.querySelectorAll('.lead-checkbox');
    const quickActionsBar = document.getElementById('quick-actions-bar');
    const selectedCountSpan = document.getElementById('selected-count');
    const bulkWaTemplateSelector = document.getElementById('bulk-wa-template-selector');
    const bulkWaMessagePreview = document.getElementById('bulk-wa-message-preview');
    const bulkWaSendButton = document.getElementById('bulk-wa-send-button');
    const bulkSelectedCount = document.getElementById('bulk-selected-count');

    function updateQuickActions() {
        const selected = document.querySelectorAll('.lead-checkbox:checked');
        selectedCountSpan.textContent = selected.length;
        quickActionsBar.classList.toggle('hidden', selected.length === 0);
    }

    selectAllCheckbox.addEventListener('change', function() {
        leadCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateQuickActions();
    });

    leadCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateQuickActions);
    });

    bulkWaTemplateSelector.addEventListener('change', function() {
        bulkWaMessagePreview.value = this.value;
        const selected = document.querySelectorAll('.lead-checkbox:checked');
        bulkWaSendButton.classList.toggle('btn-disabled', !this.value || selected.length === 0);
    });

    document.getElementById('bulk-whatsapp-trigger').addEventListener('click', () => {
        const selected = document.querySelectorAll('.lead-checkbox:checked');
        bulkSelectedCount.textContent = selected.length;
        bulkWaTemplateSelector.selectedIndex = 0;
        bulkWaMessagePreview.value = '';
        bulkWaSendButton.classList.add('btn-disabled');
    });

    bulkWaSendButton.addEventListener('click', function() {
        const selectedCheckboxes = document.querySelectorAll('.lead-checkbox:checked');
        const template = bulkWaTemplateSelector.value;
        if (!template || this.classList.contains('btn-disabled')) return;

        selectedCheckboxes.forEach((checkbox, index) => {
            const name = checkbox.dataset.name;
            const phone = checkbox.dataset.phone;
            if (phone) {
                const message = template.replace(/\{\{name\}\}/g, name).replace(/\{\{nama_pelanggan\}\}/g, name);
                const url = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
                // Use a small delay to prevent popup blockers
                setTimeout(() => window.open(url, '_blank'), index * 300);
            }
        });
    });
});
</script>
@endpush

