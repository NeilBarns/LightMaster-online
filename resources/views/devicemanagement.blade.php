@extends('components.layout')

@section('page-title')
@parent
<div>Device Management</div>
@endsection

@section('content')
<div class="flex flex-col h-full px-5 py-7">
    <form class="ui form">
        @csrf
        <div class="ui three column stackable grid">
            <div class="four wide column">
                <div class="field">
                    <label>Device name</label>
                </div>
                <div class="ui fluid small input">
                    <input type="text" placeholder="Light 1...">
                </div>
            </div>
            <div class="four wide column">
                <div class="field">
                    <label>Status</label>
                </div>
                <div class="ui fluid small selection dropdown">
                    <input type="hidden" name="increment">
                    <i class="dropdown icon"></i>
                    <div class="default text">Status</div>
                    <div class="menu">
                        <div class="item" data-value="1">Pending</div>
                        <div class="item" data-value="2">Running</div>
                        <div class="item" data-value="3">Inactive</div>
                        <div class="item" data-value="4">Disabled</div>
                    </div>
                </div>
            </div>
            <div class="three wide column">
                <div class="field">
                    <label class="invisible">search</label>
                </div>
                <button class="ui fluid small blue button">Search</button>
            </div>
        </div>

        <div class="ui divider"></div>

    </form>

    @if($devices->isEmpty())
    <div class="ui flex cards h-full overflow-y-auto justify-center align-middle">
        <div class="flex justify-center items-center h-full w-full">
            <img class="max-h-80 opacity-50" src="{{ asset('imgs/no-device.png') }}" alt="No devices">
        </div>
    </div>
    @else
    <div class="ui cards h-full overflow-y-auto">
        @foreach ($devices as $device)
        @php
        $remainingTime = 0;
        $startTime = 0;

        if ($device->DeviceStatusID == \App\Enums\DeviceStatusEnum::PAUSE_ID) {
        $pauseTransaction = \App\Models\DeviceTimeTransactions::where('DeviceID', $device->DeviceID)
        ->where('TransactionType', \App\Enums\TimeTransactionTypeEnum::PAUSE)
        ->where('Active', true)
        ->orderBy('TransactionID', 'desc')
        ->first();

        if ($pauseTransaction)
        {
        $remainingTime = $pauseTransaction ? $pauseTransaction->Duration : 0;
        }

        }

        $activeTransactions = \App\Models\DeviceTimeTransactions::where('DeviceID', $device->DeviceID)
        ->where('Active', true)
        ->whereIn('TransactionType', [\App\Enums\TimeTransactionTypeEnum::START,
        \App\Enums\TimeTransactionTypeEnum::EXTEND])
        ->get();

        $baseTime = \App\Models\DeviceTime::where('DeviceID', $device->DeviceID)
        ->where('TimeTypeID', 1)->first();

        $totalTime = $activeTransactions->sum('Duration');
        $totalRate = number_format($activeTransactions->sum('Rate'), 2);

        $startTransaction = $activeTransactions->where('TransactionType',
        \App\Enums\TimeTransactionTypeEnum::START)->first();
        $startTime = $startTransaction ? $startTransaction->StartTime : null;
        $endTime = $startTime ? \Carbon\Carbon::parse($startTime)->addMinutes($totalTime) : null;

        //Analyze if the time came from Pause and gonna be Resumed
        $transactionList = \App\Models\DeviceTimeTransactions::where('DeviceID', $device->DeviceID)
        ->where('Active', true)
        ->orderBy('TransactionID', 'desc')
        ->first();

        if ($transactionList)
        {

        $transactionType = $transactionList->TransactionType;
        if ($transactionType == \App\Enums\TimeTransactionTypeEnum::RESUME)
        {

        //Get the latest Pause
        $pauseTransaction = \App\Models\DeviceTimeTransactions::where('DeviceID', $device->DeviceID)
        ->where('TransactionType', \App\Enums\TimeTransactionTypeEnum::PAUSE)
        ->where('Active', true)
        ->orderBy('TransactionID', 'asc')
        ->first();

        if ($pauseTransaction)
        {
        //Compute for the remaining time
        $remainingTime = $pauseTransaction ? $pauseTransaction->Duration : 0;
        }
        $endTime = $transactionList->StartTime->addSeconds($remainingTime);
        }
        }

        @endphp
        <x-device-card :device="$device" :totalTime="$totalTime" :baseTime="$baseTime" :totalRate="$totalRate"
            :startTime="$startTime" :endTime="$endTime" :remainingTime="$remainingTime" />
        @endforeach
    </div>

    @endif


</div>


@endsection