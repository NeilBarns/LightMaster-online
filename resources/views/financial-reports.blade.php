@extends('components.layout')

@section('page-title')
@parent
<div>Financial Reports</div>
@endsection

@section('content')
<div class="flex flex-col h-full px-5 py-7 overflow-y-auto overflow-x-hidden">
    <div class="ui stackable equal width grid">
        <div class="row">
            <div class="column">
                <div class="text-center">
                    <h3 id="chartTitle">Monthly Rate and Usage By Device</h3>
                    <p id="chartSubtitle" class="text-muted text-gray-400">For the current year of {{ date('Y') }}</p>
                    <canvas id="financialChart" width="400" height="160"></canvas>
                </div>
            </div>
        </div>

        <div class="ui divider"></div>
        <div class="row">
            <div class="column">
                <h5 class="ui header">Time Transactions
                </h5>
            </div>
        </div>

        <div class="row">
            <div class="column">
                <div class="filter-section mb-4">
                    <div class="ui form">
                        <div class="four fields">
                            <div class="field">
                                <label>Start Date</label>
                                <input type="date" id="startDate" name="startDate">
                            </div>
                            <div class="field">
                                <label>End Date</label>
                                <input type="date" id="endDate" name="endDate">
                            </div>
                            <div class="field">
                                <label>Device</label>
                                <select id="deviceFilter" name="deviceFilter" class="ui fluid dropdown" multiple>
                                    <option value="">All Devices</option>
                                    @foreach ($devices as $device)
                                    <option value="{{ $device->ExternalDeviceName }}">{{ $device->ExternalDeviceName }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label>Transaction Type</label>
                                <select id="transactionFilter" name="transactionFilter" class="ui fluid dropdown"
                                    multiple>
                                    <option value="">All Transactions</option>
                                    <option value="Start">Start</option>
                                    <option value="End">End</option>
                                    <option value="Pause">Pause</option>
                                    <option value="Resume">Resume</option>
                                    <option value="Start Free">Start Free</option>
                                    <option value="End Free">End Free</option>
                                </select>
                            </div>
                            <div class="field">
                                <label>Triggered By</label>
                                <select id="triggeredByFilter" name="triggeredByFilter" class="ui fluid dropdown"
                                    multiple>
                                    <option value="">All Users</option>
                                    <option value="Device">Device</option>
                                    @foreach ($users as $user)
                                    <option value="{{ $user->FirstName }} {{ $user->LastName }}">{{ $user->FirstName }}
                                        {{ $user->LastName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label class="!text-white">Apply</label>
                                <button class="ui small button primary" id="applyFilters">Apply Filters</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="tableContainer" class="ui celled table-container max-h-[500px] overflow-y-auto">
                    <table class="ui celled table" id="transactionTable">
                        <thead class="sticky top-0 bg-white">
                            <tr>
                                <th class="px-4 py-2">Device</th>
                                <th class="px-4 py-2">Transaction</th>
                                <th class="px-4 py-2">Time</th>
                                <th class="px-4 py-2">Duration</th>
                                <th class="px-4 py-2">Rate</th>
                                <th class="px-4 py-2">Triggered By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            $totalRate = 0;
                            $totalDuration = 0;
                            @endphp

                            @foreach($rptDeviceTimeTransactions as $transaction)
                            @php
                            // Accumulate total rate and duration
                            $totalRate += $transaction->Rate;
                            $totalDuration += $transaction->Duration;

                            // Determine the creator name
                            $creatorName = 'N/A';
                            if ($transaction->CreatedByUserId === 999999) {
                            $creatorName = 'Device';
                            } elseif ($transaction->creator && $transaction->creator->UserID > 0) {
                            $creatorName = $transaction->creator->FirstName . ' ' . $transaction->creator->LastName;
                            }
                            @endphp
                            @if ($transaction->TransactionType == 'End')
                            <tr>
                                <td>{{ $transaction->device->ExternalDeviceName }}</td>
                                <td>{{ $transaction->TransactionType }}</td>
                                <td>{{ $transaction->Time->format('F d, Y h:i:s A') }}</td>
                                <td colspan="2" class="font-bold">{{ $transaction->StoppageType }} Stoppage</td>
                                <td>{{ $creatorName }}</td>
                            </tr>
                            <tr class="font-bold">
                                <td colspan="3" class="font-bold">Total Duration and Rate</td>
                                <td>{{ convertMinutesToHoursAndMinutes($transaction->Duration) }}</td>
                                <td colspan="3">PHP {{ number_format($transaction->Rate, 2) }}</td>
                            </tr>
                            @elseif ($transaction->TransactionType == 'Pause')
                            <tr>
                                <td>{{ $transaction->device->ExternalDeviceName }}</td>
                                <td>{{ $transaction->TransactionType }}</td>
                                <td>{{ $transaction->Time->format('F d, Y h:i:s A') }}</td>
                                <td colspan="2" class="font-bold">Remaining time: {{
                                    convertSecondsToTimeFormat($transaction->Duration) }}</td>
                                <td>{{ $creatorName }}</td>
                            </tr>
                            @elseif ($transaction->TransactionType == 'Start Free')
                            <tr>
                                <td>{{ $transaction->device->ExternalDeviceName }}</td>
                                <td>{{ $transaction->TransactionType }}</td>
                                <td>{{ $transaction->Time->format('F d, Y h:i:s A') }}</td>
                                <td colspan="2">
                                    <a href="javascript:void(0);"
                                        onclick="showReasonModal('{{ $transaction->Reason }}')">
                                        Show reason
                                    </a>
                                </td>
                                <td>{{ $creatorName }}</td>
                            </tr>
                            @elseif ($transaction->TransactionType == 'End Free')
                            <tr>
                                <td>{{ $transaction->device->ExternalDeviceName }}</td>
                                <td>{{ $transaction->TransactionType }}</td>
                                <td>{{ $transaction->Time->format('F d, Y h:i:s A') }}</td>
                                <td colspan="2">Duration: {{ convertSecondsToTimeFormat($transaction->Duration) }}</td>
                                <td>{{ $creatorName }}</td>
                            </tr>
                            @elseif ($transaction->TransactionType == 'Resume')
                            <tr>
                                <td>{{ $transaction->device->ExternalDeviceName }}</td>
                                <td>{{ $transaction->TransactionType }}</td>
                                <td colspan="3">{{ $transaction->Time->format('F d, Y h:i:s A') }}</td>
                                <td>{{ $creatorName }}</td>
                            </tr>
                            @else
                            <tr>
                                <td>{{ $transaction->device->ExternalDeviceName }}</td>
                                <td>{{ $transaction->TransactionType }}</td>
                                <td>{{ $transaction->Time->format('F d, Y h:i:s A') }}</td>
                                <td>{{ $transaction->Duration }}</td>
                                <td>{{ number_format($transaction->Rate, 2) }}</td>
                                <td>{{ $creatorName }}</td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                        <tfoot class="sticky bottom-0 bg-white !font-bold">
                            <tr class="!font-bold bg-cyan-100 h-20">
                                <td colspan="3" class="!font-bold">Overall Duration and Rate Total</td>
                                <td class="!font-bold">{{ convertMinutesToHoursAndMinutes($totalDuration) }}</td>
                                <td colspan="3" class="!font-bold">PHP {{ number_format($totalRate, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<div id="reasonModal" class="ui modal">
    <div class="header">Reason</div>
    <div class="content">
        <p id="reasonContent"></p>
    </div>
    <div class="actions">
        <button class="ui button" onclick="$('#reasonModal').modal('hide')">Close</button>
    </div>
</div>
@endsection

<script>
    document.addEventListener('DOMContentLoaded', function() {

    const today = new Date().toISOString().split('T')[0]; // Format as 'YYYY-MM-DD'

    document.getElementById('startDate').value = today;
    document.getElementById('endDate').value = today;

    const devicesData = @json($data);

    function getConfig(viewType, devicesData) {
        const monthlyLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        const datasets = [
            ...devicesData.map((device, index) => ({
                label: device.name + " Rate",
                data: device.monthlyRates,
                backgroundColor: `rgba(54, 162, 235)`,
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                borderRadius: {
                    topLeft: 5,
                    topRight: 5,
                },
                yAxisID: 'y',
                order: 2 + index, // Stagger bar order
            })),
            ...devicesData.map((device, index) => ({
                label: device.name + " Usage",
                data: device.monthlyUsage,
                type: 'line',
                backgroundColor: 'rgba(255, 0, 0, 0.5)', // Line fill color
                borderColor: 'rgba(0, 0, 0, 1)', // Red line
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: 'rgba(255, 0, 0, 1)',
                yAxisID: 'y1',
                order: 1, // Ensure line is in front
                tension: 0.4, // Smooth line
                cubicInterpolationMode: 'monotone'
            })),
        ];

        return {
            type: 'bar',
            data: {
                labels: monthlyLabels,
                datasets: datasets,
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false, // Hide the legend
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Month'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Rate (PHP)'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false // Only grid lines for the first y-axis
                        },
                        title: {
                            display: true,
                            text: 'Usage (minutes)'
                        }
                    }
                }
            }
        };
    }

    // Function to convert seconds to a formatted string (hours, minutes, seconds)
    function convertSecondsToTimeFormat(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const remainingSeconds = seconds % 60;

        let timeString = '';

        if (hours > 0) {
            timeString += `${hours} hr${hours > 1 ? 's' : ''} `;
        }
        if (minutes > 0) {
            timeString += `${minutes} min${minutes > 1 ? 's' : ''} `;
        }
        if (remainingSeconds > 0) {
            timeString += `${remainingSeconds} sec${remainingSeconds > 1 ? 's' : ''}`;
        }

        return timeString.trim();
    }

    // Function to convert minutes to hours and minutes format
    function convertMinutesToHoursAndMinutes(minutes) {
        const hours = Math.floor(minutes / 60);
        const remainingMinutes = minutes % 60;

        let timeString = '';

        if (hours > 0) {
            timeString += `${hours} hr${hours > 1 ? 's' : ''} `;
        }
        if (remainingMinutes > 0) {
            timeString += `${remainingMinutes} min${remainingMinutes > 1 ? 's' : ''}`;
        }

        return timeString.trim();
    }

    function showReasonModal(reason) {
        const reasonModal = document.getElementById('reasonModal');
        const reasonContent = document.getElementById('reasonContent');

        if (reasonModal && reasonContent) {
            reasonContent.textContent = reason; // Set the reason in the modal
            $(reasonModal).modal('show'); // Show the modal using Semantic UI's modal function
        } else {
            console.error('Modal or reason content element not found');
        }
    }

    // Function to format numbers
    function number_format(number, decimals) {
        if (typeof number !== 'number' || isNaN(number)) {
            console.log(number);
            number = 0; // Default to 0 if the value is not a number
        }
        return number.toFixed(decimals);
        // return number;
    }

    function validateAndFormatNumber(value) {
        // Convert value to a number
        const number = typeof value === 'string' ? parseFloat(value) : value;

        // Validate the number
        if (typeof number !== 'number' || isNaN(number)) {
            console.error('Invalid number:', value);
            return null; // Handle invalid numbers
        }

        // Format the number to two decimal places
        return number.toFixed(2);
    }

    function scrollToTop() {
        const container = document.getElementById('tableContainer');
        if (container) {
            container.scrollTop = 0;
        }
    }

    document.getElementById('applyFilters').addEventListener('click', function() {
        showLoading();
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        const deviceNames = $('#deviceFilter').val(); // Get selected values as array
        const transactionTypes = $('#transactionFilter').val(); // Get selected values as array
        const triggeredBy = $('#triggeredByFilter').val(); // Get selected values as array

        // Construct URL with query parameters
        const url = new URL('/reports/transactions/filter', window.location.origin);
        url.searchParams.append('startDate', startDate);
        url.searchParams.append('endDate', endDate);

        // Append array parameters properly
        deviceNames.forEach(name => url.searchParams.append('deviceNames[]', name));
        transactionTypes.forEach(type => url.searchParams.append('transactionTypes[]', type));
        triggeredBy.forEach(userId => url.searchParams.append('triggeredBy[]', userId));

        fetch(url, {
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            const tbody = document.querySelector('#transactionTable tbody');
            const tfoot = document.querySelector('#transactionTable tfoot');
            let totalRate = 0;
            let totalDuration = 0;

            tbody.innerHTML = ''; // Clear existing rows
            tfoot.innerHTML = ''; // Clear existing rows

            data.forEach(transaction => {
                totalRate += parseFloat(transaction.Rate);
                totalDuration += transaction.Duration;

                const creatorName = transaction.CreatedByUserId === 999999 ? 'Device' : (transaction.creator ? `${transaction.creator.FirstName} ${transaction.creator.LastName}` : 'N/A');
                const formattedTime = new Date(transaction.Time).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: 'numeric',
                    second: 'numeric',
                    hour12: true
                });

                let rowHtml = `<tr><td>${transaction.device.ExternalDeviceName}</td>`;

                if (transaction.TransactionType === 'End') {
                    rowHtml += `
                        <td>${transaction.TransactionType}</td>
                        <td>${formattedTime}</td>
                        <td colspan="2" class="font-bold">${transaction.StoppageType} Stoppage</td>
                        <td>${creatorName}</td>
                    </tr>
                    <tr class="font-bold">
                        <td colspan="3" class="font-bold">Total Duration and Rate</td>
                        <td>${convertMinutesToHoursAndMinutes(transaction.Duration)}</td>
                        <td colspan="3">PHP ${validateAndFormatNumber(transaction.Rate, 2)}</td>
                    </tr>`;
                } else if (transaction.TransactionType === 'Pause') {
                    rowHtml += `
                        <td>${transaction.TransactionType}</td>
                        <td>${formattedTime}</td>
                        <td colspan="2" class="font-bold">Remaining time: ${convertSecondsToTimeFormat(transaction.Duration)}</td>
                        <td>${creatorName}</td>
                    </tr>`;
                } else if (transaction.TransactionType === 'Start Free') {
                    rowHtml += `
                        <td>${transaction.TransactionType}</td>
                        <td>${formattedTime}</td>
                        <td colspan="2">
                            <a href="javascript:void(0);" onclick="showReasonModal('${transaction.Reason}')">Show reason</a>
                        </td>
                        <td>${creatorName}</td>
                    </tr>`;
                } else if (transaction.TransactionType === 'End Free') {
                    rowHtml += `
                        <td>${transaction.TransactionType}</td>
                        <td>${formattedTime}</td>
                        <td colspan="2">Duration: ${convertSecondsToTimeFormat(transaction.Duration)}</td>
                        <td>${creatorName}</td>
                    </tr>`;
                } else if (transaction.TransactionType === 'Resume') {
                    rowHtml += `
                        <td>${transaction.TransactionType}</td>
                        <td colspan="3">${formattedTime}</td>
                        <td>${creatorName}</td>
                    </tr>`;
                } else {
                    rowHtml += `
                        <td>${transaction.TransactionType}</td>
                        <td>${formattedTime}</td>
                        <td>${convertMinutesToHoursAndMinutes(transaction.Duration)}</td>
                        <td>${validateAndFormatNumber(transaction.Rate, 2)}</td>
                        <td>${creatorName}</td>
                    </tr>`;
                }

                tbody.innerHTML += rowHtml;
            });

            let rowFoorHtml = `<tr class="!font-bold bg-cyan-100 h-20">
                <td colspan="3" class="!font-bold">Overall Duration and Rate Total</td>
                <td class="!font-bold">${convertMinutesToHoursAndMinutes(totalDuration)}</td>
                <td colspan="2" class="!font-bold">PHP ${validateAndFormatNumber(totalRate, 2)}</td>
                </tr>`;

                tfoot.innerHTML += rowFoorHtml;
                hideLoading();
                scrollToTop();
        })
        .catch(error => {
            console.log('Fetch error:', error);
            showToast('An error occurred. Please try again.', 'error');
            hideLoading();
        });
    });



    
        const ctx = document.getElementById('financialChart').getContext('2d');
        const myChart = new Chart(ctx, getConfig('monthly', devicesData));
});
</script>