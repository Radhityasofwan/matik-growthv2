<?php

namespace App\Services\Reports;

use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ReportGenerator
{
    /**
     * Generate a PDF report from a Blade view.
     */
    public function generatePdf(string $view, array $data, string $filename)
    {
        $pdf = Pdf::loadView($view, $data);
        return $pdf->download($filename);
    }

    /**
     * Generate an Excel report using an Export class.
     */
    public function generateExcel(object $export, string $filename)
    {
        return Excel::download($export, $filename);
    }
}
