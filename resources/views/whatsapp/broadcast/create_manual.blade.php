<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manual WhatsApp Broadcast') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">

                    <!-- Session Messages -->
                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 text-green-700 border border-green-200 rounded-md">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="mb-4 p-4 bg-red-100 text-red-700 border border-red-200 rounded-md">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('whatsapp.broadcast.store_manual') }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <!-- Left Column: Form Inputs -->
                            <div>
                                <!-- Sender Session -->
                                <div class="mb-4">
                                    <label for="session" class="block text-sm font-medium text-gray-700">Pilih Nomor Sender</label>
                                    <select name="session" id="session" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="">-- Pilih Sesi --</option>
                                        @foreach($senders as $sender)
                                            <option value="{{ $sender->session_name }}" {{ old('session') == $sender->session_name ? 'selected' : '' }}>{{ $sender->display_name }}</option>
                                        @endforeach
                                    </select>
                                    @error('session')
                                        <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- CSV Data -->
                                <div class="mb-4">
                                    <label for="csv_data" class="block text-sm font-medium text-gray-700">Paste Data dari Excel/Sheets</label>
                                    <textarea name="csv_data" id="csv_data" rows="10" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="Paste data yang disalin dari Excel atau Google Sheets di sini.">{{ old('csv_data') }}</textarea>
                                    @error('csv_data')
                                        <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Message Template -->
                                <div>
                                    <label for="message_template" class="block text-sm font-medium text-gray-700">Template Pesan</label>
                                    <textarea name="message_template" id="message_template" rows="8" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="Contoh: Halo #1#, kami dari Matik. Penawaran spesial untuk Anda...">{{ old('message_template') }}</textarea>
                                    @error('message_template')
                                        <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Right Column: Instructions -->
                            <div>
                                <div class="bg-blue-50 border border-blue-200 p-4 rounded-md mb-4">
                                    <h3 class="text-lg font-semibold text-blue-800">Cara Penulisan Pesan</h3>
                                    <p class="text-blue-700 mt-2">
                                        Gunakan format <strong>#index_kolom#</strong> dalam pesan untuk mengambil nilai dari data yang Anda paste.
                                        Index dimulai dari <strong>#0#</strong>.
                                    </p>
                                    <p class="mt-2">
                                        <strong>Contoh:</strong> Jika kolom kedua (index 1) berisi nama, Anda bisa menulis:
                                        <br>
                                        <code class="text-sm bg-blue-100 p-1 rounded">Halo #1#, apa kabar?</code>
                                    </p>
                                    <p class="mt-2">
                                        <strong>Penting:</strong> Kolom nomor telepon diasumsikan berada di kolom ketiga (diakses dengan <strong>#2#</strong>).
                                    </p>
                                </div>

                                <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-md">
                                    <h3 class="text-lg font-semibold text-yellow-800">Tips Mengirim Broadcast</h3>
                                    <ul class="list-disc list-inside mt-2 text-yellow-700 space-y-1">
                                        <li>Gunakan jeda waktu antar pengiriman besar.</li>
                                        <li>Gunakan variasi pesan agar tidak dianggap spam.</li>
                                        <li>Pastikan nomor sender aktif dan punya reputasi baik.</li>
                                        <li>Buat isi pesan yang memancing balasan.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                                Kirim Broadcast ke Antrian
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
