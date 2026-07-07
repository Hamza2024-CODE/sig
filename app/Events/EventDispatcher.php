<?php

namespace App\Events;

class EventDispatcher
{
    private static ?EventDispatcher $instance = null;
    private array $listeners = [];

    private function __construct() {}

    public static function getInstance(): EventDispatcher
    {
        if (self::$instance === null) {
            self::$instance = new EventDispatcher();
        }
        return self::$instance;
    }

    /**
     * Bind a listener callback to an event name
     */
    public function addListener(string $eventName, callable $listener): void
    {
        $this->listeners[$eventName][] = $listener;
    }

    /**
     * Dispatch an event to all registered listeners
     */
    public function dispatch(string $eventName, array $payload = []): void
    {
        if (isset($this->listeners[$eventName])) {
            foreach ($this->listeners[$eventName] as $listener) {
                call_user_func($listener, $payload);
            }
        }
    }
}
