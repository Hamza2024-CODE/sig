<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use PDO;

class NotificationController extends Controller
{
    protected $db;

    public function __construct()
    {
        if (app()->runningInConsole()) { return; }
        $this->db = new \App\Core\LaravelDbAdapter();
    }

    // Fetch notifications
    public function fetch()
    {
        $userId = (int)session('user')['id'];

        try {
            $stmt = $this->db->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 15
            ");
            $stmt->execute([$userId]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Count unread
            $stmtCount = $this->db->prepare("
                SELECT COUNT(*) FROM notifications 
                WHERE user_id = ? AND is_read = 0
            ");
            $stmtCount->execute([$userId]);
            $unreadCount = $stmtCount->fetchColumn();

            return $this->json([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unreadCount
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Error fetching notifications: ' . $e->getMessage()], 500);
        }
    }

    // Mark notification as read
    public function markAsRead()
    {
        if (request()->isMethod('post')) {
            $userId = (int)session('user')['id'];
            $notifId = (int)(request()->all()['id'] ?? 0);

            try {
                if ($notifId > 0) {
                    // Mark specific
                    $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
                    $stmt->execute([$notifId, $userId]);
                } else {
                    // Mark all as read
                    $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
                    $stmt->execute([$userId]);
                }

                return $this->json(['success' => true]);
            } catch (\Exception $e) {
                return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
        } else {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
        }
    }
}
