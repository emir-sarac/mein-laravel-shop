<?php

use App\Livewire\Actions\ManageCart;
use App\Models\Product;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    public array $cartItems = [];
    public float $totalPrice = 0.0;

    // Hält alle aktuellen Lagerbestandsfehler
    public array $stockErrors = [];

    // Hört live auf Änderungen im Warenkorb
    protected $listeners = ['cart-updated' => 'loadCart'];

    // Wird beim Laden der Seite ausgeführt
    public function mount(): void
    {
        $this->loadCart();
    }

    // Holt die gewählten Artikel direkt aus der Browser-Session
    public function loadCart(): void
    {
        $this->cartItems = session()->get('cart', []);
        $this->totalPrice = 0;
        
        foreach ($this->cartItems as $item) {
            $this->totalPrice += $item['price'] * $item['quantity'];
        }

        // Automatische Gesamtprüfung bei jedem Laden des Warenkorbs
        $this->checkAllStock();
    }

    // Prüft alle Artikel im Warenkorb gegen den echten Lagerbestand
    protected function checkAllStock(): void
    {
        $this->stockErrors = [];

        foreach ($this->cartItems as $id => $item) {
            $product = Product::find($id);
            
            if (!$product || !$product->is_active) {
                $this->stockErrors[$id] = __('":name" ist nicht mehr verfügbar.', ['name' => $item['name']]);
            } elseif ($item['quantity'] > $product->quantity) {
                $this->stockErrors[$id] = __('":name" überschreitet den Lagerbestand (Max: :max).', ['name' => $item['name'], 'max' => $product->quantity]);
            }
        }
    }

    // Ändert die Menge (+1 / -1) direkt in der Session via Action
    public function changeQuantity(int $id, int $amount): void
    {
        // temporäre Vorabprüfung für den Plus-Klick
        if ($amount > 0) {
            $product = Product::find($id);
            $currentCartQuantity = $this->cartItems[$id]['quantity'] ?? 0;

            if ($product && ($currentCartQuantity + $amount) > $product->quantity) {
                $this->checkAllStock();
                return;
            }
        }

        ManageCart::updateQuantity($id, $amount);
        $this->loadCart();
        $this->dispatch('cart-updated');
    }

    // Löscht den Artikel komplett aus der Session via Action
    public function deleteItem(int $id): void
    {
        ManageCart::remove($id);
        $this->loadCart();
        $this->dispatch('cart-updated');
    }
};
?>

<section class="w-full">
    <!-- Kopfzeile: Identisch strukturiert wie beim Shop -->
    <div class="mb-6 flex items-start justify-between gap-4 border-b border-zinc-200 pb-5 dark:border-zinc-700">
        <div class="flex flex-col">
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Warenkorb</h1>
            <flux:subheading size="lg" class="mt-1 text-zinc-500 dark:text-zinc-400">Hier sehen Sie Ihre ausgewählten Produkte</flux:subheading>
        </div>
    </div>

    @if(empty($cartItems))
        <!-- Leerer Warenkorb -->
        <div class="flex flex-col items-center justify-center p-12 border border-dashed border-zinc-200 rounded-xl dark:border-zinc-700">
            <p class="text-zinc-500 dark:text-zinc-400 mb-4">{{ __('Ihr Warenkorb ist aktuell noch leer.') }}</p>
            <flux:button href="/shop" wire:navigate variant="primary">{{ __('Jetzt einkaufen') }}</flux:button>
        </div>
    @else
        <!-- Warenkorb-Inhalt im Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Linke Spalte: Die Liste der Artikel -->
            <div class="lg:col-span-2 space-y-4">
                @foreach($cartItems as $id => $item)
                    <div class="flex flex-row items-center gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 {{ isset($stockErrors[$id]) ? 'border-red-300 dark:border-red-900 bg-red-50/10' : '' }}">
                        
                        <!-- Bild -->
                        <div class="h-16 w-16 shrink-0 overflow-hidden rounded-lg bg-zinc-50 dark:bg-zinc-800">
                            @if(!empty($item['img_path']))
                                <img src="{{ asset('storage/' . $item['img_path']) }}" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full w-full items-center justify-center text-[10px] text-zinc-400">Kein Bild</div>
                            @endif
                        </div>

                        <!-- Name und Preis -->
                        <div class="flex-1">
                            <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $item['name'] }}</h3>
                            <p class="text-sm text-zinc-500">{{ number_format($item['price'], 2, ',', '.') }} €</p>
                        </div>

                        <!-- Plus / Minus Mengensteuerung in einer Zeile -->
                        <div class="flex items-center gap-2">
                            <flux:button size="sm" square variant="ghost" icon="minus" wire:click="changeQuantity({{ $id }}, -1)" />
                            <span class="text-sm font-medium w-6 text-center {{ isset($stockErrors[$id]) ? 'text-red-600 dark:text-red-400 font-bold' : '' }}">{{ $item['quantity'] }}</span>
                            <flux:button size="sm" square variant="ghost" icon="plus" wire:click="changeQuantity({{ $id }}, 1)" />
                        </div>

                        <!-- Mülleimer-Knopf zum Löschen -->
                        <flux:button size="sm" square variant="danger" icon="trash" wire:click="deleteItem({{ $id }})" />
                    </div>
                @endforeach
            </div>

            <!-- Rechte Spalte: Kasse & Gesamtsumme -->
            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-700 dark:bg-zinc-900 h-fit">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('Zusammenfassung') }}</h2>

                <!-- Zentrale Anzeige aller Bestandsfehler untereinander, kompakt platziert -->
                @if(!empty($stockErrors))
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 dark:bg-red-950/20 dark:border-red-900/50 rounded-lg space-y-1">
                        <p class="text-xs font-bold text-red-700 dark:text-red-400 uppercase tracking-wider mb-1">{{ __('Bitte korrigieren:') }}</p>
                        @foreach($stockErrors as $error)
                            <p class="text-xs text-red-600 dark:text-red-400 flex items-start gap-1">
                                <span>•</span> <span>{{ $error }}</span>
                            </p>
                        @endforeach
                    </div>
                @endif

                <div class="flex justify-between border-b border-zinc-200 pb-4 dark:border-zinc-700">
                    <span class="text-zinc-600 dark:text-zinc-400">{{ __('Gesamtsumme:') }}</span>
                    <span class="text-xl font-bold text-zinc-900 dark:text-white">{{ number_format($totalPrice, 2, ',', '.') }} €</span>
                </div>
                
                <!-- Der Button leitet bei Erfolg weiter oder agiert als gesperrter Button bei Fehlern -->
                @if(empty($stockErrors))
                    <flux:button href="{{ route('cart.checkout') }}" wire:navigate variant="primary" class="w-full mt-4 bg-blue-600 text-white hover:bg-blue-700">
                        {{ __('Zur Kasse gehen') }}
                    </flux:button>
                @else
                    <flux:button variant="primary" class="w-full mt-4 bg-blue-600 text-white opacity-40 cursor-not-allowed" disabled>
                        {{ __('Zur Kasse gehen') }}
                    </flux:button>
                @endif

            </div>
        </div>
    @endif
</section>
