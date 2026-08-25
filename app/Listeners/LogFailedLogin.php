<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        $activity = activity('auth')
            ->causedBy($event->user)
            ->withProperties([
                'ip' => request()->ip(),
                'email' => $event->credentials['email'] ?? null,
            ])
            ->event('failed_login');

        if ($event->user) {
            $activity->performedOn($event->user);
        }

        $activity->log('Intento de inicio de sesión fallido');
    }
}
