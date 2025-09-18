<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\User;
use App\Models\WahaSender;
use App\Services\WahaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTaskWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    public int $taskId;

    /** @var string created | status_changed | due_h1 | overdue_h1 | updated */
    public string $event;

    /** @var array */
    public array $meta;

    public function __construct(int $taskId, string $event, array $meta = [])
    {
        $this->taskId = $taskId;
        $this->event  = $event;
        $this->meta   = $meta;
    }

    public function handle(): void
    {
        /** @var Task|null $task */
        $task = Task::with(['assignee','creator'])->find($this->taskId);
        if (!$task) return;

        $sender = $this->resolveSender();
        if (!$sender) {
            activity('task_wa')->performedOn($task)->withProperties([
                'event'  => $this->event,
                'status' => 'SKIPPED_NO_SENDER',
            ])->log('Task WA di-skip (tidak ada WA sender aktif).');
            return;
        }

        // Kumpulkan semua penerima "PIC/owner":
        // - Assignee
        // - Creator (owner pembuat task)
        // - (Opsional) Relasi owners() jika ada di model Task (tidak wajib)
        $recipients = $this->resolveRecipients($task);

        if (empty($recipients)) {
            activity('task_wa')->performedOn($task)->withProperties([
                'event'  => $this->event,
                'status' => 'SKIPPED_NO_RECIPIENT',
            ])->log('Task WA di-skip (tidak ada penerima yang punya wa_number).');
            return;
        }

        $service = app(WahaService::class);
        $message = $this->buildMessage($task, $this->event, $this->meta);

        foreach ($recipients as $user) {
            $raw = (string) ($user->wa_number ?? '');
            $to  = $this->waSanitize($raw);
            if (!$to) {
                activity('task_wa')->performedOn($task)->causedBy($user)->withProperties([
                    'event'  => $this->event,
                    'status' => 'SKIPPED_NO_NUMBER',
                ])->log('Task WA di-skip (user tidak punya wa_number).');
                continue;
            }

            // Kirim via WahaService (fondasi stabil)
            $resp = $service->sendMessage($sender, $to, $message);
            $ok   = is_array($resp) ? ($resp['success'] ?? false) : false;

            activity($this->logNameFor($this->event))
                ->performedOn($task)
                ->causedBy($user)
                ->withProperties([
                    'event'       => $this->event,
                    'sender_id'   => $sender->id,
                    'recipient_id'=> $user->id,
                    'number'      => $to,
                    'status'      => $ok ? 'OK' : 'FAILED',
                    'http'        => $resp['http'] ?? null,
                    'path'        => $resp['path'] ?? null,
                    'message_id'  => $resp['message_id'] ?? null,
                ])->log($ok ? 'Task WA terkirim' : 'Task WA gagal');
        }
    }

    protected function logNameFor(string $event): string
    {
        return match ($event) {
            'created'        => 'task_wa_created',
            'status_changed' => 'task_wa_status',
            'due_h1'         => 'task_wa_due',
            'overdue_h1'     => 'task_wa_overdue',
            'updated'        => 'task_wa_updated',
            default          => 'task_wa',
        };
    }

    protected function buildMessage(Task $task, string $event, array $meta): string
    {
        $title   = trim((string)$task->title);
        $prio    = strtoupper((string)($task->priority ?? '-'));
        $due     = optional($task->due_date)->timezone(config('app.timezone'))->format('d M Y H:i');
        $creator = $task->creator?->name ?: 'System';
        $url     = route('tasks.index');

        return match ($event) {
            'created' => "📌 Tugas Baru\n"
                       . "Judul: {$title}\n"
                       . "Prioritas: {$prio}\n"
                       . "Due: ".($due ?: '-')."\n"
                       . "Dari: {$creator}\n"
                       . "Link: {$url}",

            'status_changed' => "🔄 Status Tugas Diperbarui\n"
                       . "Judul: {$title}\n"
                       . "Status: ".($meta['from'] ?? '-')." → ".($meta['to'] ?? '-')."\n"
                       . "Prioritas: {$prio}\n"
                       . "Link: {$url}",

            'updated' => "✏️ Tugas Diperbarui\n"
                       . "Judul: {$title}\n"
                       . "Prioritas: {$prio}\n"
                       . "Due: ".($due ?: '-')."\n"
                       . "Link: {$url}",

            'due_h1' => "⏰ Pengingat H-1\n"
                       . "Besok batas waktu tugas: {$title}\n"
                       . "Prioritas: {$prio}\n"
                       . "Due: ".($due ?: '-')."\n"
                       . "Link: {$url}",

            'overdue_h1' => "⚠️ Peringatan Keras (H+1)\n"
                       . "Tugas melewati deadline: {$title}\n"
                       . "Prioritas: {$prio}\n"
                       . "Due: ".($due ?: '-')."\n"
                       . "Mohon ditindaklanjuti.\n"
                       . "Link: {$url}",

            default => "Tugas: {$title}\nLink: {$url}",
        };
    }

    /**
     * Pilih WA sender aktif dengan preferensi default.
     */
    protected function resolveSender(): ?WahaSender
    {
        return WahaSender::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    /**
     * Kumpulkan daftar user penerima (PIC/owner) unik.
     * - Assignee
     * - Creator
     * - owners() jika relasi tersedia (opsional)
     *
     * @return User[]
     */
    protected function resolveRecipients(Task $task): array
    {
        $list = [];

        if ($task->assignee instanceof User) {
            $list[$task->assignee->id] = $task->assignee;
        }
        if ($task->creator instanceof User) {
            $list[$task->creator->id] = $task->creator;
        }

        // Dukungan opsional untuk relasi owners() (jika ada di model Task)
        try {
            if (method_exists($task, 'owners')) {
                $owners = $task->owners()->get();
                foreach ($owners as $owner) {
                    if ($owner instanceof User) {
                        $list[$owner->id] = $owner;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Abaikan jika tidak ada relasi/terjadi error non-fatal
        }

        return array_values($list);
    }

    protected function waSanitize(?string $p): ?string
    {
        if (!$p) return null;
        $n = preg_replace('/\D+/', '', $p);
        return $n ?: null;
    }
}
