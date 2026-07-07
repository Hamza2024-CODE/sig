<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class SecurityEventTriggered
{
    use Dispatchable, SerializesModels;

    public string $ipAddress;
    public string $userAgent;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $eventType,
        public string $severity, // 'info', 'warning', 'danger', 'critical'
        public $user = null,
        public ?string $description = null,
        public array $metadata = []
    ) {
        // Capture context synchronously before job goes to queue
        $this->ipAddress = request()->ip() ?? '127.0.0.1';
        $this->userAgent = request()->userAgent() ?? 'Unknown';

        // Capture hardware specs from cookie if present
        $specsCookie = request()->cookie('device_hardware_specs');
        if ($specsCookie) {
            $decoded = json_decode(rawurldecode($specsCookie), true);
            if (is_array($decoded)) {
                $metadata['hardware_specs'] = $decoded;
            }
        }
        $this->metadata = $metadata;
    }
}
