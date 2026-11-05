<?php

use App\Models\Product;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Gate;

new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads;

    public string $name = '';
    public string $description = '';
    public string $price = '0.0';
    public string $quantity = '0';
    public bool $is_active = false;

    public $image;

    // Sichert die Komponente direkt beim Initialisieren ab (Prüft bei JEDER Interaktion mit dem Server)
    public function boot(): void
    {
        if (Gate::denies('admin')) {
            abort(403, __('Diese Aktion ist nur für Administratoren erlaubt.'));
        }
    }

    public function createProduct(): void
    {        
        // Zusätzlicher Schutz direkt in der Ausführungsmethode
        if (Gate::denies('admin')) {
            abort(403, __('Diese Aktion ist nur für Administratoren erlaubt.'));
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:2048'], // 2MB Max, nur Bilder
        ], [
            // Fehlermeldungen für den Produktnamen
            'name.required' => __('Bitte geben Sie einen Produktnamen ein.'),
            'name.string' => __('Der Produktname muss ein gültiger Text sein.'),
            'name.max' => __('Der Produktname darf maximal 255 Zeichen lang sein.'),

            // Fehlermeldungen für den Preis
            'price.required' => __('Bitte geben Sie einen Preis ein.'),
            'price.numeric' => __('Der Preis muss eine gültige Zahl sein.'),
            'price.min' => __('Der Preis darf nicht kleiner als 0 € sein.'),

            // Fehlermeldungen für die Menge
            'quantity.required' => __('Bitte geben Sie die Menge an.'),
            'quantity.integer' => __('Die Menge muss eine ganze Zahl sein.'),
            'quantity.min' => __('Die Menge darf nicht kleiner als 0 sein.'),

            // Fehlermeldungen für das Bild
            'image.image' => __('Die hochgeladene Datei muss ein echtes Bild sein.'),
            'image.max' => __('Das Bild darf maximal 2 MB groß sein.'),
        ]);

        // Bild-Upload verarbeiten, falls vorhanden
        if ($this->image) {
            // Speichert die Datei im Ordner "storage/app/public/products"
            $path = $this->image->store('products', 'public');
            // Der generierte Pfad (z.B. "products/xyz.jpg") wird für die DB vorbereitet
            $validated['img_path'] = $path;
        }

        Product::create($validated);

        session()->flash('status', __('Neues Produkt erfolgreich erstellt!'));

        $this->redirectRoute('products.products');
    }

};
?>
<section class="w-full">
    <div class="relative mb-6 w-full border-b border-zinc-200 pb-5 dark:border-zinc-700">
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Produkte</h1>

        <flux:subheading size="lg" class="mt-1 text-zinc-500 dark:text-zinc-400">Erstellen Sie ein neues Produkt für Ihren Shop</flux:subheading>
    </div>

    <div class="my-6 max-w-xl">
        <form wire:submit="createProduct" class="my-6 w-full space-y-6">
            
            <!-- Produktname -->
            <flux:input wire:model="name" label="{{ __('Produktname') }}" type="text" name="name" autofocus />

            <!-- Beschreibung -->
            <flux:textarea wire:model="description" label="{{ __('Beschreibung') }}" name="description" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Preis -->
                <flux:input wire:model="price" label="{{ __('Preis (€)') }}" type="number" step="0.01" name="price" />

                <!-- Menge -->
                <flux:input wire:model="quantity" label="{{ __('Menge') }}" type="number" name="quantity" />
            </div>

            <!-- 5. Bild-Eingabefeld (Nutzt Flux UI Input) -->
            <flux:input wire:model="image" label="{{ __('Produktbild') }}" type="file" name="image" accept="image/*" />

            <!-- Optionale Live-Vorschau des Bildes vor dem Speichern -->
            @if ($image)
                <div class="mt-2">
                    <p class="text-xs text-zinc-500 mb-1">Vorschau:</p>
                    <img src="{{ $image->temporaryUrl() }}" class="w-32 h-32 object-cover rounded-lg border border-zinc-200 dark:border-zinc-700">
                </div>
            @endif

            <!-- Aktiv Status (Checkbox / Switch) -->
            <flux:checkbox wire:model="is_active" label="{{ __('Sichtbar im Shop') }}" name="is_active" />

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button type="submit" class="w-full bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700">{{ __('Produkt erstellen') }}</flux:button>
                </div>

                <!-- Erfolgsmeldung -->
                <x-action-message class="me-3 text-sm font-medium text-green-600 dark:text-green-400" on="product-created">{{ __('Erfolgreich erstellt!') }}</x-action-message>
            </div>
        </form>
    </div>
</section>
