<?php

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'status',
        'progress',
        'project_id',
        'creator_id',
        'start_date',
        'end_date',
        'description',
    ];

    /**
     * Get the project of this task: $task->project()->where('task', 'done')->get();
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who created this task: $task->creator()->where('role', 'admin')->first();
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get the users assigned to this task: $task->assignees()->where('role', 'admin')->get();
     */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_assignees');
    }

    /**
     * Get the users following this task.
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_followers');
    }
}
