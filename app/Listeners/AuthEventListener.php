<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Log;

class AuthEventListener
{
    /**
     * Registra un inicio de sesión exitoso en el log de actividad.
     */
    public function handleLogin(Login $event): void
    {
        activity('auth')
            ->causedBy($event->user)
            ->withProperties([
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log("Inicio de sesión: {$event->user->name} ({$event->user->email})");
    }

    /**
     * Registra un cierre de sesión en el log de actividad.
     */
    public function handleLogout(Logout $event): void
    {
        if ($event->user) {
            activity('auth')
                ->causedBy($event->user)
                ->withProperties([
                    'ip' => request()->ip(),
                ])
                ->log("Cierre de sesión: {$event->user->name} ({$event->user->email})");
        }
    }

    /**
     * Registra un intento fallido de login.
     */
    public function handleFailed(Failed $event): void
    {
        activity('auth')
            ->withProperties([
                'email' => $event->credentials['email'] ?? 'desconocido',
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Intento fallido de login: '.($event->credentials['email'] ?? 'desconocido'));
    }
}
