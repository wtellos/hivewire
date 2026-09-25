<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory;

    public function assignees() {
        return $this->belongsToMany(User::class, 'task_assignee');
    }

    public function followers() {
        return $this->belongsToMany(User::class, 'task_follower');
    }    
}
