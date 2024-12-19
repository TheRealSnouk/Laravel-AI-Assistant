<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('modifier_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modifier_id')->constrained('subscription_modifiers')->onDelete('cascade');
            $table->string('rule_type');
            $table->string('target_type');
            $table->string('target_id');
            $table->json('condition')->nullable();
            $table->integer('priority')->default(0);
            $table->string('action');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['modifier_id', 'rule_type', 'target_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('modifier_rules');
    }
};
