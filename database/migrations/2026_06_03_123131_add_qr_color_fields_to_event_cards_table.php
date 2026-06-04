<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_cards', function (Blueprint $table) {
            if (! Schema::hasColumn('event_cards', 'qrCodeForegroundColor')) {
                $table->string('qrCodeForegroundColor', 20)->default('#000000')->after('qrCodeSizePercent');
            }

            if (! Schema::hasColumn('event_cards', 'qrCodeBackgroundColor')) {
                $table->string('qrCodeBackgroundColor', 20)->default('#ffffff')->after('qrCodeForegroundColor');
            }

            if (! Schema::hasColumn('event_cards', 'qrCodeEyeColor')) {
                $table->string('qrCodeEyeColor', 20)->default('#000000')->after('qrCodeBackgroundColor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('event_cards', function (Blueprint $table) {
            foreach ([
                'qrCodeForegroundColor',
                'qrCodeBackgroundColor',
                'qrCodeEyeColor',
            ] as $column) {
                if (Schema::hasColumn('event_cards', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};