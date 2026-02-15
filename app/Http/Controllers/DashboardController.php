<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\plate;
use App\Models\transaction;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function getStats()
    {
        $revenue = transaction::where('status', 'paid')->sum('total_harga');
        $transactions = transaction::where('status', 'paid')->count();
        $plates = plate::count(); // Show total plates
        $users = User::count();   // Show total users

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

    public function getActivities(Request $request)
    {
        $limit = $request->get('limit', 10);
        $activities = ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'status' => true,
            'data' => $activities
        ]);
    }

    public function getRevenue(Request $request)
    {
        $query = transaction::where('status', 'paid');

        // Extended Filtering
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        } elseif ($request->has('month') && $request->has('year')) {
            $query->whereYear('created_at', $request->year)
                ->whereMonth('created_at', $request->month);
        } elseif ($request->has('year')) {
            $query->whereYear('created_at', $request->year);
        } else {
            $days = $request->get('days', 7);
            $query->where('created_at', '>=', now()->subDays($days));
        }

        $revenue = $query->select(
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
        $query = DB::table('transaction_details')
            ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->join('plates', 'transaction_details.plate_id', '=', 'plates.id')
            ->join('items', 'plates.item_id', '=', 'items.id')
            ->where('transactions.status', 'paid');

        // Apply same filters as revenue if provided
        if ($request->has('month') && $request->has('year')) {
            $query->whereYear('transactions.created_at', $request->year)
                ->whereMonth('transactions.created_at', $request->month);
        }

        $popular = $query->select(
            'items.id',
            'items.nama_item as name',
            DB::raw('COUNT(*) as sold')
        )
            ->groupBy('items.id', 'items.nama_item')
            ->orderBy('sold', 'desc')
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

        $transactions = transaction::with(['user', 'payment', 'details'])
            ->where('status', 'paid')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($trx) {
                return [
                    'id' => $trx->id,
                    'order_id' => $trx->id, // Frontend uses order_id
                    'customer' => $trx->user->name ?? 'Guest',
                    'total_amount' => $trx->total_harga, // Frontend uses total_amount
                    'status' => $trx->status,
                    'created_at' => $trx->created_at->toIso8601String(), // Frontend uses created_at
                    'items_count' => $trx->details->count() // Frontend uses items_count
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $transactions
        ]);
    }
}
