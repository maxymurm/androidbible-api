<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationController extends Controller
{
    /**
     * Send a sync nudge push notification to all user's other devices.
     * Used as fallback when WebSocket is not connected.
     */
    public function sendSyncNudge(Request $request): JsonResponse
    {
        $devices = Device::where('user_id', $request->user()->id)
            ->where('device_id', '!=', $request->header('X-Device-Id'))
            ->whereNotNull('push_token')
            ->get();

        $sent = 0;
        foreach ($devices as $device) {
            try {
                if ($device->platform === 'android') {
                    $this->sendFcm($device->push_token, [
                        'type' => 'sync_available',
                        'user_id' => (string) $request->user()->id,
                    ]);
                } elseif ($device->platform === 'ios') {
                    $this->sendApns($device->push_token, [
                        'type' => 'sync_available',
                        'user_id' => (string) $request->user()->id,
                    ]);
                }
                $sent++;
            } catch (\Exception $e) {
                Log::warning('Push notification failed', [
                    'device_id' => $device->device_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'data' => [
                'devices_notified' => $sent,
                'total_devices' => $devices->count(),
            ],
        ]);
    }

    /**
     * Update push token for a device.
     */
    public function updateToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['required', 'string'],
            'push_token' => ['required', 'string'],
            'platform' => ['required', 'string', 'in:android,ios'],
        ]);

        $device = Device::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'device_id' => $validated['device_id'],
            ],
            [
                'push_token' => $validated['push_token'],
                'platform' => $validated['platform'],
            ]
        );

        return response()->json(['data' => $device]);
    }

    private function sendFcm(string $token, array $data): void
    {
        $serverKey = config('services.fcm.server_key');
        if (!$serverKey) return;

        Http::withHeaders([
            'Authorization' => 'key=' . $serverKey,
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'to' => $token,
            'data' => $data,
            'priority' => 'high',
            'content_available' => true,
        ]);
    }

    private function sendApns(string $token, array $data): void
    {
        // APNs integration via Laravel notification system
        // For production, use a proper APNs library
        Log::info('APNs push placeholder', ['token' => $token, 'data' => $data]);
    }
}
