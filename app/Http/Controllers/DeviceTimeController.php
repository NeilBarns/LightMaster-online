<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceTime;
use App\Models\DeviceTimeTransactions;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Enums\DeviceStatusEnum;
use App\Enums\LogEntityEnum;
use App\Enums\LogTypeEnum;
use App\Enums\StoppageTypeEnum;
use App\Enums\TimeTransactionTypeEnum;
use App\Models\RptDeviceTimeTransactions;

class DeviceTimeController extends Controller
{
    public function InsertDeviceIncrement(Request $request)
    {
        $request->validate([
            'time' => 'required|integer',
            'rate' => 'required|numeric',
            'device_id' => 'required|integer|exists:Devices,DeviceID',
        ]);

        $device = Device::findOrFail($request->device_id);

        DeviceTime::create([
            'DeviceID' => $request->device_id,
            'Time' => $request->time,
            'Rate' => $request->rate,
            'TimeTypeID' => DeviceTime::TIME_TYPE_INCREMENT,
            'Active' => true
        ]);

        LoggingController::InsertLog(
            LogEntityEnum::DEVICE_TIME,
            $request->device_id,
            'Added increment ' . $request->time . ' with rate ' . $request->rate .  ' for device: ' . $device->DeviceName,
            LogTypeEnum::INFO,
            auth()->id()
        );

        return redirect()->back()->with('success', 'Time increment added successfully.');
    }

    public function InsertDeviceBase(Request $request)
    {
        $request->validate([
            'base_time' => 'required|integer',
            'base_rate' => 'required|numeric',
            'device_id' => 'required|integer|exists:Devices,DeviceID',
        ]);

        $device = Device::findOrFail($request->device_id);

        // Check if a base time already exists for the device
        $baseTime = DeviceTime::where('DeviceID', $request->device_id)
            ->where('TimeTypeID', DeviceTime::TIME_TYPE_BASE)
            ->first();

        if ($baseTime) {
            // Update the existing base time
            $baseTime->update([
                'Time' => $request->base_time,
                'Rate' => $request->base_rate,
            ]);


            LoggingController::InsertLog(
                LogEntityEnum::DEVICE_TIME,
                $request->device_id,
                'Updated base time ' . $baseTime->Time . ' to ' . $request->base_time . ' and base rate ' . $baseTime->Rate . ' to ' . $request->base_rate . ' for device: ' . $device->DeviceName,
                LogTypeEnum::INFO,
                auth()->id()
            );
        } else {
            // Create a new base time
            DeviceTime::create([
                'DeviceID' => $request->device_id,
                'Time' => $request->base_time,
                'Rate' => $request->base_rate,
                'TimeTypeID' => DeviceTime::TIME_TYPE_BASE,
            ]);

            LoggingController::InsertLog(
                LogEntityEnum::DEVICE_TIME,
                $request->device_id,
                'Added base time: ' . $request->base_time . ' and base rate: ' . $request->base_rate . ' for device: ' . $device->DeviceName,
                LogTypeEnum::INFO,
                auth()->id()
            );
        }

        return redirect()->back()->with('success', 'Base time and rate added/updated successfully.');
    }

    public function UpdateDeviceIncrement(Request $request, $id)
    {
        $request->validate([
            'time' => 'required|integer',
            'rate' => 'required|numeric',
            'device_id' => 'required|integer|exists:Devices,DeviceID',
        ]);

        $device = Device::findOrFail($request->device_id);
        $deviceTime = DeviceTime::findOrFail($id);

        LoggingController::InsertLog(
            LogEntityEnum::DEVICE_TIME,
            $id,
            'Updated increment time ' . $deviceTime->Time . ' to ' . $request->time . ' and base rate ' . $deviceTime->Rate . ' to ' . $request->rate . ' for device: ' . $device->DeviceName,
            LogTypeEnum::INFO,
            auth()->id()
        );

        $deviceTime->update([
            'Time' => $request->time,
            'Rate' => $request->rate,
        ]);

        return redirect()->back()->with('success', 'Time increment updated successfully.');
    }

