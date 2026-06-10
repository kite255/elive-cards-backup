<?php

namespace App\Http\Controllers;

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ExelSampleDownloadController extends Controller
{
    /**
     * Download invitees Excel sample.
     */
    public function downloadExcelSample()
    {
        $filePath = storage_path('app/public/sampleExel/eLive-Card-Sample.xlsx');

        if (file_exists($filePath)) {
            return response()->download($filePath, 'eLive-Card-Sample.xlsx');
        }

        return Excel::download(
            new class implements FromArray, WithHeadings, ShouldAutoSize {
                public function headings(): array
                {
                    return [
                        'name',
                        'phone',
                        'card_type',
                        'category',
                        'table_number',
                    ];
                }

                public function array(): array
                {
                    return [
                        ['Guest 1', '255768461644', 'SINGLE', 'Harusi', 'A1'],
                        ['Guest 2', '255713789845', 'DOUBLE', 'Harusi', 'A2'],
                        ['Guest 3', '255676089011', 'FAMILY', 'Harusi', 'A3'],
                    ];
                }
            },
            'eLive-Card-Sample.xlsx'
        );
    }

    /**
     * Download bulk SMS Excel sample.
     */
    public function bulksmsdownloadExcelSample()
    {
        $filePath = storage_path('app/public/sampleExel/eLive-Card-Bulk-SMS-Sample.xlsx');

        if (file_exists($filePath)) {
            return response()->download($filePath, 'eLive-Card-Bulk-SMS-Sample.xlsx');
        }

        return Excel::download(
            new class implements FromArray, WithHeadings, ShouldAutoSize {
                public function headings(): array
                {
                    return [
                        'name',
                        'phone',
                        'message',
                    ];
                }

                public function array(): array
                {
                    return [
                        ['Guest 1', '255768461644', 'Habari Guest 1, karibu kwenye tukio letu.'],
                        ['Guest 2', '255713789845', 'Habari Guest 2, tunakukumbusha kuhudhuria tukio.'],
                        ['Guest 3', '255676089011', 'Habari Guest 3, asante kwa ushiriki wako.'],
                    ];
                }
            },
            'eLive-Card-Bulk-SMS-Sample.xlsx'
        );
    }
}