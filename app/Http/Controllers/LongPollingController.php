<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceTimeTransactions;
use Illuminate\Http\Request;

class LongPollingController extends Controller
{
    public function GetActiveTransactions()
    {
        try {
            $activeTransactions = DeviceTimeTransactions::where('Active', true)->get();
            return response()->json($activeTransactions);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
