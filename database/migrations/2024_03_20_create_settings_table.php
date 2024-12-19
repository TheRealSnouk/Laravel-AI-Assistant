<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('api_provider')->default('openrouter'); // openrouter, anthropic, openai, etc.
            $table->text('api_key')->nullable();
            $table->string('selected_model')->default('anthropic/claude-2');
            $table->boolean('use_default_key')->default(true);
            $table->string('session_id')->unique(); // To track settings per session
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
