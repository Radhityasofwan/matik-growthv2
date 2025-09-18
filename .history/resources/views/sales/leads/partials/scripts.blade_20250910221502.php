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
        waMessage-preview.value = '';
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
