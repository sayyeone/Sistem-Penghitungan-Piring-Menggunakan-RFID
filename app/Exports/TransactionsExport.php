<?php

namespace App\Exports;

use App\Models\transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransactionsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = transaction::with(['user', 'details.plate.item'])
            ->where('status', 'paid');

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID Transaksi',
            'Tanggal',
            'Pelanggan',
            'Total Harga',
            'Status',
            'Detail Items'
        ];
    }

    public function map($transaction): array
    {
        $items = $transaction->details->map(function ($detail) {
            return ($detail->plate->item->nama_item ?? 'Unknown') . " (Rp " . number_format($detail->harga, 0, ',', '.') . ")";
        })->implode(', ');

        return [
            $transaction->id,
            $transaction->created_at->format('Y-m-d H:i:s'),
            $transaction->user->name ?? 'Guest',
            $transaction->total_harga,
            $transaction->status,
            $items
        ];
    }
}
