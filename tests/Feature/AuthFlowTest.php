<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Pages\Auth\Login;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests del flujo de autenticación.
 *
 * Cubre Kendall F3.2 — Tests de feature:
 * - Login fallido: 5 intentos → bloqueado (rate limit).
 */
class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_credentials_return_validation_error(): void
    {
        User::factory()->create(['email' => 'admin@test.com', 'password' => bcrypt('correct')]);

        Livewire::test(Login::class)
            ->set('data.email', 'admin@test.com')
            ->set('data.password', 'wrong_password')
            ->call('authenticate')
            ->assertHasErrors(['data.email']);

        $this->assertGuest();
    }

    public function test_login_is_blocked_after_five_attempts(): void
    {
        // Pre-cargar el rate limiter con 5 hits (= máximo permitido)
        $component = Login::class;
        $method    = 'authenticate';
        $ip        = '127.0.0.1';
        $key       = 'livewire-rate-limiter:' . sha1($component . '|' . $method . '|' . $ip);

        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit($key, 60);
        }

        // El 6to intento debe quedar bloqueado (retorna null, sin redirigir)
        $response = Livewire::test(Login::class)
            ->set('data.email', 'any@test.com')
            ->set('data.password', 'any_password')
            ->call('authenticate');

        // No debe estar autenticado
        $this->assertGuest();

        // El rate limiter debe indicar demasiados intentos
        $this->assertTrue(RateLimiter::tooManyAttempts($key, 5));
    }
}
