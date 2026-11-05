<?php

use App\Models\Order;
use App\Models\Address;
use App\Models\Product;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

new #[Layout('components.layouts.app')] class extends Component {
    public array $cartItems = [];
    public float $totalPrice = 0.0;

    // Ausgewählte Adress-IDs für die Bestellung
    public string $shippingAddressId = '';
    public string $billingAddressId = '';

    // Wird beim Laden der Seite ausgeführt
    public function mount(): void
    {
        // 1. Artikel laden
        $this->cartItems = session()->get('cart', []);
        
        // Falls der Warenkorb leer ist, zurück zum Shop leiten
        if (empty($this->cartItems)) {
            $this->redirectRoute('cart.shop');
            return;
        }

        foreach ($this->cartItems as $item) {
            $this->totalPrice += $item['price'] * $item['quantity'];
        }

        // 2. Standard-Adressen vorauswählen falls vorhanden
        if (Auth::check()) {
            $firstAddress = Auth::user()->addresses()->first();
            if ($firstAddress) {
                $this->shippingAddressId = (string) $firstAddress->id;
                $this->billingAddressId = (string) $firstAddress->id;
            }
        }
    }

    // Holt live alle Adressen des Users
    public function with(): array
    {
        return [
            'userAddresses' => Auth::user()->addresses()->latest()->get()
        ];
    }

    // Führt den eigentlichen Kaufprozess aus
    public function processOrder(): void
    {
        $this->validate([
            'shippingAddressId' => ['required', 'exists:addresses,id'],
            'billingAddressId' => ['required', 'exists:addresses,id'],
        ], [
             // Fehlermeldungen für die Lieferadresse (shippingAddressId)
            'shippingAddressId.required' => __('Bitte wählen Sie eine Lieferadresse aus.'),
            'shippingAddressId.exists' => __('Die ausgewählte Lieferadresse ist ungültig.'),

            // Fehlermeldungen für die Rechnungsadresse (billingAddressId)
            'billingAddressId.required' => __('Bitte wählen Sie eine Rechnungsadresse aus.'),
            'billingAddressId.exists' => __('Die ausgewählte Rechnungsadresse ist ungültig.'),
        ]);

        $shipping = Address::find($this->shippingAddressId);
        $billing = Address::find($this->billingAddressId);

        // Datenbanksicherheit über eine Transaction (Alles oder nichts)
        try {
            DB::transaction(function () use ($shipping, $billing) {
                
                // VORAB-PRÜFUNG UND ABZUG IN EINEM ATOMAREN SCHRITT
                foreach ($this->cartItems as $productId => $item) {
                    // Aktualisiert den Bestand NUR, wenn nach dem Abzug der Bestand nicht unter 0 fällt
                    $affected = Product::where('id', $productId)
                        ->where('quantity', '>=', $item['quantity'])
                        ->decrement('quantity', $item['quantity']);

                    // Wenn keine Zeile betroffen war, war der Lagerbestand zu niedrig oder das Produkt existiert nicht
                    if ($affected === 0) {
                        // Wir holen den Namen für eine präzise Fehlermeldung
                        $product = Product::find($productId);
                        $productName = $product ? $product->name : __('Unbekanntes Produkt');
                        
                        // Wenn das Produkt überhaupt nicht existiert oder die Menge exakt 0 ist
                        if (!$product || $product->quantity === 0) {
                            throw new \Exception(__('Das Produkt ":name" ist leider komplett ausverkauft.', ['name' => $productName]));
                        }
                        
                        // Wenn noch Bestände da sind, aber weniger als die gewünschte Menge
                        if ($product->quantity > 0 && $product->quantity < $item['quantity']) {
                            throw new \Exception(__('Vom Produkt ":name" sind leider nur noch :count Stück verfügbar.', ['name' => $productName, 'count' => $product->quantity]));
                        }

                        // Fallback-Fehlermeldung, falls ein anderer unerwarteter Fall eintritt
                        throw new \Exception(__('Das Produkt ":name" ist leider nicht mehr in der gewünschten Menge verfügbar.', ['name' => $productName]));
                    }

                }

                // 1. Bestellung (Order) mit Text-Adressen einfrieren
                $order = Order::create([
                    'user_id' => Auth::id(),
                    'status' => 1, // 1 = pending (aus Dokumentation)
                    
                    'deliver_address1' => $shipping->address1,
                    'deliver_address2' => $shipping->address2,
                    'deliver_postal_code' => $shipping->postal_code,
                    'deliver_city' => $shipping->city,
                    'deliver_country' => $shipping->country,

                    'invoice_address1' => $billing->address1,
                    'invoice_address2' => $billing->address2,
                    'invoice_postal_code' => $billing->postal_code,
                    'invoice_city' => $billing->city,
                    'invoice_country' => $billing->country,
                ]);

                // 2. Artikel (OrderItems) anlegen
                foreach ($this->cartItems as $productId => $item) {
                    $order->items()->create([
                        'product_id' => $productId,
                        'price' => $item['price'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            });
        } catch (\Exception $e) {
            // Bei Fehler (z.B. zu wenig Bestand): Fehlermeldung an die Oberfläche geben und abbrechen
            session()->flash('error', $e->getMessage());
            return;
        }

        // Warenkorb löschen (Session leeren)
        session()->forget('cart');
        $this->dispatch('cart-updated');

        session()->flash('status', __('Vielen Dank für Ihre Bestellung!'));
        $this->redirectRoute('account.orders');
    }
};
?>

<section class="w-full">
    <!-- Kopfzeile -->
    <div class="mb-6 flex items-start justify-between gap-4 border-b border-zinc-200 pb-5 dark:border-zinc-700">
        <div class="flex flex-col">
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ __('Kasse') }}</h1>
            <flux:subheading size="lg" class="mt-1 text-zinc-500 dark:text-zinc-400">{{ __('Schließen Sie Ihre Bestellung ab') }}</flux:subheading>
        </div>
    </div>

    <!-- Anzeige von Fehlermeldungen bei unzureichendem Lagerbestand beim Checkout -->
    @if (session('error'))
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-600 dark:border-red-800/50 dark:bg-red-950/30 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Linke Spalte: Adressauswahl -->
        <div class="lg:col-span-2 space-y-4">
            <div class="p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 space-y-6">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-white">{{ __('Versand- & Rechnungsdetails') }}</h3>
                
                @if($userAddresses->isEmpty())
                    <!-- Wenn keine Adresse verfügbar ist: Schöner Hinweis-Banner mit Button -->
                    <div class="p-5 bg-amber-50 text-amber-800 rounded-xl dark:bg-amber-950/20 dark:text-amber-400 border border-amber-200 dark:border-amber-900/50">
                        <p class="text-sm font-medium mb-3">{{ __('Bitte legen Sie zuerst eine Adresse in Ihrem Profil an.') }}</p>
                        
                        <flux:button href="{{ route('settings.addresses') }}" icon="plus" wire:navigate size="sm" class="bg-amber-600 text-white hover:bg-amber-700 border-none">
                            {{ __('Jetzt Adresse anlegen') }}
                        </flux:button>
                    </div>
                @else
                    <!-- Lieferadresse wählen -->
                    <flux:select wire:model="shippingAddressId" label="{{ __('Lieferadresse wählen') }}">
                        <option value="">-- {{ __('Bitte eine Lieferadresse wählen') }} --</option>
                        @foreach($userAddresses as $addr)
                            <option value="{{ $addr->id }}">{{ $addr->address1 }}, {{ $addr->postal_code }} {{ $addr->city }} ({{ $addr->country }})</option>
                        @endforeach
                    </flux:select>

                    <!-- Rechnungsadresse wählen -->
                    <flux:select wire:model="billingAddressId" label="{{ __('Rechnungsadresse wählen') }}">
                        <option value="">-- {{ __('Bitte eine Rechnungsadresse wählen') }} --</option>
                        @foreach($userAddresses as $addr)
                            <option value="{{ $addr->id }}">{{ $addr->address1 }}, {{ $addr->postal_code }} {{ $addr->city }} ({{ $addr->country }})</option>
                        @endforeach
                    </flux:select>
                @endif

                <div class="pt-4 border-t border-zinc-100 dark:border-zinc-700">
                    <flux:button variant="ghost" href="{{ route('cart.cart') }}" wire:navigate icon="arrow-left">
                        {{ __('Zurück zum Warenkorb') }}
                    </flux:button>
                </div>
            </div>
        </div>

        <!-- Rechte Spalte: Bestellübersicht & Kaufen-Button -->
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-700 dark:bg-zinc-900 h-fit">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('Zusammenfassung') }}</h2>
            
            <!-- Mini-Artikelliste zur Kontrolle -->
            <div class="space-y-3 mb-4 pb-4 border-b border-zinc-200 dark:border-zinc-700 max-h-48 overflow-y-auto">
                @foreach($cartItems as $item)
                    <div class="flex justify-between text-xs text-zinc-600 dark:text-zinc-400">
                        <span>{{ $item['quantity'] }}x {{ $item['name'] }}</span>
                        <span>{{ number_format($item['price'] * $item['quantity'], 2, ',', '.') }} €</span>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-between pb-4">
                <span class="text-zinc-600 dark:text-zinc-400">{{ __('Gesamtsumme:') }}</span>
                <span class="text-xl font-bold text-zinc-900 dark:text-white">{{ number_format($totalPrice, 2, ',', '.') }} €</span>
            </div>

            <!-- Kauf-Button sperrt sich automatisch, wenn keine Adresse da ist -->
            <flux:button variant="primary" class="w-full mt-4 bg-green-600 text-white hover:bg-green-700 dark:bg-green-600 dark:hover:bg-green-700" 
                         wire:click="processOrder" 
                         :disabled="$userAddresses->isEmpty()">
                {{ __('Jetzt kostenpflichtig bestellen') }}
            </flux:button>
        </div>
    </div>
</section>
