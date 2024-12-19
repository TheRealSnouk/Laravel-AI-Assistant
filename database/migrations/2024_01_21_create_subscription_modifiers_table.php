<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('subscription_modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['addon', 'discount']);
            $table->string('stripe_price_id')->nullable();
            $table->string('stripe_coupon_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->enum('billing_type', ['one_time', 'recurring']);
            $table->string('status');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'type', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscription_modifiers');
    }
};
