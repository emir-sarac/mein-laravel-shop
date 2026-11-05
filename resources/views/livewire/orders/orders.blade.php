<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.app')] class extends Component {

    // Sortierung standardmäßig nach neuestem Datum
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    // Geöffnete Akkordeons
    public array $openOrderIds = [];

    // Holt NUR die eigenen Bestellungen des eingeloggten Nutzers
    public function with(): array
    {
        return [
            'orders' => Auth::user()->orders()
                ->with('items.product')
                ->orderBy($this->sortBy, $this->sortDirection)
                ->get()
        ];
    }

    // Sortierungs-Umschalter
    public function setSort(string $field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'desc' ? 'asc' : 'desc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'desc';
        }
    }

    // Details auf-/zuklappen
    public function toggleOrder(int $orderId): void
    {
        if (in_array($orderId, $this->openOrderIds)) {
            $this->openOrderIds = array_diff($this->openOrderIds, [$orderId]);
        } else {
            $this->openOrderIds[] = $orderId;
        }
    }
};
?>

<section class="w-full">
    <!-- Kopfzeile -->
    <div class="mb-6 flex items-start justify-between gap-4 border-b border-zinc-200 pb-5 dark:border-zinc-700">
        <div class="flex flex-col">
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ __('Meine Bestellungen') }}</h1>
            <flux:subheading size="lg" class="mt-1 text-zinc-500 dark:text-zinc-400">{{ __('Hier sehen Sie eine Übersicht all Ihrer bisherigen Einkäufe') }}</flux:subheading>
        </div>
    </div>

    <!-- Sortierungs-Bar -->
    <div class="flex items-center justify-end gap-2 text-sm bg-zinc-50 dark:bg-zinc-800/50 p-2 rounded-lg border border-zinc-200 dark:border-zinc-700/60 mb-6 max-w-3xl">
        <span class="text-zinc-500 mr-2 font-medium text-xs uppercase tracking-wider">{{ __('Sortieren nach:') }}</span>
        
        <flux:button size="sm" variant="ghost" wire:click="setSort('created_at')">
            {{ __('Datum') }}
            @if($sortBy === 'created_at')
                <flux:icon name="{{ $sortDirection === 'desc' ? 'chevron-down' : 'chevron-up' }}" class="ml-1 h-3 w-3 inline" />
            @endif
        </flux:button>

        <flux:button size="sm" variant="ghost" wire:click="setSort('id')">
            {{ __('Bestellnummer') }}
            @if($sortBy === 'id')
                <flux:icon name="{{ $sortDirection === 'desc' ? 'chevron-down' : 'chevron-up' }}" class="ml-1 h-3 w-3 inline" />
            @endif
        </flux:button>
    </div>

    <!-- Bestellliste -->
    <div class="space-y-3 max-w-3xl">
        @forelse($orders as $order)
            @php $isOpen = in_array($order->id, $openOrderIds); @endphp
            
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 overflow-hidden transition-all duration-200">
                
                <!-- Tabellen-Header (Klickbar) -->
                <div wire:click="toggleOrder({{ $order->id }})" 
                     class="flex items-center justify-between p-4 cursor-pointer hover:bg-zinc-50/80 dark:hover:bg-zinc-800/40 select-none">
                    
                    <div class="flex items-center gap-4 sm:gap-8">
                        <div>
                            <p class="text-xs text-zinc-400 font-medium">#{{ $order->id }}</p>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white mt-0.5">
                                {{ $order->created_at->format('d.m.Y') }}
                            </p>
                        </div>
                        <div class="hidden sm:block">
                            <p class="text-xs text-zinc-400 font-medium">{{ __('Artikel') }}</p>
                            <p class="text-sm text-zinc-600 dark:text-zinc-300 mt-0.5">
                                {{ $order->items->sum('quantity') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-400 font-medium">{{ __('Gesamt') }}</p>
                            <p class="text-sm font-bold text-blue-600 dark:text-blue-400 mt-0.5">
                                {{ number_format($order->items->sum(fn($item) => $item->price * $item->quantity), 2, ',', '.') }} €
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <!-- Status-Badges -->
                        @if($order->status === 1)
                            <span class="inline-flex items-center rounded-md bg-amber-50 dark:bg-amber-950/30 px-2 py-0.5 text-xs font-medium text-amber-800 dark:text-amber-400 ring-1 ring-inset ring-amber-600/10">
                                {{ __('In Bearbeitung') }}
                            </span>
                        @elseif($order->status === 2)
                            <span class="inline-flex items-center rounded-md bg-green-50 dark:bg-green-950/30 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-400 ring-1 ring-inset ring-green-600/10">
                                {{ __('Erledigt') }}
                            </span>
                        @elseif($order->status === 3)
                            <span class="inline-flex items-center rounded-md bg-red-50 dark:bg-red-950/30 px-2 py-0.5 text-xs font-medium text-red-700 dark:text-red-400 ring-1 ring-inset ring-red-600/10">
                                {{ __('Storniert') }}
                            </span>
                        @endif

                        <flux:icon name="{{ $isOpen ? 'chevron-up' : 'chevron-down' }}" class="h-4 w-4 text-zinc-400 dark:text-zinc-500" />
                    </div>
                </div>

                <!-- Ausklappbare Details -->
                @if($isOpen)
                    <div class="p-4 bg-zinc-50/50 dark:bg-zinc-800/10 border-t border-zinc-100 dark:border-zinc-800 space-y-5">
                        
                        <!-- 1. Artikelliste -->
                        <div class="space-y-2.5">
                            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ __('Bestellte Artikel') }}</p>
                            @foreach($order->items as $item)
                                <div class="flex items-center justify-between text-sm text-zinc-700 dark:text-zinc-300">
                                    <div class="flex items-center gap-2">
                                        <span>{{ $item->quantity }}x</span>
                                        <span>{{ $item->product?->name ?? __('Unbekanntes Produkt') }}</span>
                                    </div>
                                    <span>{{ number_format($item->price * $item->quantity, 2, ',', '.') }} €</span>
                                </div>
                            @endforeach
                        </div>

                        <!-- 2. Adressen -->
                        <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800/60 flex flex-col sm:flex-row justify-between gap-6 text-xs text-zinc-500 dark:text-zinc-400 w-full">
                            <!-- Lieferadresse (Ganz links) -->
                            <div class="flex-1 min-w-0 text-left">
                                <p class="font-semibold text-zinc-700 dark:text-zinc-400 mb-1.5 uppercase tracking-wider text-[10px]">{{ __('Lieferadresse') }}</p>
                                <p class="text-sm text-zinc-600 dark:text-zinc-300 font-medium">{{ $order->deliver_address1 }}</p>
                                @if($order->deliver_address2) <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $order->deliver_address2 }}</p> @endif
                                <p class="mt-0.5">{{ $order->deliver_postal_code }} {{ $order->deliver_city }}</p>
                                <p>{{ $order->deliver_country }}</p>
                            </div>
                            
                            <!-- Rechnungsadresse (Ganz rechts - gleiche Textausrichtung) -->
                            <div class="flex-1 min-w-0 text-left sm:text-right">
                                <div class="inline-block text-left">
                                    <p class="font-semibold text-zinc-700 dark:text-zinc-400 mb-1.5 uppercase tracking-wider text-[10px]">{{ __('Rechnungsadresse') }}</p>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-300 font-medium">{{ $order->invoice_address1 }}</p>
                                    @if($order->invoice_address2) <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $order->invoice_address2 }}</p> @endif
                                    <p class="mt-0.5">{{ $order->invoice_postal_code }} {{ $order->invoice_city }}</p>
                                    <p>{{ $order->invoice_country }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="text-center py-12 bg-zinc-50 dark:bg-zinc-800/20 rounded-xl border border-dashed border-zinc-200 dark:border-zinc-700">
                <p class="text-zinc-500 dark:text-zinc-400 text-sm">{{ __('Sie haben noch keine Bestellungen getätigt.') }}</p>
            </div>
        @endforelse
    </div>
</section>
