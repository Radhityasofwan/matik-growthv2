<div class="space-y-4">
    <div>
        <label for="lead_id" class="label">Lead</label>
        <select id="lead_id" name="lead_id" class="select select-bordered w-full @error('lead_id') select-error @enderror">
            <option disabled selected>Select a lead</option>
            @foreach($leads as $lead)
            <option value="{{ $lead->id }}" {{ old('lead_id', $subscription->lead_id ?? '') == $lead->id ? 'selected' : '' }}>
                {{ $lead->name }} ({{ $lead->email }})
            </option>
            @endforeach
        </select>
        @error('lead_id')
        <div class="text-error text-sm mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div>
        <label for="plan" class="label">Plan</label>
        <input type="text" id="plan" name="plan" placeholder="e.g., Pro, Basic" value="{{ old('plan', $subscription->plan ?? '') }}" class="input input-bordered w-full @error('plan') input-error @enderror">
        @error('plan')
        <div class="text-error text-sm mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div>
        <label for="status" class="label">Status</label>
        <select id="status" name="status" class="select select-bordered w-full @error('status') select-error @enderror">
            <option value="active" {{ old('status', $subscription->status ?? '') == 'active' ? 'selected' : '' }}>Active</option>
            <option value="cancelled" {{ old('status', $subscription->status ?? '') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            <option value="expired" {{ old('status', $subscription->status ?? '') == 'expired' ? 'selected' : '' }}>Expired</option>
        </select>
        @error('status')
        <div class="text-error text-sm mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div>
        <label for="amount" class="label">Amount</label>
        <input type="number" id="amount" name="amount" placeholder="e.g., 99000" value="{{ old('amount', $subscription->amount ?? '') }}" class="input input-bordered w-full @error('amount') input-error @enderror">
        @error('amount')
        <div class="text-error text-sm mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div>
        <label for="cycle" class="label">Cycle</label>
        <select id="cycle" name="cycle" class="select select-bordered w-full @error('cycle') select-error @enderror">
            <option value="monthly" {{ old('cycle', $subscription->cycle ?? '') == 'monthly' ? 'selected' : '' }}>Monthly</option>
            <option value="yearly" {{ old('cycle', $subscription->cycle ?? '') == 'yearly' ? 'selected' : '' }}>Yearly</option>
        </select>
        @error('cycle')
        <div class="text-error text-sm mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div>
        <label for="expires_at" class="label">Expires At</label>
        <input type="date" id="expires_at" name="expires_at" value="{{ old('expires_at', isset($subscription->expires_at) ? $subscription->expires_at->format('Y-m-d') : '') }}" class="input input-bordered w-full @error('expires_at') input-error @enderror">
        @error('expires_at')
        <div class="text-error text-sm mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-control">
        <label class="label cursor-pointer justify-start gap-4">
            <input type="hidden" name="auto_renew" value="0">
            <input type="checkbox" name="auto_renew" value="1" class="checkbox" {{ old('auto_renew', $subscription->auto_renew ?? 0) == 1 ? 'checked' : '' }} />
            <span class="label-text">Auto Renew</span>
        </label>
    </div>

    <div class="flex justify-end gap-4 mt-6">
        <a href="{{ route('subscriptions.index') }}" class="btn">Cancel</a>
        <button type="submit" class="btn btn-primary">{{ $submitText ?? 'Save' }}</button>
    </div>
</div>
