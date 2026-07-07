<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PushNotificationService;

/**
 * PushController — HTTP endpoints for VAPID push subscription management.
 */
class PushController extends Controller
{
    public function __construct(private PushNotificationService $push) {}

    /** GET /push/vapid-key — returns the server's VAPID public key for Service Worker registration */
    public function vapidPublicKey()
    {
        return response()->json([
            'vapid_public_key' => $this->push->getVapidPublicKey(),
            'configured'       => $this->push->isConfigured(),
        ]);
    }

    /** POST /push/subscribe — store a new browser push subscription */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'url', 'max:500'],
            'p256dh'   => ['required', 'string', 'max:500'],
            'auth'     => ['required', 'string', 'max:100'],
        ]);

        $userId = (int) session('user_id', 0);
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 401);
        }

        $success = $this->push->subscribe(
            $userId,
            $validated['endpoint'],
            $validated['p256dh'],
            $validated['auth']
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'تم تسجيل الاشتراك بنجاح.' : 'فشل تسجيل الاشتراك.',
        ]);
    }

    /** POST /push/unsubscribe — remove a browser push subscription */
    public function unsubscribe(Request $request)
    {
        $endpoint = $request->input('endpoint', '');
        if (empty($endpoint)) {
            return response()->json(['success' => false, 'message' => 'endpoint مطلوب'], 422);
        }

        $success = $this->push->unsubscribe($endpoint);
        return response()->json(['success' => $success]);
    }
}
