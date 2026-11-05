<?php

use App\Models\Product;
use App\Livewire\Actions\ManageCart;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    // Array für die getippten Mengen pro Produkt-ID
    public array $quantities = [];

    // Lädt alle aktiven Produkte, die auf Lager sind
    public function mount(): void
    {
        foreach (Product::where('is_active', true)->where('quantity', '>', 0)->get() as $product) {
            $this->quantities[$product->id] = 1;
        }
    }

    // Hilfsfunktion für den Blade-Teil (Filtert inaktive und ausverkaufte Artikel heraus)
    public function getProductsProperty()
    {
        return Product::where('is_active', true)->where('quantity', '>', 0)->get();
    }

    // Ruft unsere neue Action auf
    public function addToCart(int $productId): void
    {
        $chosenQuantity = (int) ($this->quantities[$productId] ?? 1);
        
        // Frisch aus der Datenbank laden, um den exakten, aktuellen Lagerbestand zu prüfen
        $product = Product::find($productId);

        if (!$product || !$product->is_active) {
            session()->flash('error_' . $productId, __('Produkt ist nicht mehr verfügbar.'));
            return;
        }

        // Bereits im Warenkorb liegende Menge dieses Produkts ermitteln
        $cart = session()->get('cart', []);
        $alreadyInCart = (int) ($cart[$productId]['quantity'] ?? 0);

        // Gesamtsumme (bereits im Korb + neu gewählt) gegen den Lagerbestand prüfen
        if (($chosenQuantity + $alreadyInCart) > $product->quantity) {
            if ($alreadyInCart > 0) {
                session()->flash('error_' . $productId, __('Sie haben bereits :cart im Warenkorb. Maximal :max möglich.', ['cart' => $alreadyInCart, 'max' => $product->quantity]));
            } else {
                session()->flash('error_' . $productId, __('Nicht genug auf Lager! Nur noch :count verfügbar.', ['count' => $product->quantity]));
            }
            return;
        }

        if ($chosenQuantity <= 0) {
            session()->flash('error_' . $productId, __('Ungültige Menge.'));
            return;
        }
        
        ManageCart::add($productId, $chosenQuantity);

        $this->dispatch('cart-updated');
        session()->flash('status_' . $productId, __('Hinzugefügt!'));
    }
};
?>


<section>
    <!-- Kopfzeile: Exakt wie bei Produkte, aber mit Shop-Texten -->
    <div class="mb-6 flex items-start justify-between gap-4 border-b border-zinc-200 pb-5 dark:border-zinc-700">
        <!-- Links: Titel und Beschreibung -->
        <div class="flex flex-col">
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Shop</h1>
            <flux:subheading size="lg" class="mt-1 text-zinc-500 dark:text-zinc-400">Nehmen Sie sich Zeit für Ihren Einkauf</flux:subheading>
        </div>
        <!-- Rechts: Button zum Warenkorb mit Live-Zähler -->
        <flux:button 
            icon="shopping-cart" 
            href="/cart" 
            wire:navigate 
            class="bg-zinc-200 text-zinc-900 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-white dark:hover:bg-zinc-700 border-none"
        >
            {{ __('Warenkorb') }}
            @if(session()->has('cart') && count(session('cart')) > 0)
                <span class="ml-2 rounded-full bg-blue-600 px-2 py-0.5 text-xs text-white">
                    {{ collect(session('cart'))->sum('quantity') }}
                </span>
            @endif
        </flux:button>
    </div>

    <!-- Produktgrid -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @forelse ($this->products as $product)
            <div wire:key="product-{{ $product->id }}"
                 class="flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

                <!-- Produktbild -->
                <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900">
                    @if (!empty($product->img_path))
                        <img src="{{ asset('storage/' . $product->img_path) }}"
                            alt="{{ $product->name }}"
                            class="aspect-square w-full object-cover transition-transform duration-300 hover:scale-105">
                    @else
                        <div class="flex aspect-square flex-col items-center justify-center bg-zinc-100 text-xs font-medium text-zinc-400 dark:bg-zinc-800/50 dark:text-zinc-500">
                            <svg xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mb-1 h-6 w-6 opacity-60">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375 0 1 1-.75 0 .375 0 0 1 .75 0Z" />
                            </svg>
                            {{ __('Kein Bild') }}
                        </div>
                    @endif
                </div>

                <!-- Produkt-Details -->
                <div class="flex flex-1 flex-col p-4">
                    <div class="flex items-start justify-between gap-2">
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ $product->name }}</h2>
                    </div>

                    <!-- Beschreibung -->
                    @if ($product->description)
                        <p class="mt-1 line-clamp-2 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $product->description }}
                        </p>
                    @endif

                    <!-- Preis und Lagerbestand -->
                    <div class="mt-auto flex items-center justify-between pt-4">
                        <span class="text-lg font-bold text-zinc-900 dark:text-white">
                            {{ number_format($product->price, 2, ',', '.') }} €
                        </span>

                        @if ($product->quantity > 0)
                            <span class="text-sm text-green-600 dark:text-green-400">{{ $product->quantity }} auf Lager</span>
                        @else
                            <span class="text-sm text-red-600 dark:text-red-400">Ausverkauft</span>
                        @endif
                    </div>

                    <!-- Kauf-Bereich für Kunden: Mengeneingabe + Button in einer Zeile -->
                    {{-- @cannot('admin') --}}
                    <div class="mt-3 flex flex-row items-center gap-2 w-full">
                        <flux:input wire:model="quantities.{{ $product->id }}" type="number" min="1" max="{{ $product->quantity }}" class="w-20" />
                        
                        <flux:button variant="primary" size="sm" icon="shopping-cart" class="flex-1 bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700 border-none" wire:click="addToCart({{ $product->id }})">
                            {{ __('Wählen') }}
                        </flux:button>
                    </div>

                    @if(session()->has('status_' . $product->id))
                        <span class="text-xs text-green-600 dark:text-green-400 mt-1 block text-right">{{ session('status_' . $product->id) }}</span>
                    @endif

                    <!-- Anzeige von Fehlermeldungen bei unzureichendem Lagerbestand -->
                    @if(session()->has('error_' . $product->id))
                        <span class="text-xs text-red-600 dark:text-red-400 mt-1 block text-right font-medium">{{ session('error_' . $product->id) }}</span>
                    @endif
                    {{-- @endcannot --}}
                </div>
            </div>
        @empty
            <p class="col-span-full py-12 text-center text-zinc-500">Aktuell sind keine Produkte im Shop verfügbar.</p>
        @endforelse
    </div>
</section>
