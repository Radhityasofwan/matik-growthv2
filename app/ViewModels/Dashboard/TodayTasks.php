<?php

namespace App\ViewModels\Dashboard;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TodayTasks
{
    public Collection $tasks;

    public function __construct(User $user)
    {
        $this->tasks = Task::where('assignee_id', $user->id)
            ->whereIn('status', ['open', 'in_progress'])
            ->whereDate('due_date', today())
            ->orderBy('priority', 'desc')
            ->get();
    }
}
