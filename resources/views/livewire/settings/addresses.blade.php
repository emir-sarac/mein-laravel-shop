<?php

use App\Models\Address;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.app')] class extends Component {
    // ID der aktuell ausgewählten Adresse aus dem Dropdown ('new' = Neue Adresse)
    public string $selectedAddressId = 'new';

    // Formular-Felder
    public string $address1 = '';
    public string $address2 = '';
    public string $postal_code = '';
    public string $city = '';
    public string $country = 'Österreich';

    // Holt die Liste aller Adressen für das Dropdown
    public function with(): array
    {
        return [ 'addresses' => Auth::user()->addresses()->latest()->get() ];
    }

    // Wird automatisch ausgeführt, sobald der User im Dropdown etwas anderes auswählt
    public function updatedSelectedAddressId($value): void
    {
        if ($value === 'new') {
            $this->resetForm();
        } else {
            $address = Address::find($value);
            
            if ($address && $address->user_id === Auth::id()) {
                $this->address1 = $address->address1;
                $this->address2 = $address->address2 ?? '';
                $this->postal_code = $address->postal_code;
                $this->city = $address->city;
                $this->country = $address->country;
            }
        }
    }

    // Setzt das Formular für eine neue Adresse zurück
    private function resetForm(): void
    {
        $this->address1 = '';
        $this->address2 = '';
        $this->postal_code = '';
        $this->city = '';
        $this->country = 'Österreich';
    }

    // Speichert (entweder Update oder Create)
    public function saveAddress(): void
    {
        $validated = $this->validate([
            'address1' => ['required', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
        ], [
            // Fehlermeldungen für Straße und Hausnummer (address1)
            'address1.required' => __('Bitte geben Sie eine Straße und Hausnummer ein.'),
            'address1.string' => __('Die Straße muss ein gültiger Text sein.'),
            'address1.max' => __('Die Straße darf maximal 255 Zeichen lang sein.'),

            // Fehlermeldungen für Adresszusatz (address2)
            'address2.string' => __('Der Adresszusatz muss ein gültiger Text sein.'),
            'address2.max' => __('Der Adresszusatz darf maximal 255 Zeichen lang sein.'),

            // Fehlermeldungen für Postleitzahl (postal_code)
            'postal_code.required' => __('Bitte geben Sie die Postleitzahl ein.'),
            'postal_code.string' => __('Die Postleitzahl muss ein gültiger Text sein.'),
            'postal_code.max' => __('Die Postleitzahl darf maximal 20 Zeichen lang sein.'),

            // Fehlermeldungen für Stadt (city)
            'city.required' => __('Bitte geben Sie die Stadt ein.'),
            'city.string' => __('Die Stadt muss ein gültiger Text sein.'),
            'city.max' => __('Die Stadt darf maximal 255 Zeichen lang sein.'),

            // Fehlermeldungen für Land (country)
            'country.required' => __('Bitte geben Sie das Land ein.'),
            'country.string' => __('Das Land muss ein gültiger Text sein.'),
            'country.max' => __('Das Land darf maximal 255 Zeichen lang sein.'),
        ]);

        if ($this->selectedAddressId === 'new') {
            // Default Werte setzen
            $validated['is_shipping'] = false;
            $validated['is_billing'] = false;

            // Neue Adresse anlegen
            $newAddress = Auth::user()->addresses()->create($validated);

            // Direkt auf die neu erstellte Adresse wechseln
            $this->selectedAddressId = (string) $newAddress->id;
            session()->flash('status', __('Adresse erfolgreich angelegt!'));
        } else {
            // Bestehende Adresse aktualisieren
            $address = Address::find($this->selectedAddressId);
            if ($address && $address->user_id === Auth::id()) {
                $address->update($validated);
                session()->flash('status', __('Adresse erfolgreich aktualisiert!'));
            }
        }
    }

    // Löscht die aktuell ausgewählte Adresse
    public function deleteAddress(): void
    {
        if ($this->selectedAddressId === 'new') {
            return;
        }

        $address = Address::find($this->selectedAddressId);
        
        if ($address && $address->user_id === Auth::id()) {
            $address->delete();
            $this->selectedAddressId = 'new';
            $this->resetForm();
            session()->flash('status', __('Adresse wurde gelöscht.'));
        }
    }
};
?>

<section class="w-full">
    @include('partials.settings-heading')

    <!-- Nutzt die Menü-Tabs-Struktur des Starterkits -->
    <x-settings.layout heading="Addresses" subheading="Manage your saved addresses for checkout">
        <div class="my-6 w-full space-y-6">

            <!-- Statusmeldungen -->
            @if (session('status'))
                <div class="text-sm font-medium text-green-600 dark:text-green-400">
                    {{ session('status') }}
                </div>
            @endif

            <!-- Adress-Auswahl -->
            <flux:select wire:model.live="selectedAddressId" label="{{ __('Adresse auswählen') }}">
                <option value="new">➕ {{ __('Neue Adresse anlegen...') }}</option>
                @foreach($addresses as $addr)
                    <option value="{{ $addr->id }}">
                        {{ $addr->address1 }}, {{ $addr->postal_code }} {{ $addr->city }}
                    </option>
                @endforeach
            </flux:select>

            <hr class="border-zinc-200 dark:border-zinc-700" />

            <!-- Formular -->
            <form wire:submit="saveAddress" class="space-y-6">
                
                <flux:input wire:model="address1" label="{{ __('Straße und Hausnummer') }}" type="text" name="address1" />
                
                <flux:input wire:model="address2" label="{{ __('Adresszusatz (optional)') }}" type="text" name="address2" />

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-1">
                        <flux:input wire:model="postal_code" label="{{ __('PLZ') }}" type="text" name="postal_code" />
                    </div>
                    <div class="md:col-span-2">
                        <flux:input wire:model="city" label="{{ __('Stadt') }}" type="text" name="city" />
                    </div>
                </div>

                <flux:input wire:model="country" label="{{ __('Land') }}" type="text" name="country" />

                <!-- Buttons -->
                <div class="flex items-center justify-between gap-4 pt-4">
                    <flux:button type="submit" class="bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700">
                        {{ $selectedAddressId === 'new' ? __('Adresse erstellen') : __('Änderungen speichern') }}
                    </flux:button>

                    @if($selectedAddressId !== 'new')
                        <flux:button type="button" variant="ghost" class="text-red-600 hover:text-red-700 dark:text-red-500 dark:hover:text-red-400"
                                    wire:click="deleteAddress" 
                                    wire:confirm="{{ __('Möchten Sie diese Adresse wirklich löschen?') }}">
                            {{ __('Adresse löschen') }}
                        </flux:button>
                    @endif
                </div>
            </form>
        </div>
    </x-settings.layout>
</section>