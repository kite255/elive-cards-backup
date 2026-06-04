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
        Schema::create('send_whatsapp_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade')->onUpdate('cascade');   
            $table->foreignId('event_guests_id')->constrained()->onDelete('cascade')->onUpdate('cascade');  
            $table->foreignId('guest_pdf_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->string('whatsapp_sender_id')->nullable();
            $table->string('message_id')->nullable();
            $table->string('sent_status')->nullable();
            $table->timestamp('delivery_status_time')->nullable();
            $table->string('delivery_status')->nullable();
            $table->string('reply_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('send_whatsapp_cards');
    }
};
