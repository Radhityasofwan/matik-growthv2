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

    public int $taskId;
    /** created | status_changed | updated | due_h1 | overdue_h1 */
    public string $event;
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

        // Tentukan penerima: assignee, creator, owners(), owner_ids
        $recipients = $this->resolveRecipients($task);
        if (empty($recipients)) {
            activity('task_wa')->performedOn($task)->withProperties([
                'event'  => $this->event,
                'status' => 'SKIPPED_NO_RECIPIENT',
            ])->log('Task WA di-skip (tidak ada penerima).');
            return;
        }

        $service = app(WahaService::class);
        $message = $this->buildMessage($task, $this->event, $this->meta);

        foreach ($recipients as $user) {
            $number = $this->waSanitize((string)($user->wa_number ?? ''));
            if (!$number) {
                activity('task_wa')->performedOn($task)->causedBy($user)->withProperties([
                    'event'  => $this->event,
                    'status' => 'SKIPPED_NO_NUMBER',
                ])->log('Task WA di-skip (user tanpa wa_number).');
                continue;
            }

            $resp = $service->sendMessage($sender, $number, $message);
            $ok   = is_array($resp) ? ($resp['success'] ?? false) : false;

            activity($this->logNameFor($this->event))
                ->performedOn($task)
                ->causedBy($user)
                ->withProperties([
                    'event'        => $this->event,
                    'sender_id'    => $sender->id,
                    'recipient_id' => $user->id,
                    'number'       => $number,
                    'status'       => $ok ? 'OK' : 'FAILED',
                    'http'         => $resp['http'] ?? null,
                    'path'         => $resp['path'] ?? null,
                    'message_id'   => $resp['message_id'] ?? null,
                ])->log($ok ? 'Task WA terkirim' : 'Task WA gagal');
        }
    }

    protected function logNameFor(string $event): string
    {
        return match ($event) {
            'created'        => 'task_wa_created',
            'status_changed' => 'task_wa_status',
            'updated'        => 'task_wa_updated',
            'due_h1'         => 'task_wa_due',
            'overdue_h1'     => 'task_wa_overdue',
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

    protected function resolveSender(): ?WahaSender
    {
        return WahaSender::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    /**
     * Kumpulkan penerima dari berbagai sumber:
     * - assignee
     * - creator
     * - owners() relasi (jika tersedia)
     * - owner_ids (array/json) jika atribut tersedia
     *
     * @return User[]
     */
    protected function resolveRecipients(Task $task): array
    {
        $map = [];

        if ($task->assignee instanceof User) {
            $map[$task->assignee->id] = $task->assignee;
        }
        if ($task->creator instanceof User) {
            $map[$task->creator->id] = $task->creator;
        }

        // owners() relasi opsional
        try {
            if (method_exists($task, 'owners')) {
                foreach ($task->owners()->get() as $owner) {
                    if ($owner instanceof User) $map[$owner->id] = $owner;
                }
            }
        } catch (\Throwable $e) {}

        // owner_ids (atribut opsional: array/json id user)
        try {
            if (isset($task->owner_ids)) {
                $ids = $task->owner_ids;
                if (is_string($ids)) {
                    $decoded = json_decode($ids, true);
                    if (is_array($decoded)) $ids = $decoded;
                }
                if (is_array($ids) && !empty($ids)) {
                    foreach (User::whereIn('id', $ids)->get() as $u) {
                        $map[$u->id] = $u;
                    }
                }
            }
        } catch (\Throwable $e) {}

        return array_values($map);
    }

    protected function waSanitize(?string $p): ?string
    {
        if (!$p) return null;
        $n = preg_replace('/\D+/', '', $p);
        return $n ?: null;
    }
}
