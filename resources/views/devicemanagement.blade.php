@php
$greetings = [
'Good day ☀️',
'Hello 👋',
'Hi 😊',
'Greetings 🙌',
'Welcome 🎉',
'Nice to see you 👏',
'Hey 😃',
'Good to see you 👍',
'Howdy 🤠',
'What’s up 🤔',
'Good to have you here 🫱',
'Hope you’re doing well 💪',
'Glad you’re back 🎊',
'Pleasure to see you 😊',
'Hiya 🤗',
'Ahoy 🏴‍☠️',
'Salutations 🖖',
'How’s it going 🚀',
'Look who it is 👀',
'A warm welcome to you 🔥',
'Good vibes only ✨',
'Happy to see you 😄',
'Cheers 🍻',
'Yo 🤟',
'How are things? 🤓',
'Great to have you 💫',
'Let’s get started! 🚀',
'Welcome back 👋',
'You’re awesome 🤩',
'Feeling good? 😎',
'Let’s make today great 🌟'
];


// Pick a random greeting
$randomGreeting = $greetings[array_rand($greetings)];
@endphp

@extends('components.layout')

@section('page-title')
@parent
<div>Device Management</div>
@endsection

@section('content')
<div class="flex flex-col h-full px-5 py-7" id="device-management-page">
    <div class="ui one column stackable grid">
        <div class="column">
            <div class="ui header !text-base">{{ $randomGreeting }}, {{ auth()->user()->FirstName }}!</div>
        </div>
    </div>

    <div class="ui divider"></div>

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
        $isOpenTime = false;
        $isPause = false;
        $durationWhenPaused = 0;
        $activeTransactions = null;

        // Fetch base time if applicable
        $baseTime = \App\Models\DeviceTime::where('DeviceID', $device->DeviceID)
        ->where('TimeTypeID', 1)
        ->first();

        $openTime = \App\Models\DeviceTime::where('DeviceID', $device->DeviceID)
        ->where('TimeTypeID', 3)
        ->first();

        $activeTransactions = \App\Models\DeviceTimeTransactions::where('DeviceID', $device->DeviceID)
        ->where('Active', true)
        ->whereIn('TransactionType', [\App\Enums\TimeTransactionTypeEnum::START,
        \App\Enums\TimeTransactionTypeEnum::EXTEND,
        \App\Enums\TimeTransactionTypeEnum::PAUSE])
        ->get();

        $resume = \App\Models\DeviceTimeTransactions::where('DeviceID', $device->DeviceID)
        ->where('Active', true)
        ->whereIn('TransactionType', [\App\Enums\TimeTransactionTypeEnum::RESUME])
        ->orderBy('TransactionID', 'desc')
        ->first();


        if ($activeTransactions->isNotEmpty()) 
        {
            $totalTime = $activeTransactions
            ->reject(function ($transaction) {
            return $transaction->TransactionType == \App\Enums\TimeTransactionTypeEnum::PAUSE;
            })
            ->sum('Duration') / 60;
            
            $totalRate = number_format($activeTransactions->sum('Rate'), 2);

            $startTransaction = $activeTransactions->where('TransactionType',
            \App\Enums\TimeTransactionTypeEnum::START)->first();
            $startTime = $startTransaction ? $startTransaction->StartTime : null;

            $isOpenTime = $startTransaction ? $startTransaction->IsOpenTime : null;

            $isPause = \App\Models\DeviceTimeTransactions::where('DeviceID', $device->DeviceID)
            ->where('Active', true)
            ->whereIn('TransactionType', [\App\Enums\TimeTransactionTypeEnum::PAUSE])
            ->orderBy('TransactionID', 'desc')
            ->first();


            // Calculate end time based on start time and total time
            if ($startTime) {
                $endTime = \Carbon\Carbon::parse($startTime)->addMinutes($totalTime);
            }

            if ($isOpenTime)
            {
                if ($isPause)
                {
                    $remainingTime = $isPause->Duration;
                }
                else {
                    $remainingTime = $startTime->diffInSeconds(\Carbon\Carbon::now(), false);
                }
            }
            else 
            {
                if ($isPause)
                {
                    if ($resume)
                    {
                        $elapsedTime = ($totalTime * 60) - $isPause->Duration;
                        $endTime = $resume->StartTime->addSeconds($elapsedTime);
                        // $remainingTime = $elapsedTime;//\Carbon\Carbon::now()->diffInSeconds($endTime, false);
                        $remainingTime = \Carbon\Carbon::now()->diffInSeconds($endTime, false);
                    }
                    else {
                        $elapsedTime = ($totalTime * 60) - $isPause->Duration;
                        $remainingTime = $elapsedTime;
                    }
                }
                else 
                {
                    if ($endTime) 
                    {
                        $remainingTime = \Carbon\Carbon::now()->diffInSeconds($endTime, false); // Calculate remaining time in seconds
                        $remainingTime = $remainingTime > 0 ? $remainingTime : 0; // Ensure it's not negative
                    }
                }
            }

        } else {
            $totalRate = 0; // Ensure totalRate is 0 if no transactions
        }

        $remTimeNotif = $device->RemainingTimeNotification;

        @endphp
        <x-device-card :device="$device" :totalTime="$totalTime" :baseTime="$baseTime" :openTime="$openTime"
            :totalRate="$totalRate" :startTime="$startTime" :endTime="$endTime" :remainingTime="$remainingTime" :usedTime="$isPause->Duration ?? 0"
            :remTimeNotif="$remTimeNotif" :isOpenTime="$isOpenTime" />
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
        const deviceStatus = card.getAttribute('data-device-status'); 
        let openTime = card.getAttribute('data-isOpenTime');
        let openTimeTime = card.getAttribute('data-openTime');
        let openTimeRate = card.getAttribute('data-openRate');
        let syncCountDown = 30; //Every 10 sync with the node

        const timerElement = card.querySelector('.remaining-time');
        const bareTimerElement = card.querySelector('.bare-remaining-time');

        // Function to update the displayed time
        function updateTimer(timerElement, remainingTime) {
            const hours = Math.floor(remainingTime / 3600);
            const minutes = Math.floor((remainingTime % 3600) / 60);
            const seconds = remainingTime % 60;
            timerElement.textContent = `Remaining time: ${hours}h ${minutes}m ${seconds}s`;
            bareTimerElement.textContent = `${remainingTime}`;
        }

        function syncNode(deviceId, remainingTime)
        {
            syncCountDown--;

            if (syncCountDown <= 0) {
                syncCountDown = 30;

                fetch(`/device-time/sync/${deviceId}/${remainingTime}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (!data)
                        {
                            console.log('Error:', data);
                        }
                    })
                    .catch((error) => {
                        console.error('Error:', error);
                    });
            }
        }

        const deviceSync = document.getElementById(`device-sync-${deviceId}`);
        const deviceCard = document.getElementById(`device-card-${deviceId}`);
        let deviceRemTimeNotif = deviceCard.getAttribute('data-remainingTimeNotif');
        
        
        function startCountdown(remainingTime, timerElement) {
            let interval = setInterval(function() {
                if (openTime == 1)
                {
                    remainingTime++;
                    const lblTotalRate = document.getElementById(`lblTotalRate-${deviceId}`);
                        
                    if ((remainingTime / 60) < openTimeTime)
                    {
                        
                    }
                    else {
                        let openTimeRunningRate = ((remainingTime / 60) / openTimeTime) * openTimeRate;
                        lblTotalRate.textContent = "Total charge/rate: PHP " + parseInt(openTimeRunningRate) + ".00";
                    }
                    
                    syncNode(deviceId, remainingTime);
                    updateTimer(timerElement, remainingTime);
                }
                else {
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

                        syncNode(deviceId, remainingTime);
                        updateTimer(timerElement, remainingTime);
                    } else {
                        // Clear the interval once the countdown reaches 0
                        clearInterval(interval);
                        
                        if (deviceSync) {
                            deviceSync.classList.remove('!hidden');
                            deviceSync.classList.add('!flex');
                            
                            fetch(`/device-time/end/${deviceId}/0`, {
                                method: 'GET',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                },
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Network response was not ok');
                                }
                                return response.json();
                            })
                            .then(data => {
                                console.log('Success:', data);
                                // Optionally, you can do something after the API call succeeds
                            })
                            .catch((error) => {
                                console.error('Error:', error);
                            });
                        }

                        // Optionally reload the page after 10 seconds
                        setTimeout(() => {
                            location.reload();
                        }, 3000);
                    }
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