<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Campaign Name</label>
        <input type="text" name="name" id="name" value="{{ old('name', $campaign->name) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white">
    </div>
    <div>
        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Status</label>
        <select name="status" id="status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white">
            <option value="planning" @selected(old('status', $campaign->status) == 'planning')>Planning</option>
            <option value="active" @selected(old('status', $campaign->status) == 'active')>Active</option>
            <option value="completed" @selected(old('status', $campaign->status) == 'completed')>Completed</option>
            <option value="on_hold" @selected(old('status', $campaign->status) == 'on_hold')>On Hold</option>
        </select>
    </div>
</div>

<div class="mt-4">
    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Description</label>
    <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white">{{ old('description', $campaign->description) }}</textarea>
</div>

<div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Start Date</label>
        <input type="date" name="start_date" id="start_date" value="{{ old('start_date', $campaign->start_date?->format('Y-m-d')) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white">
    </div>
    <div>
        <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-200">End Date</label>
        <input type="date" name="end_date" id="end_date" value="{{ old('end_date', $campaign->end_date?->format('Y-m-d')) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white">
    </div>
</div>

<div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label for="budget" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Budget ($)</label>
        <input type="number" step="0.01" name="budget" id="budget" value="{{ old('budget', $campaign->budget) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white">
    </div>
     @if ($campaign->exists)
        <div>
            <label for="revenue" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Revenue ($)</label>
            <input type="number" step="0.01" name="revenue" id="revenue" value="{{ old('revenue', $campaign->revenue) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white">
        </div>
    @endif
</div>

<div class="mt-4">
    <label for="channel" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Channel</label>
    <select name="channel" id="channel" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white">
        <option value="WA" @selected(old('channel', $campaign->channel) == 'WA')>WhatsApp</option>
        <option value="Ads" @selected(old('channel', $campaign->channel) == 'Ads')>Ads</option>
        <option value="Content" @selected(old('channel', $campaign->channel) == 'Content')>Content</option>
    </select>
</div>
