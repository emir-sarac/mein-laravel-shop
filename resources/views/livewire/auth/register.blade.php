<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ], [
            // Fehlermeldungen für den Namen
            'name.required' => __('Bitte geben Sie Ihren Namen ein.'),
            'name.string' => __('Der Name muss ein gültiger Text sein.'),
            'name.max' => __('Der Name darf maximal 255 Zeichen lang sein.'),

            // Fehlermeldungen für die E-Mail-Adresse
            'email.required' => __('Bitte geben Sie eine E-Mail-Adresse ein.'),
            'email.string' => __('Die E-Mail-Adresse muss ein gültiger Text sein.'),
            'email.lowercase' => __('Die E-Mail-Adresse muss in Kleinbuchstaben geschrieben sein.'),
            'email.email' => __('Bitte geben Sie eine echte E-Mail-Adresse ein.'),
            'email.max' => __('Die E-Mail-Adresse darf maximal 255 Zeichen lang sein.'),
            'email.unique' => __('Diese E-Mail-Adresse wird bereits von einem anderen Benutzer verwendet.'),

            // Fehlermeldungen für das Passwort
            'password.required' => __('Bitte geben Sie ein Passwort ein.'),
            'password.string' => __('Das Passwort muss aus gültigen Zeichen bestehen.'),
            'password.confirmed' => __('Die Passwort-Bestätigung stimmt nicht mit dem Passwort überein.'),
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered(($user = User::create($validated))));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header title="Create an account" description="Enter your details below to create your account" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="register" class="flex flex-col gap-6">
        <!-- Name -->
        <div class="grid gap-2">
            <flux:input wire:model="name" id="name" label="{{ __('Name') }}" type="text" name="name" required autofocus autocomplete="name" placeholder="Name" />
        </div>

        <!-- Email Address -->
        <div class="grid gap-2">
            <flux:input wire:model="email" id="email" label="{{ __('Email address') }}" type="email" name="email" required autocomplete="email" placeholder="email@example.com" />
        </div>

        <!-- Password -->
        <div class="grid gap-2">
            <flux:input
                wire:model="password"
                id="password"
                label="{{ __('Password') }}"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                placeholder="Password"
            />
        </div>

        <!-- Confirm Password -->
        <div class="grid gap-2">
            <flux:input
                wire:model="password_confirmation"
                id="password_confirmation"
                label="{{ __('Confirm password') }}"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Confirm password"
            />
        </div>

        <div class="flex items-center justify-end">
            <flux:button type="submit" variant="primary" class="w-full">
                {{ __('Create account') }}
            </flux:button>
        </div>
    </form>

    <div class="space-x-1 text-center text-sm text-zinc-600 dark:text-zinc-400">
        Already have an account?
        <x-text-link href="{{ route('login') }}">Log in</x-text-link>
    </div>
</div>
