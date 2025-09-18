<?php

namespace App\Jobs;

use App\Http\Controllers\WahaController;
use App\Models\Task;
use App\Models\WahaSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SendTaskWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $taskId;
    public string $event; // created | status_changed | due_h1 | overdue_h1
    public array $meta;

    public function __construct(int $taskId, string $event, array $meta = [])
    {
        $this->taskId = $taskId;
        $this->event  = $event;
        $this->meta   = $meta;
    }

    public function handle(): void
    {
        /** @var Task $task */
        $task = Task::with(['assignee','creator'])->find($this->taskId);
        if (!$task || !$task->assignee) return;

        $assignee = $task->assignee;
        $raw      = (string) ($assignee->wa_number ?? '');
        $to       = $this->waSanitize($raw);

        if (!$to) {
            activity('task_wa')->performedOn($task)->causedBy($assignee)->withProperties([
                'event'  => $this->event,
                'status' => 'SKIPPED_NO_NUMBER',
            ])->log('Task WA di-skip (assignee tidak punya wa_number).');
            return;
        }

        $sender = $this->resolveSender();
        if (!$sender) {
            activity('task_wa')->performedOn($task)->causedBy($assignee)->withProperties([
                'event'  => $this->event,
                'status' => 'SKIPPED_NO_SENDER',
            ])->log('Task WA di-skip (tidak ada WA sender aktif).');
            return;
        }

        $message = $this->buildMessage($task, $this->event, $this->meta);

        // Kirim via WahaController (tanpa menyentuh WahaService pondasi)
        $req  = Request::create('/_internal/task-notify', 'POST', [
            'sender_id' => $sender->id,
            'recipient' => $to,
            'message'   => $message,
        ]);
        $resp = app()->call([app(WahaController::class), 'sendMessage'], ['request' => $req]);

        $http  = method_exists($resp, 'getStatusCode') ? $resp->getStatusCode() : 200;
        $payload = [];
        if (method_exists($resp, 'getData')) {
            $payload = (array) $resp->getData(true);
        } elseif (method_exists($resp, 'getContent')) {
            $json = json_decode($resp->getContent(), true);
            if (is_array($json)) $payload = $json;
        }

        activity($this->logNameFor($this->event))
            ->performedOn($task)
            ->causedBy($assignee)
            ->withProperties([
                'event'      => $this->event,
                'sender_id'  => $sender->id,
                'assignee_id'=> $assignee->id,
                'number'     => $to,
                'http'       => $http,
                'status'     => $payload['status'] ?? ($http >=200 && $http<300 ? 'OK' : 'FAILED'),
                'message_id' => $payload['message_id'] ?? null,
            ])->log('Task WA terkirim');
    }

    protected function logNameFor(string $event): string
    {
        return match ($event) {
            'created'        => 'task_wa_created',
            'status_changed' => 'task_wa_status',
            'due_h1'         => 'task_wa_due',
            'overdue_h1'     => 'task_wa_overdue',
            default          => 'task_wa',
        };
    }

    protected function buildMessage(Task $task, string $event, array $meta): string
    {
        $title   = trim((string)$task->title);
        $prio    = strtoupper((string)$task->priority ?: '-');
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

    protected function waSanitize(?string $p): ?string
    {
        if (!$p) return null;
        $n = preg_replace('/\D+/', '', $p);
        return $n ?: null;
    }
}
