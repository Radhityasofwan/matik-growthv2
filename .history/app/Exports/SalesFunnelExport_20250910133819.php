<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class SalesFunnelExport implements FromView
{
    protected $data;

    /**
     * Menerima data yang akan di-render ke dalam view.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Mengembalikan view yang akan di-render menjadi file Excel.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function view(): View
    {
        return view('reports.sales_funnel_excel', [
            'leads' => $this->data['leads'],
            'stats' => $this->data['stats'],
            'date' => $this->data['date'],
        ]);
    }
}
