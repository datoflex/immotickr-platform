<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.register');
    }

    public function test_new_users_can_register(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register');

        $component->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_an_invalid_registration_shows_a_message_below_every_affected_field(): void
    {
        Volt::test('pages.auth.register')
            ->set('email', 'keine-adresse')
            ->set('password', 'kurz')
            ->set('password_confirmation', 'anders')
            ->call('register')
            ->assertHasErrors(['name', 'email', 'password'])
            ->assertSee('Name muss ausgefüllt werden.')
            ->assertSee('E-Mail muss eine gültige E-Mail-Adresse sein.')
            ->assertSee('Passwort stimmt nicht mit der Bestätigung überein.')
            ->assertSee('Passwort muss mindestens 8 Zeichen lang sein.');

        $this->assertGuest();
    }

    public function test_registering_with_an_email_that_is_already_taken_says_so(): void
    {
        User::factory()->create(['email' => 'vergeben@example.com']);

        Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'vergeben@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertHasErrors('email')
            ->assertSee('E-Mail ist bereits vergeben.');

        $this->assertGuest();
    }
}
