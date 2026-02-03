<?php

namespace App\Providers;

use App\Models\plate;
use Illuminate\Support\ServiceProvider;

class helper extends ServiceProvider
{
    public static function jsonToArray($data): array
    {
        if (!isset($data['plate']) || !is_array($data['plate'])) {
            return [];
        }

        $rfids = array_column($data['plate'], 'rfid_uid');

        if (empty($rfids)) {
            return [];
        }

        return Plate::whereIn('rfid_uid', $rfids)
            ->pluck('id', 'rfid_uid')
            ->toArray();
    }



}
