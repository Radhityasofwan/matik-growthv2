<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Models\LeadFollowUpRule;
use App\Models\User;
use App\Models\WATemplate;
use App\Models\WahaSender;
use App\Http\Controllers\WahaController;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Proses 1 rule follow-up (by id) dan kirim pesan ke lead yang match.
 * Sekaligus mengirim notifikasi internal ke owner (jika ada wa_number).
 *
 * Catatan:
 * - Tetap bebas dari middleware/auth; jangan panggil route, langsung panggil controller method.
 * - Activity log:
 *     - log_name: "follow_up" (ke lead)  --> properties: rule_id, sender_id, number, http, status, text
 *     - log_name: "follow_up_notify_owner" (ke owner) --> properties mirip, + owner_id
 */
class SendLeadFollowUpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int|null */
    public int|null $ruleId;

    public function __construct(?int $ruleId = null)
    {
        $this->ruleId = $ruleId;
        // antri pada queue bawaan; bisa diubah via ->onQueue('…') saat dispatch
    }

    public function handle(): void
    {
        // Ambil rules: jika ruleId null -> semua rule aktif
        $rules = $this->ruleId
            ? LeadFollowUpRule::query()->active()->whereKey($this->ruleId)->get()
            : LeadFollowUpRule::query()->active()->get();

        if ($rules->isEmpty()) {
            Log::info('[FollowUp] Tidak ada rule aktif.');
            return;
        }

        foreach ($rules as $rule) {
            $this->processRule($rule);
        }
    }

    /* ============================================================
     |                          CORE
     * ============================================================ */

    protected function processRule(LeadFollowUpRule $rule): void
    {
        // tentukan anchor dan filter lead yang eligible untuk kondisi rule
        $eligible = $this->eligibleLeadsFor($rule);

        if ($eligible->isEmpty()) {
            $rule->updateQuietly(['last_run_at' => now()]);
            Log::info("[FollowUp] Rule #{$rule->id} ({$rule->condition}) tidak ada target.");
            return;
        }

        $sender = $this->resolveSender($rule);
        if (!$sender) {
            Log::warning("[FollowUp] Rule #{$rule->id} tidak punya sender aktif. Skip.");
            return;
        }

        $templateText = $this->resolveTemplateBody($rule);
        $sent = 0; $errors = 0;

        foreach ($eligible as $lead) {
            try {
                $to = $this->waSanitize($lead->phone);
                if (!$to) {
                    // tidak punya nomor, skip & catat
                    activity('follow_up')
                        ->performedOn($lead)
                        ->withProperties([
                            'rule_id'    => $rule->id,
                            'sender_id'  => $sender->id,
                            'status'     => 'SKIPPED_NO_PHONE',
                        ])->log('Follow-up di-skip (tanpa nomor).');
                    continue;
                }

                // susun pesan untuk LEAD
                $messageToLead = $this->fillTemplate($templateText, $lead);

                // kirim ke LEAD
                $resultLead = $this->sendViaWaha($sender->id, $to, $messageToLead);

                activity('follow_up')
                    ->performedOn($lead)
                    ->withProperties([
                        'rule_id'    => $rule->id,
                        'sender_id'  => $sender->id,
                        'number'     => $to,
                        'http'       => data_get($resultLead, 'http'),
                        'status'     => data_get($resultLead, 'result.status') ?? data_get($resultLead, 'status'),
                        'text'       => Str::limit($messageToLead, 500),
                    ])->log('WA follow-up terkirim');

                $sent++;

                // === Notifikasi internal ke OWNER (opsional & aman) ===
                $this->notifyOwner($rule, $lead, $sender, $messageToLead, $resultLead);
            } catch (\Throwable $e) {
                Log::error('[FollowUp] Gagal kirim: '.$e->getMessage(), ['lead_id' => $lead->id, 'rule_id' => $rule->id]);
                $errors++;
                activity('follow_up')
                    ->performedOn($lead)
                    ->withProperties([
                        'rule_id'    => $rule->id,
                        'sender_id'  => $sender->id,
                        'error'      => $e->getMessage(),
                    ])->log('WA follow-up gagal');
            }
        }

        $rule->updateQuietly(['last_run_at' => now()]);

        Log::info("[FollowUp] Rule #{$rule->id} selesai. sent=$sent errors=$errors targets={$eligible->count()}");
    }

    /**
     * Kirim pemberitahuan ke owner->wa_number jika tersedia.
     * – Pakai sender yang sama.
     * – Lewati jika: owner null, wa_number kosong, sama dengan nomor lead (hindari spam ke nomor yang sama),
     *   atau jika sebelumnya error kirim ke lead dan kita ingin minimalkan noise (tetap kirim info error singkat).
     */
    protected function notifyOwner(LeadFollowUpRule $rule, Lead $lead, WahaSender $sender, string $messageToLead, array $resultLead): void
    {
        $owner = $lead->owner;
        $ownerNumber = $owner?->wa_number ? $this->waSanitize($owner->wa_number) : null;

        if (!$ownerNumber) {
            // fallback: tidak ada notif
            activity('follow_up_notify_owner')
                ->performedOn($lead)
                ->withProperties([
                    'rule_id'    => $rule->id,
                    'sender_id'  => $sender->id,
                    'owner_id'   => $owner?->id,
                    'status'     => 'SKIPPED_NO_OWNER_NUMBER',
                ])->log('Notifikasi owner di-skip (owner tidak isi WA).');
            return;
        }

        // Hindari kalau nomor owner sama persis dengan nomor lead
        $leadNumber = $this->waSanitize($lead->phone);
        if ($leadNumber && $leadNumber === $ownerNumber) {
            activity('follow_up_notify_owner')
                ->performedOn($lead)
                ->withProperties([
                    'rule_id'    => $rule->id,
                    'sender_id'  => $sender->id,
                    'owner_id'   => $owner?->id,
                    'status'     => 'SKIPPED_SAME_NUMBER',
                ])->log('Notifikasi owner di-skip (nomor sama dengan lead).');
            return;
        }

        // Susun ringkas pesan owner
        $statusLead = data_get($resultLead, 'result.status') ?? data_get($resultLead, 'status') ?? 'UNKNOWN';
        $httpLead   = data_get($resultLead, 'http');

        $ruleLabel = match ($rule->condition) {
            'no_chat'         => 'Belum di-chat',
            'chat_1_no_reply' => 'Chat 1x (belum balas)',
            'chat_2_no_reply' => 'Chat 2x (belum balas)',
            'chat_3_no_reply' => 'Chat 3x (belum balas)',
            default           => Str::headline($rule->condition)
        };

        $detailUrl = route('leads.show', $lead); // supaya PIC bisa klik cepat

        $ownerMsg  = "Notifikasi Follow-up 📣\n".
                     "Lead: {$lead->name} ({$leadNumber})\n".
                     "Rule: {$ruleLabel}\n".
                     "Status: {$statusLead}".($httpLead ? " (HTTP {$httpLead})" : '')."\n".
                     "Pengirim: {$sender->name} ({$sender->number})\n".
                     "Link: {$detailUrl}";

        try {
            $resultOwner = $this->sendViaWaha($sender->id, $ownerNumber, $ownerMsg);

            activity('follow_up_notify_owner')
                ->performedOn($lead)
                ->causedBy($owner) // tampilkan nama owner di timeline
                ->withProperties([
                    'rule_id'    => $rule->id,
                    'sender_id'  => $sender->id,
                    'owner_id'   => $owner->id,
                    'number'     => $ownerNumber,
                    'http'       => data_get($resultOwner, 'http'),
                    'status'     => data_get($resultOwner, 'result.status') ?? data_get($resultOwner, 'status'),
                ])->log('Notifikasi owner terkirim');
        } catch (\Throwable $e) {
            activity('follow_up_notify_owner')
                ->performedOn($lead)
                ->causedBy($owner)
                ->withProperties([
                    'rule_id'    => $rule->id,
                    'sender_id'  => $sender->id,
                    'owner_id'   => $owner?->id,
                    'error'      => $e->getMessage(),
                ])->log('Notifikasi owner gagal');
        }
    }

    /* ============================================================
     |                    SELEKSI TARGET PER RULE
     * ============================================================ */

    /**
     * Ambil leads yang memenuhi kondisi dan jatuh tempo (days_after) untuk rule.
     * Menggunakan data activity_log untuk hitung chat_count/last_* tanpa menambah kolom DB.
     */
    protected function eligibleLeadsFor(LeadFollowUpRule $rule)
    {
        $days  = (int) $rule->days_after;
        $since = now()->copy()->subDays($days)->startOfDay(); // anchor batas minimal

        // subquery hitung & waktu terakhir chat / balasan
        $sub = Lead::query()
            ->select('leads.*')
            ->selectSub(function ($q) {
                $q->from('activity_log')
                  ->selectRaw('COUNT(*)')
                  ->whereColumn('activity_log.subject_id', 'leads.id')
                  ->where('activity_log.subject_type', Lead::class)
                  ->where('activity_log.log_name', 'wa_chat');
            }, 'chat_count')
            ->selectSub(function ($q) {
                $q->from('activity_log')
                  ->selectRaw('MAX(created_at)')
                  ->whereColumn('activity_log.subject_id', 'leads.id')
                  ->where('activity_log.subject_type', Lead::class)
                  ->where('activity_log.log_name', 'wa_chat');
            }, 'last_wa_chat_at')
            ->selectSub(function ($q) {
                $q->from('activity_log')
                  ->selectRaw('MAX(created_at)')
                  ->whereColumn('activity_log.subject_id', 'leads.id')
                  ->where('activity_log.subject_type', Lead::class)
                  ->whereIn('activity_log.log_name', ['wa_reply','wa_incoming']);
            }, 'last_reply_at');

        // scope by rule.lead_id (khusus) atau semua lead
        if ($rule->lead_id) {
            $sub->where('leads.id', $rule->lead_id);
        }

        // hanya yang ada nomor telepon
        $sub->whereNotNull('leads.phone');

        // apply kondisi
        $rows = DB::query()->fromSub($sub, 'L');

        switch ($rule->condition) {
            case 'no_chat':
                $rows->where('chat_count', '=', 0)
                     // anchor pakai created_at
                     ->where('L.created_at', '<=', $since);
                break;

            case 'chat_1_no_reply':
                $rows->where('chat_count', '=', 1)
                     ->where(function ($q) {
                         $q->whereNull('last_reply_at')
                           ->orWhereColumn('last_reply_at', '<', 'last_wa_chat_at');
                     })
                     ->where('last_wa_chat_at', '<=', $since);
                break;

            case 'chat_2_no_reply':
                $rows->where('chat_count', '=', 2)
                     ->where(function ($q) {
                         $q->whereNull('last_reply_at')
                           ->orWhereColumn('last_reply_at', '<', 'last_wa_chat_at');
                     })
                     ->where('last_wa_chat_at', '<=', $since);
                break;

            case 'chat_3_no_reply':
                $rows->where('chat_count', '=', 3)
                     ->where(function ($q) {
                         $q->whereNull('last_reply_at')
                           ->orWhereColumn('last_reply_at', '<', 'last_wa_chat_at');
                     })
                     ->where('last_wa_chat_at', '<=', $since);
                break;

            default:
                // tidak dikenal -> kosong
                $rows->whereRaw('1=0');
        }

        // batasi batch agar aman (mis. 200 per run)
        $leads = $rows->orderBy('L.id')->limit(200)->get()->map(function ($r) {
            // casting baris ke model Lead
            $lead = new Lead((array) $r);
            $lead->exists = true;
            $lead->id     = (int) $r->id;
            return $lead;
        });

        return $leads;
    }

    /* ============================================================
     |                  UTIL: SENDER, TEMPLATE, WA
     * ============================================================ */

    protected function resolveSender(LeadFollowUpRule $rule): ?WahaSender
    {
        if ($rule->sender) {
            return $rule->sender;
        }
        return WahaSender::query()
            ->where('is_active', true)
            ->orderByDesc('is_default') // kalau ada kolom ini, akan prioritas; kalau tidak, diabaikan DB
            ->orderBy('id')
            ->first();
    }

    protected function resolveTemplateBody(LeadFollowUpRule $rule): string
    {
        $body = '';
        if ($rule->template) {
            $body = (string) $rule->template->body;
        } else {
            // fallback body sederhana
            $body = "Halo {{name}}, saya {{owner_name}} dari {{company}}. "
                  . "Kami ingin follow-up terkait kebutuhan Anda. "
                  . "Balas pesan ini ya 🙂";
        }
        return $body;
    }

    protected function fillTemplate(string $tpl, Lead $lead): string
    {
        $owner   = $lead->owner;
        $map = [
            '{{name}}'           => $lead->name ?? ($lead->store_name ?: 'Kak'),
            '{{nama}}'           => $lead->name ?? ($lead->store_name ?: 'Kak'),
            '{{store_name}}'     => $lead->store_name ?? '-',
            '{{trial_ends_at}}'  => optional($lead->trial_ends_at)->format('d M Y') ?? '-',
            '{{owner_name}}'     => $owner?->name ?? 'Tim',
            '{{company}}'        => $lead->company ?? ($lead->store_name ?? 'Perusahaan kami'),
        ];
        // dukung @{{var}} di template Blade/JS
        $withAt = [];
        foreach ($map as $k => $v) { $withAt['@'.$k] = $v; }

        return strtr($tpl, $withAt + $map);
    }

    protected function waSanitize(?string $p): ?string
    {
        if (!$p) return null;
        $n = preg_replace('/\D+/', '', $p);
        return $n ?: null;
    }

    /**
     * Kirim lewat controller WAHA internal (tanpa route).
     * Return array ringkas: ['http'=>int, 'status'=>..., 'result'=>...]
     */
    protected function sendViaWaha(int $senderId, string $recipient, string $message): array
    {
        $req = Request::create('/_internal/followup', 'POST', [
            'sender_id' => $senderId,
            'recipient' => $recipient,
            'message'   => $message,
        ]);

        /** @var \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response $resp */
        $resp = app(WahaController::class)->sendMessage($req);

        // Normalisasi
        if (method_exists($resp, 'getStatusCode')) {
            $http = $resp->getStatusCode();
        } else {
            $http = 200;
        }

        $payload = [];
        if (method_exists($resp, 'getData')) {
            $payload = (array) $resp->getData(true);
        } elseif (method_exists($resp, 'getContent')) {
            $json = json_decode($resp->getContent(), true);
            if (is_array($json)) $payload = $json;
        }

        return ['http' => $http] + $payload;
    }
}
