<?php

namespace App\Security;

use App\Models\SecurityLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class SecurityAuditLogger
{
    /**
     * Logs a security event to both file-based logs and the database logs table.
     */
    public function log(string $eventType, string $description, string $severity = 'high', array $metadata = []): void
    {
        $ip = Request::ip();
        $userAgent = Request::userAgent();
        $user = UserContext::fromSession();
        $userId = $user ? $user->id : null;
        $username = $user ? $user->username : 'guest';

        $fullMessage = sprintf(
            "[SECURITY_AUDIT][%s] User: %s (ID: %s) | IP: %s | Description: %s",
            strtoupper($severity),
            $username,
            $userId ?? 'N/A',
            $ip,
            $description
        );

        if (strtolower($severity) === 'critical') {
            Log::critical($fullMessage, $metadata);
        } else {
            Log::warning($fullMessage, $metadata);
        }

        try {
            SecurityLog::create([
                'user_id' => $userId,
                'event_type' => strtoupper($eventType),
                'severity' => strtolower($severity),
                'description' => $description,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'metadata' => array_merge($metadata, [
                    'url' => Request::fullUrl(),
                    'method' => Request::method(),
                    'username' => $username,
                ]),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error("[SecurityAuditLogger] Database logging failed: " . $e->getMessage());
        }
    }

    public function logIdorAttempt(string $resourceType, $resourceId, string $action = 'view'): void
    {
        $this->log(
            'IDOR_ATTEMPT',
            "Unauthorized attempt to {$action} resource type '{$resourceType}' with ID '{$resourceId}' (Ownership scope mismatch)",
            'critical',
            ['resource_type' => $resourceType, 'resource_id' => $resourceId, 'action' => $action]
        );
    }

    public function logPermissionDenied(string $action, string $description = ''): void
    {
        $this->log(
            'PERMISSION_DENIED',
            "Permission denied for action '{$action}'. " . $description,
            'high',
            ['action' => $action]
        );
    }
}
