<?php


use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id)
            ],
        ], [
            // Fehlermeldungen für den Namen
            'name.required' => __('Bitte geben Sie Ihren Namen ein.'),
            'name.string' => __('Der Name muss ein gültiger Text sein.'),
            'name.max' => __('Der Name darf maximal 255 Zeichen lang sein.'),

            // Fehlermeldungen für die E-Mail-Adresse
            'email.required' => __('Bitte geben Sie Ihre E-Mail-Adresse ein.'),
            'email.string' => __('Die E-Mail-Adresse muss ein gültiger Text sein.'),
            'email.lowercase' => __('Die E-Mail-Adresse muss in Kleinbuchstaben geschrieben sein.'),
            'email.email' => __('Bitte geben Sie eine echte E-Mail-Adresse ein (z.B. name@beispiel.de).'),
            'email.max' => __('Die E-Mail-Adresse darf maximal 255 Zeichen lang sein.'),
            'email.unique' => __('Diese E-Mail-Adresse wird bereits von einem anderen Benutzer verwendet.'),
        ]);

        $user->name = $this->name;
        $user->email = $this->email;

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated');
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout heading="Profile" subheading="Update your name and email address">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" label="{{ __('Name') }}" type="text" name="name" required autofocus
                        autocomplete="name"/>
            <div>
                <flux:input wire:model="email" label="{{ __('Email') }}" type="email" name="email" required
                            autocomplete="email"/>

                @if (auth()->user() instanceof MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                    <div>
                        <p class="mt-2 text-sm text-gray-800">
                            {{ __('Your email address is unverified.') }}

                            <button
                                wire:click.prevent="resendVerificationNotification"
                                class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 text-sm font-medium text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" x-on:click="setTimeout(() => { Livewire.navigate('/settings/profile') }, 1000)">{{ __('Save') }}</flux:button>
                </div>
            </div>
        </form>

        <livewire:settings.delete-user-form/>
    </x-settings.layout>
</section>
