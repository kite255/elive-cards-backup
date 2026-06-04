<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulk_sms', function (Blueprint $table) {
            if (! Schema::hasColumn('bulk_sms', 'response')) {
                $table->json('response')->nullable()->after('delivery_status');
            }

            if (! Schema::hasColumn('bulk_sms', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('response');
            }

            if (! Schema::hasColumn('bulk_sms', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bulk_sms', function (Blueprint $table) {
            $table->dropColumn([
                'response',
                'sent_at',
                'delivered_at',
            ]);
        });
    }
};