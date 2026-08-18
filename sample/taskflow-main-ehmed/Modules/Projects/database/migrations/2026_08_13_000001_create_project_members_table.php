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
        Schema::create('project_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->foreignId('user_id')->constrained('users');
            $table->string('member_role');
            $table->timestamp('joined_at');
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_members');
    }
};
