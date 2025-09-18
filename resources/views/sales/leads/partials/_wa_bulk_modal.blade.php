{{-- Bulk WhatsApp Modal (via WAHA) --}}
<div id="bulk_whatsapp_modal" class="modal">
  <div class="modal-box w-11/12 max-w-2xl">
    <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
    <h3 class="font-bold text-lg">Kirim WhatsApp Massal</h3>
    <p class="py-2 text-sm text-gray-500">
      Pesan akan dikirim ke <strong><span id="bulk-selected-count-modal">0</span></strong> lead terpilih.
      Gunakan placeholder <code>@{{name}}</code>.
    </p>

    @if($wahaSenders->isEmpty())
      <div class="alert alert-warning my-3">Belum ada sender aktif. Tambahkan sender terlebih dulu di menu WhatsApp.</div>
    @endif

    <div class="mt-4 space-y-4">
      <div>
        <label class="label"><span class="label-text">Kirim Dari</span></label>
        <select id="bulk-wa-sender-selector" class="select select-bordered w-full" {{ $wahaSenders->isEmpty() ? 'disabled' : '' }}>
          <option value="">-- Pilih Nomor Pengirim --</option>
          @foreach ($wahaSenders as $sender)
            <option value="{{ $sender->id }}">{{ $sender->name }} ({{ $sender->number }})</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="label"><span class="label-text">Template Pesan</span></label>
        <select id="bulk-wa-template-selector" class="select select-bordered w-full">
          <option value="">-- Pilih template --</option>
          @foreach ($whatsappTemplates as $template)
            <option value="{{ $template->id }}" data-body="{{ e($template->body) }}">{{ $template->name }}</option>
          @endforeach
        </select>
      </div>
      <textarea id="bulk-wa-message-preview" class="textarea textarea-bordered w-full h-32" placeholder="Pratinjau pesan massal..."></textarea>
    </div>

    <div class="modal-action mt-6">
      <a href="#" class="btn btn-ghost">Batal</a>
      <button id="bulk-wa-send-button" class="btn btn-success" disabled>Kirim</button>
    </div>
  </div>
  <a href="#" class="modal-backdrop">Close</a>
</div>
