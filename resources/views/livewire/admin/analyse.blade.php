<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

new #[Layout('components.layouts.app')] class extends Component {

    // Sichert die Komponente direkt beim Initialisieren ab (Prüft bei JEDER Interaktion mit dem Server)
    public function boot(): void
    {
        if (Gate::denies('admin')) {
            abort(403, __('Diese Aktion ist nur für Administratoren erlaubt.'));
        }
    }

    // Bereitet die Analyse-Daten für das Blade-Template vor
    public function with(): array
    {
        // 1. Top 3 meistgekaufte Artikel ermitteln (über die order_items-Relation)
        // HINWEIS: Falls deine Pivot-Tabelle anders heißt, passe 'order_items' an
        $topProducts = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', '=', 2) // Ausschließlich erledigte Bestellungen (2) einbeziehen
            ->select('products.name', DB::raw('SUM(order_items.quantity) as total_quantity'), DB::raw('SUM(order_items.quantity * order_items.price) as total_revenue'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_quantity')
            ->limit(3)
            ->get();

        // 2. Top 3 Spender / Kunden mit dem höchsten Gesamt-Einkaufswert ermitteln
        // Berechnet die Summe aller abgeschlossenen oder in Bearbeitung befindlichen Bestellungen eines Users
        $topSpenders = User::select('users.name', 'users.email')
            ->join('orders', 'users.id', '=', 'orders.user_id')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '=', 2) // Umsatz wird nur aus erledigten Bestellungen (2) berechnet
            ->select('users.name', 'users.email', DB::raw('SUM(order_items.quantity * order_items.price) as total_spent'))
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_spent')
            ->limit(3)
            ->get();

        // 3. Allgemeine Kennzahlen für die Übersichtskarten
        $totalRevenue = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', '=', 2) // Nur Umsatz aus erledigten Bestellungen (2) zählen
            ->select(DB::raw('SUM(order_items.quantity * order_items.price) as total'))
            ->value('total') ?? 0.0;

        $ordersCount = Order::where('status', '=', 2)->count(); // Zählt nur noch die tatsächlich erledigten Bestellungen (2)

        return [
            'topProducts' => $topProducts,
            'topSpenders' => $topSpenders,
            'totalRevenue' => $totalRevenue,
            'ordersCount' => $ordersCount,
        ];
    }
};
?>


<section class="w-full">
    <!-- Kopfzeile -->
    <div class="mb-6 flex items-start justify-between gap-4 border-b border-zinc-200 pb-5 dark:border-zinc-700">
        <div class="flex flex-col">
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ __('Analyse & Statistiken') }}</h1>
            <flux:subheading size="lg" class="mt-1 text-zinc-500 dark:text-zinc-400">{{ __('Geschäftliche Auswertungen und Bestseller im Überblick') }}</flux:subheading>
        </div>
    </div>

    <!-- 1. Kennzahlen-Karten (KPIs) -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 max-w-5xl mb-6">
        <!-- Gesamtumsatz Karte -->
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ __('Gesamtumsatz') }}</p>
            <p class="mt-2 text-3xl font-bold text-blue-600 dark:text-blue-400">
                {{ number_format($totalRevenue, 2, ',', '.') }} €
            </p>
        </div>

        <!-- Bestellungen Karte -->
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ __('Erledigte Bestellungen') }}</p>
            <p class="mt-2 text-3xl font-bold text-zinc-900 dark:text-white">
                {{ $ordersCount }}
            </p>
        </div>
    </div>

    <!-- 2. Tabellen-Bereich im Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-5xl">
        
        <!-- Tabelle: Top 3 Meistgekaufte Artikel -->
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="text-sm font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-400 mb-4">{{ __('Top 3 Meistgekaufte Artikel') }}</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-zinc-600 dark:text-zinc-400">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-800 text-xs font-semibold uppercase text-zinc-400">
                            <th class="py-2">{{ __('Artikel') }}</th>
                            <th class="py-2 text-center">{{ __('Menge') }}</th>
                            <th class="py-2 text-right">{{ __('Umsatz') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                        @forelse($topProducts as $index => $product)
                            <tr>
                                <td class="py-3 font-medium text-zinc-900 dark:text-white">
                                    <span class="inline-block w-4 text-zinc-400 font-bold">{{ $index + 1 }}.</span>
                                    {{ $product->name }}
                                </td>
                                <td class="py-3 text-center font-semibold">{{ $product->total_quantity }}</td>
                                <td class="py-3 text-right text-blue-600 dark:text-blue-400 font-medium">{{ number_format($product->total_revenue, 2, ',', '.') }} €</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-4 text-center text-zinc-400 text-xs">{{ __('Keine Daten verfügbar') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tabelle: Top 3 Spender / Top Kunden -->
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="text-sm font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-400 mb-4">{{ __('Top 3 Kunden (Umsatz)') }}</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-zinc-600 dark:text-zinc-400">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-800 text-xs font-semibold uppercase text-zinc-400">
                            <th class="py-2">{{ __('Kunde') }}</th>
                            <th class="py-2 text-right">{{ __('Gesamtumsatz') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                        @forelse($topSpenders as $index => $spender)
                            <tr>
                                <td class="py-3 font-medium text-zinc-900 dark:text-white">
                                    <div class="flex items-start gap-2">
                                        <span class="text-zinc-400 font-bold shrink-0">{{ $index + 1 }}.</span>
                                        <div>
                                            <p class="font-semibold text-zinc-900 dark:text-white">{{ $spender->name }}</p>
                                            <p class="text-xs text-zinc-400 font-normal mt-0.5">{{ $spender->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 text-right font-bold text-green-600 dark:text-green-400">{{ number_format($spender->total_spent, 2, ',', '.') }} €</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="py-4 text-center text-zinc-400 text-xs">{{ __('Keine Daten verfügbar') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
