<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('time_based_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modifier_id')->constrained('subscription_modifiers')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('rule_type');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->json('days_of_week')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('timezone')->default('UTC');
            $table->decimal('price_multiplier', 8, 4)->nullable();
            $table->decimal('fixed_adjustment', 10, 2)->nullable();
            $table->integer('priority')->default(0);
            $table->json('conditions')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['modifier_id', 'rule_type', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('time_based_rules');
    }
};
