<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('status', ['backlog', 'to_do', 'in_progress', 'in_review', 'done'])
                ->default('backlog');
            $table->foreignId('creator_id')->constrained('users');
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->integer('progress')->min(0)->max(100);
            $table->date('start_date');
            $table->date('end_date');
            $table->text('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
