<?php

namespace App\Http\Controllers;

use App\Enums\DeviceStatusEnum;
use App\Enums\LogEntityEnum;
use App\Enums\LogTypeEnum;
use App\Models\Device;
use App\Models\DeviceTime;
use App\Models\DeviceTimeTransactions;
use App\Models\RptDeviceTimeTransactions;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DeviceMangementController extends Controller
{
    public function GetDevices(Request $request)
    {
        $devices = Device::with(['deviceStatus', 'increments' => function ($query) {
            $query->where('Active', true);
        }])->get();

        // Check if the request expects a JSON response
        if ($request->expectsJson()) {
            return response()->json(['devices' => $devices]);
        }

        // Otherwise, return the view
        return view('devicemanagement', compact('devices'));
    }

    public function GetDeviceDetails($id)
    {
        $device = Device::with('deviceStatus')->findOrFail($id);
        $baseTime = DeviceTime::where('DeviceID', $id)->where('TimeTypeID', DeviceTime::TIME_TYPE_BASE)->first();
        $deviceTimes = DeviceTime::where('DeviceID', $id)->where('TimeTypeID', DeviceTime::TIME_TYPE_INCREMENT)->get();

        $deviceTimeTransactions = DeviceTimeTransactions::where('DeviceID', $id)->where('Active', true)->get();

        $totalTime = $deviceTimeTransactions->sum('Duration');
        $totalRate = $deviceTimeTransactions->sum('Rate');

        $rptDeviceTimeTransactions = RptDeviceTimeTransactions::where('DeviceID', $id)
            ->whereDate('Time', Carbon::now())
            ->with('creator')
            ->get();

        return view('device-detail', compact('device', 'baseTime', 'deviceTimes', 'deviceTimeTransactions', 'totalTime', 'totalRate', 'rptDeviceTimeTransactions'));
    }

    public function UpdateDeviceOperationDate($id)
    {
        $device = Device::findOrFail($id);
        $device->DeviceStatusID = DeviceStatusEnum::INACTIVE_ID;
        $device->OperationDate = Carbon::now();
        $device->save();

        LoggingController::InsertLog(LogEntityEnum::DEVICE, $device->DeviceID, 'Deployment', LogTypeEnum::INFO, auth()->id());

        return redirect()->route('device.detail', $id)->with('status', 'Device deployed successfully');
    }

    public function InsertDeviceDetails(Request $request)
    {
        $validatedData = $request->validate([
            'DeviceName' => 'required|string|max:255',
            'IPAddress' => 'required|ip',
            'DeviceStatusID' => 'required|integer',
        ]);

        $device = new Device();
        try {
            $device->DeviceName = $validatedData['DeviceName'];
            $device->ExternalDeviceName = $validatedData['DeviceName'];
            $device->IPAddress = $validatedData['IPAddress'];
            $device->WatchdogInterval = 30;
            $device->RemainingTimeNotification = 0;
            $device->DeviceStatusID = $validatedData['DeviceStatusID'];
            $device->save();

            LoggingController::InsertLog(LogEntityEnum::DEVICE, $device->DeviceID, 'Device registered', LogTypeEnum::INFO, auth()->id());

            return response()->json(['success' => true, 'message' => 'Device registered successfully.', 'device_id' => $device->DeviceID], 201);
        } catch (\Exception $e) {
            LoggingController::InsertLog(LogEntityEnum::DEVICE, $device->DeviceID, 'Error inserting device: ' . $e, LogTypeEnum::ERROR, auth()->id());
            return response()->json(['success' => false, 'message' => 'Failed to register device.', 'error' => $e->getMessage()], 500);
        }
    }

    public function UpdateDeviceDetails(Request $request)
    {
        $validatedData = $request->validate([
            'DeviceID' => 'required|integer',
            'DeviceName' => 'required|string|max:255',
            'IPAddress' => 'required|ip',
            'DeviceStatusID' => 'required|integer',
        ]);

        $device = Device::with('deviceStatus')->findOrFail($validatedData['DeviceID']);
        try {
            $device->DeviceName = $validatedData['DeviceName'];
            $device->ExternalDeviceName = $validatedData['DeviceName'];
            $device->DeviceStatusID = DeviceStatusEnum::PENDING_ID;
            $device->IPAddress = $validatedData['IPAddress'];
            $device->save();

            LoggingController::InsertLog(LogEntityEnum::DEVICE, $device->DeviceID, 'Device info update through AP', LogTypeEnum::INFO, null);

            return response()->json(['success' => true, 'message' => 'Device updated successfully.', 'device_id' => $device->DeviceID], 201);
        } catch (\Exception $e) {
            LoggingController::InsertLog(LogEntityEnum::DEVICE, $device->DeviceID, 'Error updating device: ' . $e, LogTypeEnum::ERROR, auth()->id());
            return response()->json(['success' => false, 'message' => 'Failed to update device.', 'error' => $e->getMessage()], 500);
        }
    }

    public function DeleteDevice($id)
    {
        $device = Device::find($id);

        if (!$device) {
            return response()->json(['success' => false, 'message' => 'Device not found.'], 404);
        }

        $deviceIpAddress = $device->IPAddress;

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->delete("http://$deviceIpAddress/api/reset");

            if ($response->getStatusCode() == 200) {

                LoggingController::InsertLog(LogEntityEnum::DEVICE, $device->DeviceID, 'Deleted device', LogTypeEnum::INFO, auth()->id());
                $device->delete();
                return response()->json(['success' => true, 'message' => 'Device deleted successfully.']);
            }
            return response()->json(['success' => false, 'message' => 'Failed to reset the device.'], $response->getStatusCode());
        } catch (\Exception $e) {
            LoggingController::InsertLog(LogEntityEnum::DEVICE, $device->DeviceID, 'Error deleting device: ' . $e, LogTypeEnum::ERROR, auth()->id());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function DeviceTest($id)
    {
        $device = Device::find($id);

        if (!$device) {
            return response()->json(['success' => false, 'message' => 'Device not found.'], 404);
        }

        $deviceIpAddress = $device->IPAddress;

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get("http://$deviceIpAddress/api/test");

            if ($response->getStatusCode() == 200) {

                LoggingController::InsertLog(LogEntityEnum::DEVICE, $device->DeviceID, 'Tested device', LogTypeEnum::INFO, auth()->id());
                return response()->json(['success' => true, 'message' => 'Device tested successfully.']);
            }
            return response()->json(['success' => false, 'message' => 'Failed to test the device.'], $response->getStatusCode());
        } catch (\Exception $e) {
            LoggingController::InsertLog(LogEntityEnum::DEVICE, $device->DeviceID, 'Error testing device: ' . $e, LogTypeEnum::ERROR, auth()->id());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function DisableDevice($id)
    {
        $device = Device::find($id);

        if (!$device) {
            return response()->json(['success' => false, 'message' => 'Device not found.'], 404);
        }

        $deviceIpAddress = $device->IPAddress;

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get("http://$deviceIpAddress/api/disable");

            if ($response->getStatusCode() == 200) {
                $device->DeviceStatusID = DeviceStatusEnum::DISABLED_ID;
                $device->save();

                LoggingController::InsertLog(LogEntityEnum::DEVICE, $device->DeviceID, 'Disabled device', LogTypeEnum::INFO, auth()->id());

                return response()->json(['success' => true, 'message' => 'Device disabled successfully']);
            }

            return response()->json(['success' => false, 'message' => 'Failed to disable the device'], $response->getStatusCode());
        } catch (\Exception $e) {
            LoggingController::InsertLog(LogEntityEnum::DEVICE, $device->DeviceID, 'Error disabling device: ' . $e->getMessage(), LogTypeEnum::ERROR, auth()->id());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function EnableDevice($id)
    {
        $device = Device::find($id);

        if (!$device) {
            return response()->json(['success' => false, 'message' => 'Device not found.'], 404);
        }

        $deviceIpAddress = $device->IPAddress;

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get("http://$deviceIpAddress/api/enable");

            if ($response->getStatusCode() == 200) {
                $device->DeviceStatusID = DeviceStatusEnum::INACTIVE_ID;
                $device->save();

                LoggingController::InsertLog(LogEntityEnum::DEVICE, $device->DeviceID, 'Enabled device', LogTypeEnum::INFO, auth()->id());

                return response()->json(['success' => true, 'message' => 'Device enabled successfully']);
            }

            return response()->json(['success' => false, 'message' => 'Failed to enable the device'], $response->getStatusCode());
        } catch (\Exception $e) {
            LoggingController::InsertLog(LogEntityEnum::DEVICE, $device->DeviceID, 'Error enabling device: ' . $e->getMessage(), LogTypeEnum::ERROR, auth()->id());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function UpdateDeviceName(Request $request)
    {
        $request->validate([
            'external_device_id' => 'required|integer|exists:devices,DeviceID',
            'external_device_name' => 'required|string|max:255',
        ]);

        $device = Device::findOrFail($request->external_device_id);
        $deviceIpAddress = $device->IPAddress;

        $orginalName = $device->ExternalDeviceName;
        $newDeviceName = $request->external_device_name;

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post("http://{$deviceIpAddress}/api/updateDeviceName", [
                'body' => $newDeviceName,
                'headers' => [
                    'Content-Type' => 'text/plain',
                ],
            ]);

            if ($response->getStatusCode() == 200) {


                $device->ExternalDeviceName = $newDeviceName;
                $device->save();

                LoggingController::InsertLog(LogEntityEnum::DEVICE, $device->DeviceID, 'Changed device name from ' . $orginalName . ' to ' . $device->ExternalDeviceName, LogTypeEnum::INFO, auth()->id());

                return response()->json(['success' => true, 'message' => 'Device name updated successfully.']);
            }
            return response()->json(['success' => false, 'message' => 'Failed to update the device name.'], $response->getStatusCode());
        } catch (\Exception $e) {
            LoggingController::InsertLog(LogEntityEnum::DEVICE, $device->DeviceID, 'Error updating device name: ' . $e, LogTypeEnum::ERROR, auth()->id());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function UpdateWatchdogInterval(Request $request)
    {
        $request->validate([
            'deviceId' => 'required|integer',
            'watchdogInterval' => 'required|integer|min:1'
        ]);

        $device = Device::findOrFail($request->deviceId);
        $deviceIpAddress = $device->IPAddress;
        $originalWatchdogInterval = $device->WatchdogInterval;
        $newWatchdogInterval = $request->watchdogInterval;

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post("http://{$deviceIpAddress}/api/setWatchdogInterval", [
                'body' => $newWatchdogInterval,
                'headers' => [
                    'Content-Type' => 'text/plain',
                ],
            ]);

            Log::info('Sent watchdog interval:', ['interval' => $newWatchdogInterval]);

            if ($response->getStatusCode() == 200) {
                // Update the interval only if the device update was successful
                $device->WatchdogInterval = $newWatchdogInterval;
                $device->save();

                LoggingController::InsertLog(LogEntityEnum::DEVICE, $device->DeviceID, 'Changed device watchdog interval from ' . $originalWatchdogInterval . ' to ' . $newWatchdogInterval, LogTypeEnum::INFO, auth()->id());

                return response()->json(['success' => true, 'message' => 'Device watchdog interval updated successfully.']);
            }
            return response()->json(['success' => false, 'message' => 'Failed to update the device watchdog interval.'], $response->getStatusCode());
        } catch (\Exception $e) {
            LoggingController::InsertLog(LogEntityEnum::DEVICE, $device->DeviceID, 'Error updating device watchdog interval: ' . $e->getMessage(), LogTypeEnum::ERROR, auth()->id());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function UpdateRemainingTimeNotification(Request $request)
    {
        $request->validate([
            'deviceId' => 'required|integer',
            'remainingTime' => 'required|integer|min:1'
        ]);

        $device = Device::findOrFail($request->deviceId);
        $originalRemainingTime = $device->RemainingTimeNotification;
        $newRemainingTime = $request->remainingTime;

        try {
            // Update the interval only if the device update was successful
            $device->RemainingTimeNotification = $newRemainingTime;
            $device->save();

            LoggingController::InsertLog(LogEntityEnum::DEVICE, $device->DeviceID, 'Changed device remaining time notification from ' . $originalRemainingTime . ' to ' . $newRemainingTime, LogTypeEnum::INFO, auth()->id());

            return response()->json(['success' => true, 'message' => 'Device remaining time notification updated successfully.']);
        } catch (\Exception $e) {
            LoggingController::InsertLog(LogEntityEnum::DEVICE, $device->DeviceID, 'Error updating device remaining time notification: ' . $e->getMessage(), LogTypeEnum::ERROR, auth()->id());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
