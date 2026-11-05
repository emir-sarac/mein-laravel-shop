<?php

use App\Models\Product;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Gate;

new #[Layout('components.layouts.app')] class extends Component {

    public array $products = [];

    // Sichert die Komponente direkt beim Initialisieren ab (Prüft bei JEDER Interaktion mit dem Server)
    public function boot(): void
    {
        if (Gate::denies('admin')) {
            abort(403, __('Diese Aktion ist nur für Administratoren erlaubt.'));
        }
    }

    public function mount(): void
    {
        $this->products = Product::all()->toArray();
    }
}
?>

<section>
    <div class="mb-6 flex items-start justify-between gap-4 border-b border-zinc-200 pb-5 dark:border-zinc-700">
        <!-- Links: Titel und Beschreibung untereinander wegen flex-col -->
        <div class="flex flex-col">
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Produkte</h1>

            <flux:subheading size="lg" class="mt-1 text-zinc-500 dark:text-zinc-400">Verwalten Sie Ihre Produkte im Überblick</flux:subheading>
        </div>

        @can('admin')
        <!-- Rechts: Button auf gleicher Höhe, weil wir vorher justify-between gemacht haben -->
        <flux:button icon="plus-circle" href="{{ route('products.create') }}" wire:navigate class="bg-zinc-200 text-zinc-900 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-white dark:hover:bg-zinc-700 border-none">
            {{ __('Neues Produkt') }}
        </flux:button>
        @endcan
    </div>

    <!-- Erfolgsmeldung anzeigen -->
    @if (session('status'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
            class="mb-6 flex items-center justify-between rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-600 dark:border-green-800/50 dark:bg-green-950/30 dark:text-green-400">
            
            <span>{{ session('status') }}</span>
            
            <!-- Schließen-Button (X) -->
            <button @click="show = false" class="ml-4 hover:opacity-75 focus:outline-none">✕</button>
        </div>
    @endif

    <!-- Produktgrid -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @forelse ($products as $product)
            <div wire:key="product-{{ $product['id'] }}"
                 class="flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

                <!-- Produktbild -->
                <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900">
                    @if (!empty($product['img_path']))
                        <img src="{{ asset('storage/' . $product['img_path']) }}"
                            alt="{{ $product['name'] }}"
                            class="aspect-square w-full object-cover transition-transform duration-300 hover:scale-105">
                    @else
                        {{-- Moderner Platzhalter mit Icon --}}
                        <div class="flex aspect-square flex-col items-center justify-center bg-zinc-100 text-xs font-medium text-zinc-400 dark:bg-zinc-800/50 dark:text-zinc-500">
                            <svg xmlns="http://w3.org" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mb-1 h-6 w-6 opacity-60">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375 0 1 1-.75 0 .375 0 0 1 .75 0Z" />
                            </svg>
                            {{ __('Kein Bild') }}
                        </div>
                    @endif
                </div>

                <div class="flex flex-1 flex-col p-4">
                    <div class="flex items-start justify-between gap-2">
                        <!-- Produktname -->
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ $product['name'] }}</h2>

                        <!-- Produkt inaktiv -->
                        @unless ($product['is_active'])
                            <span class="shrink-0 rounded-full bg-zinc-200 px-2 py-0.5 text-xs text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                Inaktiv
                            </span>
                        @endunless
                    </div>

                    <!-- Beschreibung -->
                    @if ($product['description'])
                        <p class="mt-1 line-clamp-2 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $product['description'] }}
                        </p>
                    @endif

                    <div class="mt-auto flex items-center justify-between pt-4">
                        <!-- Preis -->
                        <span class="text-lg font-bold text-zinc-900 dark:text-white">
                            {{ number_format($product['price'], 2, ',', '.') }} €
                        </span>

                        <!-- Menge -->
                        @if ($product['quantity'] > 0)
                            <span class="text-sm text-green-600 dark:text-green-400">{{ $product['quantity'] }} auf Lager</span>
                        @else
                            <span class="text-sm text-red-600 dark:text-red-400">Ausverkauft</span>
                        @endif
                    </div>

                    @can('admin')
                    <!-- Aktions-Buttons -->
                    <div class="mt-4 flex flex-row items-center gap-2 w-full">
                        <!-- Gelber Bearbeiten Button -->
                        <flux:button variant="filled" size="sm" icon="pencil" class="w-1/2 bg-yellow-500 text-zinc-950 hover:bg-yellow-600 dark:bg-yellow-500 dark:text-white dark:hover:bg-yellow-600 border-none"
                        href="{{ route('products.edit', $product['id']) }}" wire:navigate>{{ __('Bearbeiten') }}</flux:button>
                        
                        <!-- Roter Löschen Button -->
                        <flux:button variant="danger" size="sm" icon="trash" class="w-1/2 bg-red-600 text-white hover:bg-red-700 dark:bg-red-600 dark:text-white dark:hover:bg-red-700 border-none"
                        href="{{ route('products.delete', $product['id']) }}" wire:navigate>{{ __('Löschen') }}</flux:button>
                    </div>
                    @endcan
                </div>
            </div>
        @empty
            <p class="col-span-full py-12 text-center text-zinc-500">Keine Produkte vorhanden.</p>
        @endforelse
    </div>
</section>