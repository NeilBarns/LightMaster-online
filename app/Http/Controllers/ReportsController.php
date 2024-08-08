<?php

namespace App\Http\Controllers;

use App\Enums\TimeTransactionTypeEnum;
use App\Models\DeviceTimeTransactions;
use App\Models\RptDeviceTimeTransactions;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportsController extends Controller
{
    public function GetDailyUsage($deviceID)
    {
        $today = Carbon::now();
        $currentMonth = $today->month;
        $currentMonthName = $today->format('M'); //Get the current month name

        $dailyUsage = RptDeviceTimeTransactions::where('DeviceID', $deviceID)
            ->where('TransactionType', TimeTransactionTypeEnum::END)
            ->whereMonth('Time', $currentMonth)
            ->selectRaw('DAY(Time) as day, SUM(Duration) as totalDuration, SUM(Rate) as totalRate')
            ->groupBy('day')
            ->get();
        $data = $dailyUsage->mapWithKeys(function ($item) use ($currentMonthName) {
            return ["{$currentMonthName}-{$item->day}" => ['totalDuration' => $item->totalDuration, 'totalRate' => $item->totalRate]];
        });

        return response()->json($data);
    }

    public function GetMonthlyUsage($deviceID)
    {
        $monthlyUsage = RptDeviceTimeTransactions::where('DeviceID', $deviceID)
            ->where('TransactionType', TimeTransactionTypeEnum::END)
            ->selectRaw('MONTH(Time) as month, SUM(Duration) as totalDuration, SUM(Rate) as totalRate')
            ->groupBy('month')
            ->get();

        $data = $monthlyUsage->mapWithKeys(function ($item) {
            $monthName = Carbon::createFromFormat('m', $item->month)->format('F'); // Converts month number to month name
            return [$monthName => ['totalDuration' => $item->totalDuration, 'totalRate' => $item->totalRate]];
        });

        return response()->json($data);
    }
}
