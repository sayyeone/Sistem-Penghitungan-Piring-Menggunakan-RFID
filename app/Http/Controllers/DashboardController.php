<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Plate;
use App\Models\transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function getStats()
    {
        $revenue = transaction::where('status', 'paid')->sum('total_harga');
        $transactions = transaction::where('status', 'paid')->count();
        $plates = Plate::where('is_active', 1)->count();
        $users = User::count();

        return response()->json([
            'status' => true,
            'data' => [
                'revenue' => (int) $revenue,
                'transactions' => $transactions,
                'plates' => $plates,
                'users' => $users
            ]
        ]);
    }

    public function getRevenue(Request $request)
    {
        $days = $request->get('days', 7);

        $revenue = transaction::where('status', 'paid')
            ->where('created_at', '>=', now()->subDays($days))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_harga) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $revenue
        ]);
    }

    public function getPopularPlates(Request $request)
    {
        $limit = $request->get('limit', 5);

        $popular = DB::table('transaction_details')
            ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->join('plates', 'transaction_details.plate_id', '=', 'plates.id')
            ->join('items', 'plates.item_id', '=', 'items.id')
            ->where('transactions.status', 'paid')
            ->select(
                'items.nama_item as name',
                'plates.rfid_uid',
                DB::raw('COUNT(*) as total_sold')
            )
            ->groupBy('items.nama_item', 'plates.rfid_uid', 'plates.id')
            ->orderBy('total_sold', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'status' => true,
            'data' => $popular
        ]);
    }

    public function getRecentTransactions(Request $request)
    {
        $limit = $request->get('limit', 5);

        $transactions = transaction::with(['user', 'payment'])
            ->where('status', 'paid')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($trx) {
                return [
                    'id' => $trx->id,
                    'customer' => $trx->user->name ?? 'Guest',
                    'amount' => $trx->total_harga,
                    'status' => $trx->status,
                    'date' => $trx->created_at->format('Y-m-d H:i:s')
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $transactions
        ]);
    }
}
