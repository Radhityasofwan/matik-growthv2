<?php

namespace App\Jobs;

use App\Models\LeadFollowUpRule;
use App\Models\Lead;
use App\Models\WahaSender;
use App\Services\WahaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SendLeadFollowUpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $rules = LeadFollowUpRule::active()->get();
        if ($rules->isEmpty()) {
            Log::info('SendLeadFollowUpJob: Tidak ada rule aktif.');
            return;
        }

        foreach ($rules as $rule) {
            $targets = $this->queryTargets($rule)->chunkById(200, function ($chunk) use ($rule) {
                foreach ($chunk as $lead) {
                    // safety: nomor wajib
                    $phone = preg_replace('/\D+/', '', (string) ($lead->phone ?? ''));
                    if ($phone === '') continue;

                    // templating
                    $message = $this->renderMessage($rule, $lead);
                    if (trim($message) === '') continue;

                    // pilih sender (rule->sender, lalu default active)
                    $sender = $rule->sender ?: WahaSender::query()
                        ->where('is_active', true)
                        ->orderByDesc('is_default')
                        ->orderBy('id')->first();

                    if (!$sender) {
                        Log::warning("SendLeadFollowUpJob: Tidak ada WAHA sender aktif untuk Rule #{$rule->id}");
                        continue;
                    }

                    try {
                        app(WahaService::class)->sendMessage($sender, $phone, $message);

                        // activity log → memudahkan timeline
                        activity('wa_follow_up_auto')
                            ->performedOn($lead)
                            ->withProperties([
                                'rule_id'   => $rule->id,
                                'condition' => $rule->condition,
                                'days_after'=> $rule->days_after,
                                'sender_id' => $sender->id,
                                'phone'     => $phone,
                            ])->log('Auto follow-up sent');

                        Log::info("Auto follow-up terkirim. Lead #{$lead->id} ({$phone}) via Rule #{$rule->id}");
                    } catch (\Throwable $e) {
                        Log::error("Auto follow-up gagal. Lead #{$lead->id}: {$e->getMessage()}");
                    }
                }
            });

            // cap waktu terakhir rule dieksekusi
            $rule->update(['last_run_at' => now()]);
        }
    }

    /**
     * Query kandidat lead sesuai rule & kondisi.
     * Memakai agregasi ringan agar tetap stabil.
     */
    private function queryTargets(LeadFollowUpRule $rule): Builder
    {
        $now = now();
        $cutNoChat = $now->copy()->subDays($rule->days_after);
        $base = Lead::query()->whereNotNull('phone');

        if ($rule->lead_id) {
            $base->where('id', $rule->lead_id);
        }

        switch ($rule->condition) {
            case 'no_chat':
                // Belum pernah ada aktivitas chat, dan umur lead >= days_after
                return $base
                    ->whereDoesntHave('activities', function ($q) {
                        $q->where('log_name', 'wa_chat');
                    })
                    ->where('created_at', '<=', $cutNoChat);

            case 'chat_1_no_reply':
            case 'chat_2_no_reply':
            case 'chat_3_no_reply':
                $n = (int) str_replace(['chat_','_no_reply'], '', $rule->condition); // 1/2/3

                // Ambil hitungan chat & timestamp chat/reply terakhir sebagai kolom terhitung
                return $base
                    ->withCount(['activities as wa_chat_count' => function ($q) {
                        $q->where('log_name', 'wa_chat');
                    }])
                    ->withMax(['activities as last_wa_chat_at' => function ($q) {
                        $q->where('log_name', 'wa_chat');
                    }], 'created_at')
                    ->withMax(['activities as last_reply_at' => function ($q) {
                        $q->where('log_name', 'lead_reply');
                    }], 'created_at')
                    ->where('wa_chat_count', '=', $n)
                    // belum ada balasan setelah chat terakhir
                    ->where(function ($q) {
                        $q->whereNull('last_reply_at')
                          ->orWhereColumn('last_reply_at', '<', 'last_wa_chat_at');
                    })
                    // last chat sudah lebih lama dari days_after
                    ->where('last_wa_chat_at', '<=', $now->copy()->subDays($rule->days_after));

            default:
                // fallback aman: tidak memproses apa-apa
                return $base->whereRaw('1=0');
        }
    }

    private function renderMessage(LeadFollowUpRule $rule, Lead $lead): string
    {
        $tpl = optional($rule->template)->body ?? 'Halo {{name}}, kami ingin follow-up terkait trial/layanan Anda.';

        $repl = [
            '{{name}}'         => (string) ($lead->name ?? ''),
            '{{store_name}}'   => (string) ($lead->store_name ?? ''),
            '{{trial_ends_at}}'=> optional($lead->trial_ends_at)->format('d M Y') ?? '',
            '{{email}}'        => (string) ($lead->email ?? ''),
            '{{phone}}'        => (string) ($lead->phone ?? ''),
        ];

        return strtr($tpl, $repl);
    }
}
