<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WahaController extends Controller
{
    /**
     * Endpoint webhook publik untuk WAHA:
     * Kirim ke route ini dari WAHA (settings → webhook URL).
     * Jangan dibungkus 'auth' middleware.
     */
    public function webhook(Request $request)
    {
        $data = $request->json()->all();

        // Simpan log agar mudah debugging
        Log::info('WAHA Webhook', ['payload' => $data]);

        // Contoh perilaku sesuai snippet kamu:
        // (Kalau event BUKAN 'message', proses payload)
        if (($data['event'] ?? null) !== 'message') {
            $this->processMessage($data['payload'] ?? []);
        }

        // Wajib balas OK cepat
        return response('OK', 200);
    }

    /** Contoh handler sederhana (silakan sesuaikan kebutuhanmu) */
    protected function processMessage(array $payload): void
    {
        // Implementasi bebas: simpan ke DB, trigger notifikasi, dll.
        Log::info('WAHA processMessage()', ['payload' => $payload]);
    }
}
