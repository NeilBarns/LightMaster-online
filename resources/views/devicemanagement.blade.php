@extends('components.layout')

@section('page-title')
@parent
<div>Device Management</div>
@endsection

@section('content')
<div class="flex flex-col h-full px-5 py-7" id="device-management-page">
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
    <div class="ui cards h-full overflow-y-auto" id="device-cards-container">
        @foreach ($devices as $device)
        @php
        $remainingTime = 0;
        $startTime = 0;
        $endTime = null;
        $totalTime = 0; // Initialize totalTime

        // Fetch base time if applicable
        $baseTime = \App\Models\DeviceTime::where('DeviceID', $device->DeviceID)
        ->where('TimeTypeID', 1)
        ->first();

        // Fetch active transactions for the device
        $activeTransactions = \App\Models\DeviceTimeTransactions::where('DeviceID', $device->DeviceID)
        ->where('Active', true)
        ->whereIn('TransactionType', [\App\Enums\TimeTransactionTypeEnum::START,
        \App\Enums\TimeTransactionTypeEnum::EXTEND])
        ->get();

        if ($activeTransactions->isNotEmpty()) {
        $totalTime = $activeTransactions->sum('Duration');
        $totalRate = number_format($activeTransactions->sum('Rate'), 2);

        $startTransaction = $activeTransactions->where('TransactionType',
        \App\Enums\TimeTransactionTypeEnum::START)->first();
        $startTime = $startTransaction ? $startTransaction->StartTime : null;

        // Calculate end time based on start time and total time
        if ($startTime) {
        $endTime = \Carbon\Carbon::parse($startTime)->addMinutes($totalTime);
        }

        // Calculate the remaining time
        if ($endTime) {
        $remainingTime = \Carbon\Carbon::now()->diffInSeconds($endTime, false); // Calculate remaining time in seconds
        $remainingTime = $remainingTime > 0 ? $remainingTime : 0; // Ensure it's not negative
        }
        } else {
        $totalRate = 0; // Ensure totalRate is 0 if no transactions
        }

        $remTimeNotif = $device->RemainingTimeNotification;

        @endphp
        <x-device-card :device="$device" :totalTime="$totalTime" :baseTime="$baseTime" :totalRate="$totalRate"
            :startTime="$startTime" :endTime="$endTime" :remainingTime="$remainingTime" :remTimeNotif="$remTimeNotif" />
        @endforeach

    </div>

    @endif

    <div class="fixed bottom-4 right-4">
        <button id="signalButton" onclick="location.reload();"
            class="w-16 h-16 bg-green-300 rounded-full shadow-lg flex items-center justify-center"
            title="Locate device">
            <img src="{{ asset('imgs/signal.png') }}" alt="Signal" class="w-10 h-10">
        </button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Initialize timers for each device
    const deviceCards = document.querySelectorAll('[data-remaining-time]');

    deviceCards.forEach(function(card) {
        const deviceId = card.getAttribute('data-device-id');
        let remainingTime = parseInt(card.getAttribute('data-remaining-time'));
        const deviceStatus = card.getAttribute('data-device-status'); // Get the device status

        const timerElement = card.querySelector('.remaining-time');

        // Function to update the displayed time
        function updateTimer(timerElement, remainingTime) {
            const hours = Math.floor(remainingTime / 3600);
            const minutes = Math.floor((remainingTime % 3600) / 60);
            const seconds = remainingTime % 60;
            timerElement.textContent = `Remaining time: ${hours}h ${minutes}m ${seconds}s`;
        }

        const deviceSync = document.getElementById(`device-sync-${deviceId}`);
        const deviceCard = document.getElementById(`device-card-${deviceId}`);
        let deviceRemTimeNotif = deviceCard.getAttribute('data-remainingTimeNotif');
        console.log(deviceRemTimeNotif);
        function startCountdown(remainingTime, timerElement, deviceId) {
            let interval = setInterval(function() {
                if (remainingTime > 0) {
                    remainingTime--;
                    
                    if (deviceRemTimeNotif !== null && deviceRemTimeNotif > 0)
                    {
                        if (remainingTime <= deviceRemTimeNotif * 60)
                        {
                            deviceCard.classList.add('!bg-amber-200');   
                            card.classList.add('!font-bold');
                            card.classList.add('!text-xl');
                        }
                    }

                    updateTimer(timerElement, remainingTime);
                } else {
                    // Clear the interval once the countdown reaches 0
                    clearInterval(interval);
                    if (deviceSync) {
                        deviceSync.classList.remove('!hidden');
                        deviceSync.classList.add('!flex');
                    }

                    // Optionally reload the page after 10 seconds
                    setTimeout(() => {
                        location.reload();
                    }, 10000);
                }
            }, 1000);

            return interval; // Return the interval so it can be cleared elsewhere if needed
        }

        // If the device is paused, display the remaining time but do not start the countdown
        if (deviceStatus === 'pause') {
            updateTimer(timerElement, remainingTime); // Display the remaining time
        } 
        // If the device is running or resumed, start the countdown
        else if (deviceStatus === 'running' || deviceStatus === 'resume') {
            startCountdown(remainingTime, timerElement);
        } else {
            // console.log(`Device ${deviceId} is in an unknown state: ${deviceStatus}`);
        }
    });
});

document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'visible') {
        // Refresh the page when the user comes back to the tab
        location.reload();
    }
});
</script>

@endsection