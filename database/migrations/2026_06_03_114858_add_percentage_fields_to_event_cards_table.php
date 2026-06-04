<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_cards', function (Blueprint $table) {
            if (! Schema::hasColumn('event_cards', 'guestPositionXPercent')) {
                $table->decimal('guestPositionXPercent', 8, 4)->nullable()->after('guestPositionY');
            }

            if (! Schema::hasColumn('event_cards', 'guestPositionYPercent')) {
                $table->decimal('guestPositionYPercent', 8, 4)->nullable()->after('guestPositionXPercent');
            }

            if (! Schema::hasColumn('event_cards', 'guestFontSizePercent')) {
                $table->decimal('guestFontSizePercent', 8, 4)->nullable()->after('guestPositionYPercent');
            }

            if (! Schema::hasColumn('event_cards', 'cardTypePositionXPercent')) {
                $table->decimal('cardTypePositionXPercent', 8, 4)->nullable()->after('guestFontSizePercent');
            }

            if (! Schema::hasColumn('event_cards', 'cardTypePositionYPercent')) {
                $table->decimal('cardTypePositionYPercent', 8, 4)->nullable()->after('cardTypePositionXPercent');
            }

            if (! Schema::hasColumn('event_cards', 'cardTypeFontSizePercent')) {
                $table->decimal('cardTypeFontSizePercent', 8, 4)->nullable()->after('cardTypePositionYPercent');
            }

            if (! Schema::hasColumn('event_cards', 'cardTypeWidthPercent')) {
                $table->decimal('cardTypeWidthPercent', 8, 4)->nullable()->after('cardTypeFontSizePercent');
            }

            if (! Schema::hasColumn('event_cards', 'cardTypeHeightPercent')) {
                $table->decimal('cardTypeHeightPercent', 8, 4)->nullable()->after('cardTypeWidthPercent');
            }

            if (! Schema::hasColumn('event_cards', 'qrCodePositionXPercent')) {
                $table->decimal('qrCodePositionXPercent', 8, 4)->nullable()->after('cardTypeHeightPercent');
            }

            if (! Schema::hasColumn('event_cards', 'qrCodePositionYPercent')) {
                $table->decimal('qrCodePositionYPercent', 8, 4)->nullable()->after('qrCodePositionXPercent');
            }

            if (! Schema::hasColumn('event_cards', 'qrCodeSizePercent')) {
                $table->decimal('qrCodeSizePercent', 8, 4)->nullable()->after('qrCodePositionYPercent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('event_cards', function (Blueprint $table) {
            $columns = [
                'guestPositionXPercent',
                'guestPositionYPercent',
                'guestFontSizePercent',
                'cardTypePositionXPercent',
                'cardTypePositionYPercent',
                'cardTypeFontSizePercent',
                'cardTypeWidthPercent',
                'cardTypeHeightPercent',
                'qrCodePositionXPercent',
                'qrCodePositionYPercent',
                'qrCodeSizePercent',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('event_cards', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};