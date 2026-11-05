<?php

use App\Models\Product;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads;

    // Das geladene Produkt-Modell
    public Product $product;

    // Formular-Felder
    public string $name = '';
    public string $description = '';
    public string $price = '0.0';
    public string $quantity = '0';
    public bool $is_active = false;
    public $image;

    // Feld für die Lösch-Option
    public bool $remove_image = false;

    // Sichert die Komponente direkt beim Initialisieren ab (Prüft bei JEDER Interaktion mit dem Server)
    public function boot(): void
    {
        if (Gate::denies('admin')) {
            abort(403, __('Diese Aktion ist nur für Administratoren erlaubt.'));
        }
    }

    // Lädt die alten Daten des Produkts beim Öffnen der Seite
    public function mount(Product $product): void
    {
        // Holt alle Schlüssel aus der URL
        $urlKeys = array_keys(request()->query());

        // Nimmt den ersten Schlüssel
        $productId = $urlKeys[0] ?? null;

        // Lädt das echte Produkt mit all seinen Daten aus der Datenbank
        $product = Product::findOrFail($productId);

        $this->product = $product;
        $this->name = $product->name;
        $this->description = $product->description;
        $this->price = $product->price;
        $this->quantity = $product->quantity;
        $this->is_active = (bool)$product->is_active;
    }

    // Speichert die Änderungen ab
    public function updateProduct(): void
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
            'image' => ['nullable', 'image', 'max:2048'], // 2MB Max
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

        // Option A: Wenn das Bild komplett gelöscht werden soll
        if ($this->remove_image) {
            if ($this->product->img_path && Storage::disk('public')->exists($this->product->img_path)) {
                Storage::disk('public')->delete($this->product->img_path);
            }
            $validated['img_path'] = null;
        }
        // Option B: Wenn ein neues Bild das alte ersetzen soll
        elseif ($this->image) {
            // Altes Bild löschen, um Speicherplatz zu sparen
            if ($this->product->img_path && Storage::disk('public')->exists($this->product->img_path)) {
                Storage::disk('public')->delete($this->product->img_path);
            }
            // Neues Bild speichern
            $path = $this->image->store('products', 'public');
            $validated['img_path'] = $path;
        }

        // Produkt aktualisieren
        $this->product->update($validated);

        session()->flash('status', __('Produkt erfolgreich aktualisiert!'));

        $this->redirectRoute('products.products');
    }
};
?>

<section class="w-full">
    <div class="relative mb-6 w-full border-b border-zinc-200 pb-5 dark:border-zinc-700">
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Produkt bearbeiten</h1>
        <flux:subheading size="lg" class="mt-1 text-zinc-500 dark:text-zinc-400">Passen Sie die Details für Ihr Produkt an</flux:subheading>
    </div>

    <div class="my-6 max-w-xl">
        <form wire:submit="updateProduct" class="my-6 w-full space-y-6">
            
            <!-- Produktname -->
            <flux:input wire:model="name" label="{{ __('Produktname') }}" type="text" name="name" autofocus />

            <!-- Beschreibung -->
            <flux:textarea wire:model="description" label="{{ __('Beschreibung (optional)') }}" name="description" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Preis -->
                <flux:input wire:model="price" label="{{ __('Preis (€)') }}" type="number" step="0.01" name="price" />

                <!-- Menge -->
                <flux:input wire:model="quantity" label="{{ __('Menge') }}" type="number" name="quantity" />
            </div>

            <!-- Bild-Eingabefeld -->
            <flux:input wire:model="image" label="{{ __('Neues Produktbild (optional)') }}" type="file" name="image" accept="image/*" />

            <!-- Bild-Vorschau -->
            <div class="mt-2">
                @if ($image)
                    <p class="text-xs text-zinc-500 mb-1">Vorschau (Neu):</p>
                    <img src="{{ $image->temporaryUrl() }}" class="w-32 h-32 object-cover rounded-lg border border-zinc-200 dark:border-zinc-700">
                @elseif ($product->img_path)
                    <p class="text-xs text-zinc-500 mb-1">Aktuelles Bild:</p>
                    <img src="{{ asset('storage/' . $product->img_path) }}" class="mb-3 w-32 h-32 object-cover rounded-lg border border-zinc-200 dark:border-zinc-700">
                    
                    <flux:checkbox wire:model.live="remove_image" label="{{ __('Aktuelles Bild dauerhaft löschen') }}" class="text-xs text-red-600 dark:text-red-400" />
                @endif
            </div>

            <!-- Aktiv Status -->
            <flux:checkbox wire:model="is_active" label="{{ __('Sichtbar im Shop') }}" name="is_active" />

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end w-full">
                    <flux:button type="submit" class="w-full bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700">{{ __('Änderungen speichern') }}</flux:button>
                </div>
            </div>
        </form>
    </div>
</section>
