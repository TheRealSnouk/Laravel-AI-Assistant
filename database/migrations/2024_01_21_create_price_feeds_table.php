<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('price_feeds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider');
            $table->string('asset_id');
            $table->string('quote_currency');
            $table->decimal('price', 24, 8);
            $table->decimal('volume_24h', 24, 2)->nullable();
            $table->decimal('market_cap', 24, 2)->nullable();
            $table->decimal('change_24h', 8, 2)->nullable();
            $table->timestamp('last_updated');
            $table->integer('confidence_score')->default(0);
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'asset_id', 'quote_currency']);
            $table->index(['status', 'last_updated']);
        });

        Schema::create('price_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_feed_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 24, 8);
            $table->string('source')->nullable();
            $table->integer('confidence_score');
            $table->timestamp('timestamp');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['price_feed_id', 'timestamp']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('price_updates');
        Schema::dropIfExists('price_feeds');
    }
};
