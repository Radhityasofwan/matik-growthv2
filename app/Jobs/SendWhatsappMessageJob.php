<?php

namespace App\Jobs;

use App\Services\WahaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsappMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Jumlah percobaan ulang jika job gagal.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * Waktu (detik) untuk menunggu sebelum mencoba ulang job.
     *
     * @var int
     */
    public int $backoff = 60;

    protected string $number;
    protected string $text;
    protected string $session;

    /**
     * Create a new job instance.
     *
     * @param string $number Nomor tujuan
     * @param string $text Isi pesan
     * @param string $session Sesi/sender yang digunakan
     */
    public function __construct(string $number, string $text, string $session)
    {
        $this->number = $number;
        $this->text = $text;
        $this->session = $session;
    }

    /**
     * Execute the job.
     *
     * @param WahaService $wahaService
     * @return void
     */
    public function handle(WahaService $wahaService): void
    {
        // Tambahkan jeda acak antara 1-5 detik untuk menghindari rate limiting
        sleep(rand(1, 5));

        $wahaService->sendText(
            $this->number,
            $this->text,
            $this->session
        );
    }
}
