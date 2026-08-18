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
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            // $table->string('status');
            $table->enum('status', [
                'draft',
                'active',
                'completed',
                'archived',
            ]);
            $table->foreignId('owner_id')->constrained('users');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'status']);
            $table->index(['status', 'due_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