    public function UpdateDeviceIncrementStatus(Request $request, $id)
    {
        try {
            $device = Device::findOrFail($request->device_id);
            $deviceTime = DeviceTime::findOrFail($id);

            LoggingController::InsertLog(
                LogEntityEnum::DEVICE_TIME,
                $id,
                'Disabled increment with time ' . $deviceTime->Time . ' and base rate ' . $deviceTime->Rate . ' for device: ' . $device->DeviceName,
                LogTypeEnum::INFO,
                auth()->id()
            );

            $deviceTime->update([
                'Active' => $request->incrementStatus,
            ]);

            return response()->json(['success' => true, 'message' => 'Time increment disabled successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function DeleteDeviceIncrement($id)
    {
        $deviceTime = DeviceTime::findOrFail($id);
        $device = Device::findOrFail($deviceTime->DeviceID);

        LoggingController::InsertLog(
            LogEntityEnum::DEVICE_TIME,
            $id,
            'Deleted increment time ' . $deviceTime->Time . ' with rate ' . $deviceTime->Rate . ' for device: ' . $device->DeviceName,
            LogTypeEnum::INFO,
            auth()->id()
        );

        $deviceTime->delete();

        return response()->json(['success' => 'Time increment deleted successfully.']);
    }

    public function StartDeviceTime($id)
    {
        try {
            $device = Device::findOrFail($id);
            $officialStartTime =   Carbon::now();

            // Fetch the base time
            $baseTime = DeviceTime::where('DeviceID', $id)->where('TimeTypeID', DeviceTime::TIME_TYPE_BASE)->first();

            if (!$baseTime) {
                return response()->json(['error' => 'Base time not configured for this device.'], 400);
            }

            $deviceIpAddress = $device->IPAddress;

            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', "http://$deviceIpAddress/api/span", [
                'query' => [
                    'time' => $baseTime->Time * 60,
                ],
                //'timeout' => 5, // Optional: Set a timeout in seconds
            ]);

            if ($response->getStatusCode() == 200) {

                // Start transaction
                $transaction = DeviceTimeTransactions::create([
                    'DeviceID' => $device->DeviceID,
                    'TransactionType' => TimeTransactionTypeEnum::START,
                    'StartTime' => $officialStartTime,
                    'Duration' => $baseTime->Time,
                    'Rate' => $baseTime->Rate,
                    'Active' => true,
                    'CreatedByUserId' => auth()->id()
                ]);

                // Update device status to running
                $device->DeviceStatusID = DeviceStatusEnum::RUNNING_ID;
                $device->save();

                // Calculate end time and total time
                $startTime = Carbon::parse($transaction->StartTime);
                $endTime = $startTime->clone()->addMinutes($baseTime->Time);
                $totalTime = $baseTime->Time;
                $totalRate = $baseTime->Rate;

                //Log in RptDeviceTimeTransactions table
                $rptTransactions = RptDeviceTimeTransactions::create([
                    'DeviceTimeTransactionsID' => $transaction->TransactionID,
                    'DeviceID' => $device->DeviceID,
                    'TransactionType' => TimeTransactionTypeEnum::START,
                    'Time' => $officialStartTime,
                    'Duration' => $baseTime->Time,
                    'Rate' => $baseTime->Rate,
                    'CreatedByUserId' => auth()->id()
                ]);

                return response()->json([
                    'success' => 'Device time started successfully.',
                    'startTime' => $startTime->format('Y-m-d H:i:s'),
                    'endTime' => $endTime->format('Y-m-d H:i:s'),
                    'totalTime' => $totalTime,
                    'totalRate' => $totalRate,
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Failed to start time to the device.'], $response->getStatusCode());
        } catch (\Exception $e) {
            LoggingController::InsertLog(LogEntityEnum::DEVICE, $id, 'Error starting device time: ' . $e, LogTypeEnum::ERROR, auth()->id());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function EndDeviceTime($id)
    {
        try {
            $device = Device::findOrFail($id);
            $officialStartTime = Carbon::now();

            $deviceIpAddress = $device->IPAddress;

            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', "http://$deviceIpAddress/api/stop");

            if ($response->getStatusCode() == 200) {

                $calculabletransactions = DeviceTimeTransactions::where('DeviceID', $id)
                    ->where('Active', true)
                    ->get(['Duration', 'Rate']);
                $totalDuration = $calculabletransactions->sum('Duration');
                $totalRate = $calculabletransactions->sum('Rate');

                $transaction = DeviceTimeTransactions::where('DeviceID', $id)
                    ->where('TransactionType', TimeTransactionTypeEnum::START)
                    ->where('Active', true)->first();

                // Set all active transactions to inactive
                DeviceTimeTransactions::where('DeviceID', $id)->update(['Active' => false]);

                //Log in RptDeviceTimeTransactions table
                if ($transaction) {
                    $transaction->update([
                        'EndTime' => $officialStartTime,
                        'StoppageType' => StoppageTypeEnum::MANUAL
                    ]);

                    $rptTransactions = RptDeviceTimeTransactions::create([
                        'DeviceTimeTransactionsID' => $transaction->TransactionID,
                        'DeviceID' => $device->DeviceID,
                        'TransactionType' => TimeTransactionTypeEnum::END,
                        'Time' => $officialStartTime,
                        'StoppageType' => StoppageTypeEnum::MANUAL,
                        'Duration' => $totalDuration,
                        'Rate' => $totalRate,
                        'CreatedByUserId' => auth()->id()
                    ]);
                }

                // Update device status to inactive
                $device->DeviceStatusID = DeviceStatusEnum::INACTIVE_ID;
                $device->save();

                return response()->json(['success' => 'Device time ended successfully.']);
            }

            return response()->json(['success' => false, 'message' => 'Failed to reset the device.'], $response->getStatusCode());
        } catch (\Exception $e) {
            LoggingController::InsertLog(LogEntityEnum::DEVICE, $id, 'Error ending device time: ' . $e, LogTypeEnum::ERROR, auth()->id());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function ExtendDeviceTime(Request $request, $id)
    {
        try {
            $device = Device::findOrFail($id);
            $increment = $request->input('increment');
            $rate = $request->input('rate');
            $officialStartTime = Carbon::now();
            $deviceIpAddress = $device->IPAddress;

            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', "http://$deviceIpAddress/api/span", [
                'query' => [
                    'time' => $increment * 60,
                ],
                //'timeout' => 5, // Optional: Set a timeout in seconds
            ]);

            if ($response->getStatusCode() == 200) {
                // Extend transaction
                $transaction = DeviceTimeTransactions::create([
                    'DeviceID' => $device->DeviceID,
                    'TransactionType' => TimeTransactionTypeEnum::EXTEND,
                    'StartTime' => $officialStartTime,
                    'Duration' => $increment,
                    'Rate' => $rate,
                    'Active' => true,
                    'CreatedByUserId' => auth()->id()
                ]);

                $rptTransactions = RptDeviceTimeTransactions::create([
                    'DeviceTimeTransactionsID' => $transaction->TransactionID,
                    'DeviceID' => $device->DeviceID,
                    'TransactionType' => TimeTransactionTypeEnum::EXTEND,
                    'Time' => $officialStartTime,
                    'Duration' => $increment,
                    'Rate' => $rate,
                    'CreatedByUserId' => auth()->id()
                ]);

                // Fetch updated device time transactions
                $activeTransactions = DeviceTimeTransactions::where('DeviceID', $id)->where('Active', true)->get();

                // Calculate total time and rate
                $totalTime = $activeTransactions->sum('Duration');
                $totalRate = $activeTransactions->sum('Rate');

                // Calculate the start and end times
                $startTime = $activeTransactions->first() ? $activeTransactions->first()->StartTime : null;
                $endTime = $startTime ? Carbon::parse($startTime)->addMinutes($totalTime) : null;

                return response()->json([
                    'success' => 'Device time extended successfully.',
                    'totalTime' => $totalTime,
                    'totalRate' => $totalRate,
                    'startTime' => $startTime,
                    'endTime' => $endTime
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Failed to extend time to the device.'], $response->getStatusCode());
        } catch (\Exception $e) {
            LoggingController::InsertLog(LogEntityEnum::DEVICE, $id, 'Error extending device time: ' . $e, LogTypeEnum::ERROR, auth()->id());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function EndDeviceTimeAPI(Request $request)
    {
        $validatedData = $request->validate([
            'device_id' => 'required|integer',
            'from_testing' => 'required|integer',
        ]);

        $officialStartTime = Carbon::now();

        $device = Device::findOrFail($validatedData['device_id']);
        $fromTesting = $validatedData['from_testing'];

        try {

            if ($fromTesting == 0) {
                $transaction = DeviceTimeTransactions::where('DeviceID', $device->DeviceID)
                    ->where('TransactionType', TimeTransactionTypeEnum::START)
                    ->where('Active', true)->first();

                if ($transaction) {

                    $calculabletransactions = DeviceTimeTransactions::where('DeviceID', $device->DeviceID)
                        ->where('Active', true)
                        ->get(['Duration', 'Rate']);
                    $totalDuration = $calculabletransactions->sum('Duration');
                    $totalRate = $calculabletransactions->sum('Rate');

                    $transaction->update([
                        'EndTime' => $officialStartTime,
                        'StoppageType' => StoppageTypeEnum::AUTO
                    ]);

                    $rptTransactions = RptDeviceTimeTransactions::create([
                        'DeviceTimeTransactionsID' => $transaction->TransactionID,
                        'DeviceID' => $device->DeviceID,
                        'TransactionType' => TimeTransactionTypeEnum::END,
                        'Time' => $officialStartTime,
                        'StoppageType' => StoppageTypeEnum::AUTO,
                        'Duration' => $totalDuration,
                        'Rate' => $totalRate,
                        'CreatedByUserId' => 999999
                    ]);
                }

                DeviceTimeTransactions::where('DeviceID', $device->DeviceID)->update(['Active' => false]);

                $device->DeviceStatusID = DeviceStatusEnum::INACTIVE_ID;
                $device->save();
            }

            return response()->json(['success' => 'Device time ended successfully.']);
        } catch (\Exception $e) {
            LoggingController::InsertLog(LogEntityEnum::DEVICE, $device->DeviceID, 'Error ending device time API: ' . $e, LogTypeEnum::ERROR, auth()->id());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function PauseDeviceTimeAPI(Request $request)
    {
        $validatedData = $request->validate([
            'device_id' => 'required|integer',
            'remaining_time' => 'required|integer',
        ]);

        $deviceId = $request->input('device_id');
        $remainingTime = $request->input('remaining_time');
        $officialPauseTime = Carbon::now();

        try {
            // Find the device
            $device = Device::findOrFail($deviceId);

            // Retrieve active transactions for the device, excluding PAUSE transactions
            $activeTransactions = DeviceTimeTransactions::where('DeviceID', $deviceId)
                ->where('Active', true)
                ->whereNotIn('TransactionType', [TimeTransactionTypeEnum::PAUSE, TimeTransactionTypeEnum::RESUME])
                ->orderBy('TransactionID', 'desc')
                ->get();

            // Calculate total time and rate
            $totalTime = $activeTransactions->sum('Duration');
            $totalRate = $activeTransactions->sum('Rate');

            // Calculate the start time of the pause by subtracting the remaining time from the current time
            $pauseStartTime = $officialPauseTime->copy()->subSeconds($remainingTime);

            // Start the PAUSE transaction
            $transaction = DeviceTimeTransactions::create([
                'DeviceID' => $device->DeviceID,
                'TransactionType' => TimeTransactionTypeEnum::PAUSE,
                'StartTime' => $pauseStartTime,
                'Duration' => $remainingTime,
                'Rate' => 0,
                'Active' => true,
                'Reason' => 'Power interrupted',
                'CreatedByUserId' => 999999  // or system user if no auth context
            ]);

            // Update the device status to PAUSED
            $device->DeviceStatusID = DeviceStatusEnum::PAUSE_ID;
            $device->save();

            // Log this pause in the report transactions
            RptDeviceTimeTransactions::create([
                'DeviceTimeTransactionsID' => $transaction->TransactionID,
                'DeviceID' => $device->DeviceID,
                'TransactionType' => TimeTransactionTypeEnum::PAUSE,
                'Time' => $officialPauseTime,
                'Duration' => $remainingTime,
                'Rate' => 0,
                'Reason' => 'Power interrupted',
                'CreatedByUserId' => 999999
            ]);

            // Return a response with the calculated start time and remaining time
            return response()->json([
                'success' => 'Device paused successfully.',
                'pause_start_time' => $pauseStartTime->toDateTimeString(),
                'remaining_time' => $remainingTime,
                'total_rate' => $totalRate,
            ]);
        } catch (\Exception $e) {
            // Log any errors
            LoggingController::InsertLog(LogEntityEnum::DEVICE, $deviceId, 'Error pausing device: ' . $e->getMessage(), LogTypeEnum::ERROR, auth()->id());
            return response()->json(['error' => 'Error pausing device: ' . $e->getMessage()], 500);
        }
    }

    public function PauseDeviceTime(Request $request, $id)
    {
        $device = Device::findOrFail($id);
        $officialStartTime = Carbon::now();

        $deviceIpAddress = $device->IPAddress;
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get("http://$deviceIpAddress/api/pause");

            if ($response->getStatusCode() == 200) {
                // Retrieve active transactions for the device, excluding PAUSE transactions
                $activeTransactions = DeviceTimeTransactions::where('DeviceID', $id)
                    ->where('Active', true)
                    ->whereNotIn('TransactionType', [TimeTransactionTypeEnum::PAUSE, TimeTransactionTypeEnum::RESUME])
                    ->orderBy('TransactionID', 'desc')
                    ->get();


                // Calculate total time and rate
                $totalTime = $activeTransactions->sum('Duration');
                $totalRate = $activeTransactions->sum('Rate');

                $startTime = $activeTransactions->first() ? $activeTransactions->first()->StartTime : null;
                $endTime = $startTime ? Carbon::parse($startTime)->addMinutes($totalTime) : null;


                // Calculate elapsed time since the last start or extend transaction
                $lastTransaction = $activeTransactions->first();

                $elapsedTimeInSeconds = $officialStartTime->diffInSeconds($startTime);

                // Calculate remaining time in seconds
                $remainingTimeInSeconds = max(0, ($totalTime * 60) - $elapsedTimeInSeconds);

                // Start transaction
                $transaction = DeviceTimeTransactions::create([
                    'DeviceID' => $device->DeviceID,
                    'TransactionType' => TimeTransactionTypeEnum::PAUSE,
                    'StartTime' => $officialStartTime,
                    'Duration' => $remainingTimeInSeconds,
                    'Rate' => 0,
                    'Active' => true,
                    'CreatedByUserId' => auth()->id()
                ]);

                // Update device status to pause
                $device->DeviceStatusID = DeviceStatusEnum::PAUSE_ID;
                $device->save();

                $rptTransactions = RptDeviceTimeTransactions::create([
                    'DeviceTimeTransactionsID' => $transaction->TransactionID,
                    'DeviceID' => $device->DeviceID,
                    'TransactionType' => TimeTransactionTypeEnum::PAUSE,
                    'Time' => $officialStartTime,
                    'Duration' => $remainingTimeInSeconds,
                    'Rate' => 0,
                    'CreatedByUserId' => auth()->id()
                ]);

                return response()->json([
                    'success' => 'Device time paused successfully.',
                    'remaining_time' => $remainingTimeInSeconds,
                    'totalRate' => $totalRate,
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Failed to pause time on the device.'], $response->getStatusCode());
        } catch (\Exception $e) {
            LoggingController::InsertLog(LogEntityEnum::DEVICE, $id, 'Error pausing device time: ' . $e->getMessage(), LogTypeEnum::ERROR, auth()->id());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function ResumeDeviceTime(Request $request, $id)
    {
        $device = Device::findOrFail($id);
        $officialResumeTime = Carbon::now();

        $deviceIpAddress = $device->IPAddress;
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get("http://$deviceIpAddress/api/resume");

            if ($response->getStatusCode() == 200) {

                $pauseTransaction = DeviceTimeTransactions::where('DeviceID', $id)
                    ->where('TransactionType', TimeTransactionTypeEnum::PAUSE)
                    ->where('Active', true)
                    ->orderBy('TransactionID', 'desc')
                    ->first();

                // Use the remaining time stored in the pause transaction
                $remainingTimeInSeconds = $pauseTransaction->Duration;

                // Create a resume transaction
                $transaction = DeviceTimeTransactions::create([
                    'DeviceID' => $device->DeviceID,
                    'TransactionType' => TimeTransactionTypeEnum::RESUME,
                    'StartTime' => $officialResumeTime,
                    'Duration' => 0,
                    'Rate' => 0,
                    'Active' => true,
                    'CreatedByUserId' => auth()->id()
                ]);

                // Update device status to running
                $device->DeviceStatusID = DeviceStatusEnum::RUNNING_ID;
                $device->save();

                RptDeviceTimeTransactions::create([
                    'DeviceTimeTransactionsID' => $transaction->TransactionID,
                    'DeviceID' => $device->DeviceID,
                    'TransactionType' => TimeTransactionTypeEnum::RESUME,
                    'Time' => $officialResumeTime,
                    'Duration' => 0,
                    'Rate' => 0,
                    'CreatedByUserId' => auth()->id()
                ]);

                $endTime = $officialResumeTime->addSeconds($remainingTimeInSeconds);

                $activeTransactions = DeviceTimeTransactions::where('DeviceID', $id)
                    ->where('Active', true)
                    ->whereIn('TransactionType', [TimeTransactionTypeEnum::START, TimeTransactionTypeEnum::EXTEND])
                    ->get();

                $startTime = $activeTransactions->where('TransactionType', TimeTransactionTypeEnum::START)->first();
                // Calculate total time and rate
                $totalTime = $activeTransactions->sum('Duration');
                $totalRate = $activeTransactions->sum('Rate');

                return response()->json([
                    'success' => true,
                    'message' => 'Device time resumed successfully.',
                    'startTime' => $startTime->StartTime->format('Y-m-d H:i:s'),
                    'endTime' => $endTime->format('Y-m-d H:i:s'),
                    'totalTime' => $totalTime,
                    'totalRate' => $totalRate,
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Failed to resume time on the device.'], $response->getStatusCode());
        } catch (\Exception $e) {
            LoggingController::InsertLog(LogEntityEnum::DEVICE, $id, 'Error resuming device time: ' . $e, LogTypeEnum::ERROR, auth()->id());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function FreeLight(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        try {
            $device = Device::findOrFail($id);
            $reason = $request->input('reason');
            $officialStartTime =   Carbon::now();

            $deviceIpAddress = $device->IPAddress;

            $client = new \GuzzleHttp\Client();
            $response = $client->get("http://$deviceIpAddress/api/startfree");

            if ($response->getStatusCode() == 200) {

                // Start transaction
                $transaction = DeviceTimeTransactions::create([
                    'DeviceID' => $device->DeviceID,
                    'TransactionType' => TimeTransactionTypeEnum::STARTFREE,
                    'StartTime' => $officialStartTime,
                    'Duration' => 0,
                    'Rate' => 0,
                    'Active' => true,
                    'Reason' => $reason,
                    'CreatedByUserId' => auth()->id()
                ]);

                // Update device status to running
                $device->DeviceStatusID = DeviceStatusEnum::STARTFREE_ID;
                $device->save();

                //Log in RptDeviceTimeTransactions table
                $rptTransactions = RptDeviceTimeTransactions::create([
                    'DeviceTimeTransactionsID' => $transaction->TransactionID,
                    'DeviceID' => $device->DeviceID,
                    'TransactionType' => TimeTransactionTypeEnum::STARTFREE,
                    'Time' => $officialStartTime,
                    'Duration' => 0,
                    'Rate' => 0,
                    'Reason' => $reason,
                    'CreatedByUserId' => auth()->id()
                ]);

                return response()->json([
                    'success' => 'Device time started successfully.'
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Failed to start time to the device.'], $response->getStatusCode());
        } catch (\Exception $e) {
            LoggingController::InsertLog(LogEntityEnum::DEVICE, $id, 'Error starting device time: ' . $e, LogTypeEnum::ERROR, auth()->id());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function StopFreeLight($id)
    {
        try {
            $device = Device::findOrFail($id);
            $officialStartTime = Carbon::now();

            $deviceIpAddress = $device->IPAddress;

            $client = new \GuzzleHttp\Client();
            $response = $client->get("http://$deviceIpAddress/api/stopfree");

            if ($response->getStatusCode() == 200) {
                // Retrieve a single transaction
                $freeTransaction = DeviceTimeTransactions::where('DeviceID', $id)
                    ->where('Active', true)
                    ->where('TransactionType', TimeTransactionTypeEnum::STARTFREE)
                    ->orderBy('TransactionID', 'desc')
                    ->first();

                // Check if the transaction exists
                if ($freeTransaction) {
                    $freeTransaction->EndTime = $officialStartTime;
                    $freeTransaction->Active = false;
                    $freeTransaction->StoppageType = StoppageTypeEnum::MANUAL;
                    $freeTransaction->save();

                    // Update device status to inactive
                    $device->DeviceStatusID = DeviceStatusEnum::INACTIVE_ID;
                    $device->save();

                    // Log in RptDeviceTimeTransactions table
                    $rptTransactions = RptDeviceTimeTransactions::create([
                        'DeviceTimeTransactionsID' => $freeTransaction->TransactionID,
                        'DeviceID' => $id,
                        'TransactionType' => TimeTransactionTypeEnum::ENDFREE,
                        'Time' => $officialStartTime,
                        'Duration' => $officialStartTime->diffInSeconds($freeTransaction->StartTime),
                        'Rate' => 0,
                        'CreatedByUserId' => auth()->id()
                    ]);
                }

                return response()->json(['success' => true, 'message' => 'Free light stopped successfully']);
            }

            return response()->json(['success' => false, 'message' => 'Failed to stop free light'], $response->getStatusCode());
        } catch (\Exception $e) {
            LoggingController::InsertLog(LogEntityEnum::DEVICE, $id, 'Error stopping free light: ' . $e->getMessage(), LogTypeEnum::ERROR, auth()->id());
            return response()->json(['success' => false, 'message' => 'Failed to stop free light'], 500);
        }
    }
}
