<?php

use App\Models\Product;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

new #[Layout('components.layouts.app')] class extends Component {
    // Das zu löschende Produkt
    public Product $product;

    // Sichert die Komponente direkt beim Initialisieren ab (Prüft bei JEDER Interaktion mit dem Server)
    public function boot(): void
    {
        if (Gate::denies('admin')) {
            abort(403, __('Diese Aktion ist nur für Administratoren erlaubt.'));
        }
    }

    // Holt das Produkt über die ID aus der URL
    public function mount(): void
    {
        $urlKeys = array_keys(request()->query());
        $productId = $urlKeys[0] ?? null;

        $this->product = Product::findOrFail($productId);
    }

    // Löscht das Produkt und leitet zurück
    public function destroyProduct(): void
    {
        // Zusätzlicher Schutz direkt in der Ausführungsmethode
        if (Gate::denies('admin')) {
            abort(403, __('Diese Aktion ist nur für Administratoren erlaubt.'));
        }

        // 1. Bild von der Festplatte löschen
        if ($this->product->img_path && Storage::disk('public')->exists($this->product->img_path)) {
            Storage::disk('public')->delete($this->product->img_path);
        }

        // 2. Aus der Datenbank löschen
        $this->product->delete();

        session()->flash('status', __('Produkt erfolgreich gelöscht!'));

        // 3. Zurück zur Übersicht
        $this->redirectRoute('products.products');
    }
};
?>

<section class="w-full">
    <div class="relative mb-6 w-full border-b border-zinc-200 pb-5 dark:border-zinc-700">
        <h1 class="text-2xl font-semibold text-red-600">{{ __('Produkt löschen') }}</h1>
        <flux:subheading size="lg" class="mt-1 text-zinc-500 dark:text-zinc-400">Sind Sie sicher, dass Sie dieses Produkt dauerhaft löschen möchten?</flux:subheading>
    </div>

    <!-- Sicherheits-Box -->
    <div class="my-6 max-w-xl rounded-xl border border-red-200 bg-red-50/50 p-6 dark:border-red-900/50 dark:bg-red-950/20">
        <h2 class="text-lg font-medium text-zinc-900 dark:text-white mb-2">{{ $product->name }}</h2>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">Diese Aktion kann nicht rückgängig gemacht werden. Das Produkt und das zugehörige Bild werden unwiderruflich gelöscht.</p>

        <div class="flex gap-4">
            <!-- Abbrechen -->
            <flux:button href="{{ route('products.products') }}" variant="ghost" class="w-1/2" wire:navigate>{{ __('Abbrechen') }}</flux:button>
            
            <!-- Bestätigen -->
            <flux:button wire:click="destroyProduct" variant="danger" class="w-1/2">{{ __('Ja, endgültig löschen') }}</flux:button>
        </div>
    </div>
</section>
