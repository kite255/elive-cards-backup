<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('send_whatsapp_cards', function (Blueprint $table) {
            if (! Schema::hasColumn('send_whatsapp_cards', 'error_code')) {
                $table->string('error_code')->nullable()->after('reply_message');
            }

            if (! Schema::hasColumn('send_whatsapp_cards', 'error_message')) {
                $table->text('error_message')->nullable()->after('error_code');
            }

            if (! Schema::hasColumn('send_whatsapp_cards', 'reply_received_at')) {
                $table->timestamp('reply_received_at')->nullable()->after('error_message');
            }

            if (! Schema::hasColumn('send_whatsapp_cards', 'rsvp_status')) {
                $table->string('rsvp_status')->nullable()->after('reply_received_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('send_whatsapp_cards', function (Blueprint $table) {
            if (Schema::hasColumn('send_whatsapp_cards', 'rsvp_status')) {
                $table->dropColumn('rsvp_status');
            }

            if (Schema::hasColumn('send_whatsapp_cards', 'reply_received_at')) {
                $table->dropColumn('reply_received_at');
            }

            if (Schema::hasColumn('send_whatsapp_cards', 'error_message')) {
                $table->dropColumn('error_message');
            }

            if (Schema::hasColumn('send_whatsapp_cards', 'error_code')) {
                $table->dropColumn('error_code');
            }
        });
    }
};