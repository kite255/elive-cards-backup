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
        Schema::create('send_message_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('event_guests_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->string('request_id');
            $table->string('sent_status');
            $table->string('delivery_status')->nullable();
            $table->text('reminder_message');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('send_message_reminders');
    }
};
