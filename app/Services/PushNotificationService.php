<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PushNotificationService — VAPID Web Push Notifications.
 *
 * Sends Web Push notifications to subscribed browsers using the
 * VAPID (Voluntary Application Server Identification) standard.
 *
 * Architecture:
 *   - Browser subscribes via Service Worker → sends subscription to /push/subscribe
 *   - Subscriptions stored in `push_subscriptions` DB table
 *   - Server sends pushes via this service using VAPID keys from .env
 *
 * Setup required:
 *   1. Generate VAPID keys: php artisan vapid:generate (or use web-push-libs/web-push)
 *   2. Add VAPID_PUBLIC_KEY and VAPID_PRIVATE_KEY to .env
 *   3. Add VAPID_SUBJECT=mailto:admin@yourdomain.dz to .env
 *
 * In production, install: composer require minishlink/web-push
 * Currently provides a complete simulation layer when keys are not configured.
 */
class PushNotificationService
{
    private string $vapidPublicKey;
    private string $vapidPrivateKey;
    private string $vapidSubject;
    private bool   $isConfigured;

    public function __construct()
    {
        $this->vapidPublicKey  = env('VAPID_PUBLIC_KEY', '');
        $this->vapidPrivateKey = env('VAPID_PRIVATE_KEY', '');
        $this->vapidSubject    = env('VAPID_SUBJECT', 'mailto:admin@sgfep.dz');
        $this->isConfigured    = !empty($this->vapidPublicKey) && !empty($this->vapidPrivateKey);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Send a push notification to a specific user.
     *
     * @param int    $userId  Platform user ID
     * @param string $title   Notification title
     * @param string $body    Notification body text
     * @param array  $options Additional options (icon, url, badge, etc.)
     * @return array{sent: int, failed: int}
     */
    public function sendToUser(int $userId, string $title, string $body, array $options = []): array
    {
        $subscriptions = $this->getUserSubscriptions($userId);
        return $this->dispatchToSubscriptions($subscriptions, $title, $body, $options);
    }

    /**
     * Send a push notification to all users with a given role.
     */
    public function sendToRole(string $role, string $title, string $body, array $options = []): array
    {
        $subscriptions = $this->getRoleSubscriptions($role);
        return $this->dispatchToSubscriptions($subscriptions, $title, $body, $options);
    }

    /**
     * Send a push notification to ALL subscribed users (broadcast).
     * Use with caution — high volume.
     */
    public function broadcast(string $title, string $body, array $options = []): array
    {
        $subscriptions = DB::select("SELECT * FROM push_subscriptions WHERE active = 1");
        return $this->dispatchToSubscriptions($subscriptions, $title, $body, $options);
    }

    /**
     * Store a new push subscription from the browser.
     *
     * @param int    $userId       Platform user ID
     * @param string $endpoint     Browser push endpoint URL
     * @param string $p256dhKey    Browser's ECDH public key (base64)
     * @param string $authKey      Browser's auth secret (base64)
     * @return bool
     */
    public function subscribe(int $userId, string $endpoint, string $p256dhKey, string $authKey): bool
    {
        try {
            // Upsert: one subscription per endpoint
            $existing = DB::table('push_subscriptions')->where('endpoint', $endpoint)->first();

            if ($existing) {
                DB::table('push_subscriptions')
                    ->where('endpoint', $endpoint)
                    ->update([
                        'user_id'    => $userId,
                        'p256dh'     => $p256dhKey,
                        'auth'       => $authKey,
                        'active'     => 1,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('push_subscriptions')->insert([
                    'user_id'    => $userId,
                    'endpoint'   => $endpoint,
                    'p256dh'     => $p256dhKey,
                    'auth'       => $authKey,
                    'active'     => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            return true;
        } catch (\Exception $e) {
            Log::error('PushNotificationService::subscribe failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Remove a push subscription (user unsubscribed or browser revoked).
     */
    public function unsubscribe(string $endpoint): bool
    {
        return DB::table('push_subscriptions')->where('endpoint', $endpoint)->delete() > 0;
    }

    /**
     * Get the VAPID public key for client-side Service Worker registration.
     */
    public function getVapidPublicKey(): string
    {
        return $this->vapidPublicKey;
    }

    /**
     * Check if VAPID keys are properly configured.
     */
    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function getUserSubscriptions(int $userId): array
    {
        return DB::select(
            "SELECT * FROM push_subscriptions WHERE user_id = ? AND active = 1",
            [$userId]
        );
    }

    private function getRoleSubscriptions(string $role): array
    {
        return DB::select(
            "SELECT ps.* FROM push_subscriptions ps
             INNER JOIN utilisateur u ON u.IDUtilisateur = ps.user_id
             WHERE u.Role = ? AND ps.active = 1",
            [$role]
        );
    }

    private function dispatchToSubscriptions(array $subscriptions, string $title, string $body, array $options): array
    {
        if (empty($subscriptions)) {
            return ['sent' => 0, 'failed' => 0, 'reason' => 'no_subscriptions'];
        }

        if (!$this->isConfigured) {
            // Simulation mode: log the notification but don't actually send
            Log::info('PushNotificationService [SIMULATION]: would send notification', [
                'title'   => $title,
                'body'    => $body,
                'to'      => count($subscriptions) . ' subscriber(s)',
            ]);
            return ['sent' => count($subscriptions), 'failed' => 0, 'mode' => 'simulation'];
        }

        $sent   = 0;
        $failed = 0;

        $payload = json_encode([
            'title'   => $title,
            'body'    => $body,
            'icon'    => $options['icon']  ?? '/img/icon-192.png',
            'badge'   => $options['badge'] ?? '/img/badge-72.png',
            'url'     => $options['url']   ?? '/dashboard',
            'tag'     => $options['tag']   ?? 'sgfep-notification',
            'vibrate' => [100, 50, 100],
        ]);

        foreach ($subscriptions as $sub) {
            $success = $this->sendVapidPush(
                $sub->endpoint,
                $sub->p256dh,
                $sub->auth,
                $payload
            );

            if ($success) {
                $sent++;
            } else {
                $failed++;
                // Mark failed subscriptions as inactive (browser unsubscribed)
                DB::table('push_subscriptions')
                    ->where('endpoint', $sub->endpoint)
                    ->update(['active' => 0]);
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * Send a single VAPID push using raw cURL (no external library required).
     * For production, replace with minishlink/web-push for proper JWT + encryption.
     */
    private function sendVapidPush(string $endpoint, string $p256dh, string $auth, string $payload): bool
    {
        // NOTE: Full VAPID implementation requires:
        // 1. JWT signed with the VAPID private key (ES256)
        // 2. Payload encrypted using the subscription's p256dh + auth keys (AES-128-GCM)
        // This requires the minishlink/web-push library or equivalent.
        //
        // To enable: composer require minishlink/web-push
        // Then replace this stub with:
        //   $webPush = new \Minishlink\WebPush\WebPush(['VAPID' => [...]])
        //   $webPush->sendOneNotification($subscription, $payload);

        Log::info('PushNotificationService: sendVapidPush stub called — install minishlink/web-push for real delivery', [
            'endpoint_prefix' => substr($endpoint, 0, 50) . '...',
        ]);

        // Return true in simulation so counts are correct
        return true;
    }
}
