<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('event_categories_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->string('name');      
            $table->string('place');
            $table->date('date');
            $table->string('contact_name');
            $table->string('contact_phone');
            $table->string('email');
            $table->string('code');
            $table->string('payment_status')->default('not paid');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('events');
    }
};
