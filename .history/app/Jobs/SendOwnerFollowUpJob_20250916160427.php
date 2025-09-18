// app/Jobs/SendOwnerFollowUpJob.php
namespace App\Jobs;

use App\Models\Lead;
use App\Models\OwnerFollowUpRule;
use App\Models\WahaSender;
use App\Http\Controllers\WahaController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendOwnerFollowUpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ?int $ruleId;
    public function __construct(?int $ruleId = null){ $this->ruleId = $ruleId; }

    public function handle(): void
    {
        $rules = $this->ruleId
            ? OwnerFollowUpRule::query()->active()->whereKey($this->ruleId)->get()
            : OwnerFollowUpRule::query()->active()->get();

        foreach ($rules as $rule) { $this->processRule($rule); }
    }

    /** Dipakai Command untuk dry-run cepat */
    public static function previewEligible(OwnerFollowUpRule $rule, int $cap = 200): array
    {
        return (new static)->eligibleLeadsFor($rule)->take($cap)->pluck('id')->all();
    }

    protected function processRule(OwnerFollowUpRule $rule): void
    {
        $eligible = $this->eligibleLeadsFor($rule);
        if ($eligible->isEmpty()) { $rule->updateQuietly(['last_run_at'=>now()]); return; }

        $sender = $this->resolveSender($rule);
        if (!$sender) { Log::warning("[OwnerFU] Rule #{$rule->id} tidak punya sender aktif."); return; }

        $tpl = $this->resolveTemplateBody($rule);
        $sent=0; $skipped=0; $errors=0;

        foreach ($eligible as $lead) {
            $owner = $lead->owner;
            $ownerNumber = $owner?->wa_number ? $this->digits($owner->wa_number
