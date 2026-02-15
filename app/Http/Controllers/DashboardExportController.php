<?php

namespace App\Http\Controllers;

use App\Exports\TransactionsExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DashboardExportController extends Controller
{
    public function export(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $filename = 'laporan-transaksi-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new TransactionsExport($startDate, $endDate), $filename);
    }
}
