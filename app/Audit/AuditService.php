<?php

namespace App\Audit;

class AuditService
{
    protected string $logPath;

    public function __construct()
    {
        $this->logPath = dirname(dirname(__DIR__)) . '/logs/audit.json';
        if (!file_exists(dirname($this->logPath))) {
            mkdir(dirname($this->logPath), 0777, true);
        }
    }

    /**
     * Record a data modification event
     */
    public function log(
        string $user, 
        string $action, 
        string $entity, 
        string $recordId, 
        ?array $oldValue = null, 
        ?array $newValue = null
    ): void {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user' => $user,
            'action' => strtoupper($action), // CREATE, UPDATE, DELETE
            'entity' => $entity, // Table name
            'record_id' => $recordId,
            'changes' => [
                'before' => $oldValue,
                'after' => $newValue
            ],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'CLI'
        ];

        $currentData = [];
        if (file_exists($this->logPath)) {
            $currentData = json_decode(file_get_contents($this->logPath), true) ?: [];
        }

        $currentData[] = $logEntry;
        file_put_contents($this->logPath, json_encode($currentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
