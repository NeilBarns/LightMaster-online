<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>{{ config('app.name') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('imgs/lightmaster-icon.png') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- JQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    {{--
    <link href="/css/app.css" rel="stylesheet"> --}}

    <!-- UIkit CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/uikit@3.20.8/dist/css/uikit.min.css" />

    <!-- UIkit JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/uikit@3.20.8/dist/js/uikit.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/uikit@3.20.8/dist/js/uikit-icons.min.js"></script>

    <!-- AG Grid -->
    {{-- <script defer src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.js"></script> --}}
    <link rel="stylesheet" href="https://unpkg.com/ag-grid-community/styles/ag-grid.css">
    <link rel="stylesheet" href="https://unpkg.com/ag-grid-community/styles/ag-theme-balham.css">
    <script src="https://unpkg.com/ag-grid-community/dist/ag-grid-community.min.js"></script>

    <!-- Chart.js  -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Fomantic IU -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.9.3/semantic.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.9.3/semantic.min.js"></script>

</head>

<body id="bdy" class="antialiased">
    <div class="toast-container" id="toastContainer"></div>
    <div id="loadingScreen" style="">
        <img src="{{ asset('imgs/loading.gif') }}" alt="Loading...">
    </div>
    <div class="flex flex-row h-full">
        <div class="grow-0 w-72 min-w-72 h-full">
            <x-side-nav />
        </div>
        <div class="grow h-full">
            <div class="flex flex-col grow-0 h-full">
                <div class="grow-0 flex flex-row h-16 min-h-16 shadow-md bg-white">
                    <form id="logout-form" action="{{ route('auth.logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                    <div class="basis-1/2">
                        <div class="flex items-center px-4 h-full text-sm font-bold">
                            @section('page-title')
                            @show
                        </div>
                    </div>
                    <div class="basis-1/2 flex items-center justify-end px-4">
                        <div class="relative inline-block text-left">
                            <button id="user-menu-button" class="flex items-center focus:outline-none">
                                <img src="{{ asset('imgs/people.png') }}" alt="User Image" class="w-8 h-8 rounded-full">
                                <span class="ml-2 text-sm font-medium text-gray-700">{{ auth()->user()->FirstName }} {{
                                    auth()->user()->LastName }}</span>
                                <svg class="w-4 h-4 ml-1 text-gray-700" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="user-menu" style="z-index: 1000 !important"
                                class="hidden origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none">
                                <div class="py-1">
                                    <a href="#"
                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                                    <a href="#"
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="h-[92%] overflow-y-hidden">
                    @yield('content')
                    <script>
                        $(document).ready(function() {
                            @if (session('toast_message'))
                                $.toast({
                                    title: 'Success!',
                                    class: 'success',
                                    displayTime: 3000,
                                    position: 'bottom right',
                                    message: "{{ session('toast_message') }}"
                                });
                            @endif
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/service-worker.js').then(function (registration) {
                    console.log('ServiceWorker registration successful with scope: ', registration.scope);
                }, function (err) {
                    console.log('ServiceWorker registration failed: ', err);
                });
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const toastData = sessionStorage.getItem('toastMessage');
            if (toastData) {
                const { message, type } = JSON.parse(toastData);
                showToast(message, type);
                sessionStorage.removeItem('toastMessage');
            }
        });

        function showLoading() {
            const loadingScreen = document.getElementById('loadingScreen');
            loadingScreen.classList.remove('!hidden'); // Remove the '!hidden' class
            loadingScreen.classList.add('!block'); // Add the '!block' class
        }

        function hideLoading() {
            const loadingScreen = document.getElementById('loadingScreen');
            loadingScreen.classList.remove('!block'); // Remove the '!block' class
            loadingScreen.classList.add('!hidden'); // Add the '!hidden' class
        }

        // Toast function
        function showToast(message, type = 'error', duration = 3000) {
            console.log(message);
            const toastContainer = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.classList.add('toast', type);
            toast.classList.add('!flex');
            toast.classList.add('flex-row');

            // toast success show  
            toast.innerHTML = '<div style="padding: 15px; display: flex; justify-content: center; align-items: center;"><i style="font-size: large;" class="exclamation triangle icon"></i></div><div style="display: flex;align-items: center;text-align: left;padding: 5px;">' + message + '</div>';

            toastContainer.appendChild(toast);

            // Show toast
            setTimeout(() => {
                toast.classList.add('show');
            }, 100);

            // Remove toast
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 500);
            }, duration);
        }

        // Polling functionality
        let pollingInterval = 10000;
        let currentlyWatchedDevices = [];
        let timeoutId = 0;
        const MIN_POLLING_INTERVAL = 5000; // Minimum polling interval in milliseconds (e.g., 5 seconds)

        function fetchActiveTransactions() {
            console.log('timeoutId', timeoutId);
            
            fetch('/active-transactions')
                .then(response => response.json())
                .then(data => {
                    const processedData = processDeviceTransactions(data);
                    console.log('Processed Transaction Data:', processedData);
                    pollingUpdateUI(data, processedData);
                })
                .catch(error => console.error('Error fetching transactions:', error));
        }

        function processDeviceTransactions(data) {
            return data.map(transaction => {
                let totalDuration = 0;
                let totalRate = 0;
                let startTime = null;
                let endTime = null;

                totalDuration += transaction.Duration;
                totalRate += parseFloat(transaction.Rate);
                if (!startTime) {
                    startTime = new Date(transaction.StartTime).getTime();
                }

                if (startTime !== null) {
                    endTime = (startTime + totalDuration * 60 * 1000) - 5000; // Convert total duration to milliseconds
                }

                totalRate = totalRate.toFixed(2);

                const currentTime = Date.now();
                const remainingTimeInSeconds = endTime ? Math.max(0, Math.floor((endTime - currentTime) / 1000)) : 0;
                console.log('remainingTimeInSeconds', remainingTimeInSeconds);
                
                // Set polling interval based on remaining time, ensuring it does not go below the minimum interval
                pollingInterval = Math.max(remainingTimeInSeconds * 1000, MIN_POLLING_INTERVAL);

                return {
                    DeviceID: transaction.DeviceID,
                    totalDuration,
                    totalRate,
                    startTime: startTime ? new Date(startTime).toISOString() : null,
                    endTime: endTime ? new Date(endTime).toISOString() : null,
                    remainingTimeInSeconds
                };
            });
        }

        function startPolling() {
            console.log('pollingInterval', pollingInterval);

            //clearTimeout(timeoutId); // Clear any existing timeout

            //fetchActiveTransactions();

            // Set a new timeout with the updated interval
            //timeoutId = setTimeout(startPolling, pollingInterval);
        }

        function isEmptyArray(data) {
            return Array.isArray(data) && data.length === 0;
        }

        function pollingUpdateUI(data, processedData) {
            console.log('pollingUpdateUI');
            console.log('currentlyWatchedDevices', currentlyWatchedDevices);
            if (!isEmptyArray(data)) {
                processedData.forEach(device => {
                    const deviceId = device.DeviceID;
                    currentlyWatchedDevices.push(deviceId);
                });
            } else {
                if (currentlyWatchedDevices.length > 0) {
                    currentlyWatchedDevices.forEach(deviceId => {
                        const startTimeElement = document.getElementById(`lblStartTime-${deviceId}`);
                        const endTimeElement = document.getElementById(`lblEndTime-${deviceId}`);
                        const totalTimeElement = document.getElementById(`lblTotalTime-${deviceId}`);
                        const totalRateElement = document.getElementById(`lblTotalRate-${deviceId}`);

                        if (startTimeElement && endTimeElement && totalTimeElement && totalRateElement) {
                            startTimeElement.textContent = `Start time: --:--`;
                            endTimeElement.textContent = `End time: --:--`;
                            totalTimeElement.textContent = `Total time: 0 hr 0 mins`;
                            totalRateElement.textContent = `Total charge/rate: PHP 0.00`;
                        }

                        const startButton = document.querySelector(`.start-time-button[data-id="${deviceId}"]`);
                        startButton.textContent = 'Start Time';
                        startButton.classList.remove('red');
                        const extendButtonMenu = document.querySelector(`.dropdown[data-id="${deviceId}"]`);
                        const extendButton = document.querySelector(`.extend-time-single-button[data-id="${deviceId}"]`);
                        if (extendButtonMenu) {
                            extendButtonMenu.classList.add('disabled');
                        }
                        if (extendButton) {
                            extendButton.classList.add('disabled');
                            extendButton.setAttribute('disabled', 'true');
                        }

                        updateStatusRibbon(deviceId, 'Inactive');
                    });

                    currentlyWatchedDevices = [];
                }
            }
            console.log('Active transactions:', data);
        }

        startPolling();

        function updateStatusRibbon(deviceId, newStatus) {
            const statusRibbon = document.getElementById(`device-status-${deviceId}`);
            const statusClasses = {
                'Pending Configuration': 'grey',
                'Running': 'green',
                'Inactive': 'yellow',
                'Disabled': 'red'
            };
            statusRibbon.className = `ui ${statusClasses[newStatus]} ribbon label`;
            statusRibbon.textContent = newStatus;
        }

        document.addEventListener('DOMContentLoaded', function () {
            var userMenuButton = document.getElementById('user-menu-button');
            var userMenu = document.getElementById('user-menu');

            userMenuButton.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent the click from propagating to the window
                userMenu.classList.toggle('hidden');
            });

            // Close the dropdown if clicked outside
            window.addEventListener('click', function(e) {
                if (!userMenu.contains(e.target) && e.target !== userMenuButton) {
                    userMenu.classList.add('hidden');
                }
            });
        });

    </script>

    @yield('scripts')

</body>

</html>