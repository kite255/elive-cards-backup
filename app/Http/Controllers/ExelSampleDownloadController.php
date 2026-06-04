<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

use Response;
class ExelSampleDownloadController extends Controller
{
    public function downloadExcelSample()
    {
        $filePath = storage_path('app/public/sampleExel/Ecardz-Excel-Sample.xlsx');

        // Check if the file exists before trying to serve it
        if (file_exists($filePath)) {
            return response()->download($filePath);
        } else {
            return response()->json(['error' => 'File not found.'], 404);
        }
    }
    public function bulksmsdownloadExcelSample()
    {
        $filePath = storage_path('app/public/sampleExel/Ecardz-bulk-sms-excel-sample.xlsx');

        // Check if the file exists before trying to serve it
        if (file_exists($filePath)) {
            return response()->download($filePath);
        } else {

           Alert::error('error','try again later or contact support');
           return redirect()->back();
        }
    }
}
