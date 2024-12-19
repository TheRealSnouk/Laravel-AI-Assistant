<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('session_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->enum('tier', ['free', 'basic', 'pro'])->default('free');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');
            $table->integer('api_calls_limit');
            $table->integer('api_calls_used')->default(0);
            $table->timestamps();

            $table->index(['session_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('session_subscriptions');
    }
};
