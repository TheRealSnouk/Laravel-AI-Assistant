<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('crypto_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('reference')->unique();
            $table->string('network');
            $table->string('token_id');
            $table->decimal('amount', 20, 8);
            $table->string('currency');
            $table->string('recipient_address');
            $table->string('sender_address')->nullable();
            $table->string('transaction_hash')->nullable();
            $table->string('memo')->nullable();
            $table->string('status')->default('pending');
            $table->json('payment_details')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
            $table->index('reference');
            $table->index('transaction_hash');
        });
    }

    public function down()
    {
        Schema::dropIfExists('crypto_payments');
    }
};
