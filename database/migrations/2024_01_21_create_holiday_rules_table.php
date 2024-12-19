<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('holiday_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modifier_id')->constrained('subscription_modifiers')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('country_code', 2);
            $table->json('holiday_types')->nullable();
            $table->json('specific_holidays')->nullable();
            $table->json('excluded_holidays')->nullable();
            $table->decimal('price_multiplier', 8, 4)->nullable();
            $table->decimal('fixed_adjustment', 10, 2)->nullable();
            $table->integer('days_before')->default(0);
            $table->integer('days_after')->default(0);
            $table->integer('priority')->default(0);
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['modifier_id', 'country_code', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('holiday_rules');
    }
};
