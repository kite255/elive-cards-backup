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
        Schema::create('event_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('event_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->string('card_name');
            $table->string('guestPositionX');
            $table->string('guestPositionY');
            $table->string('statusPositionX');
            $table->string('statusPositionY');
            $table->string('qrcodePositionX');
            $table->string('qrcodePositionY');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_cards');
    }
};
