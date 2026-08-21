<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderConsumable;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    private const PRODUCTS = [
        'all' => 'Todos los productos',
        'plates' => 'Placas RX',
        'plate_envelopes' => 'Sobres de placas',
        'cds' => 'CD entregados',
        'iopamidol' => 'Iopamidol',
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request);
        $product = array_key_exists($request->string('product')->toString(), self::PRODUCTS)
            ? $request->string('product')->toString()
            : 'all';

        $orders = $this->ordersBetween($startDate, $endDate);
        $periodDays = $startDate->diffInDays($endDate) + 1;
        $previousEnd = $startDate->subDay();
        $previousStart = $previousEnd->subDays($periodDays - 1);
        $previousSummary = $this->consumptionSummary($this->ordersBetween($previousStart, $previousEnd));
        $summary = $this->consumptionSummary($orders, $previousSummary['totals']);
        $monthlyConsumption = $this->monthlyConsumption($orders);
        $recentOrders = $orders->take(8)->map(fn (Order $order) => $this->orderConsumptionRow($order));
        $topConsumables = $this->topConsumables($orders->pluck('id'));
        $filters = [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'product' => $product,
            'product_label' => self::PRODUCTS[$product],
            'products' => self::PRODUCTS,
            'period_label' => $startDate->translatedFormat('d M Y').' — '.$endDate->translatedFormat('d M Y'),
        ];

        return view('home', compact('summary', 'monthlyConsumption', 'recentOrders', 'topConsumables', 'filters'));
    }

    private function dateRange(Request $request): array
    {
        $end = $this->safeDate($request->input('end_date')) ?? CarbonImmutable::today();
        $start = $this->safeDate($request->input('start_date')) ?? $end->startOfMonth();

        return $start->greaterThan($end) ? [$end, $start] : [$start, $end];
    }

    private function safeDate(?string $value): ?CarbonImmutable
    {
        if (! $value || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);
            return $date->format('Y-m-d') === $value ? $date : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function ordersBetween(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return Order::with(['admissionForm', 'consumables.reagent', 'patient', 'agreement'])
            ->whereBetween('fecha_orden', [$start->startOfDay(), $end->endOfDay()])
            ->latest('fecha_orden')
            ->get();
    }

    private function consumptionSummary(Collection $orders, ?array $previousTotals = null): array
    {
        $totals = ['plates' => 0.0, 'plate_envelopes' => 0.0, 'cds' => 0.0, 'iopamidol' => 0.0, 'orders' => $orders->count()];

        foreach ($orders as $order) {
            $row = $this->orderConsumptionRow($order);
            foreach (['plates', 'plate_envelopes', 'cds', 'iopamidol'] as $key) {
                $totals[$key] += $row[$key];
            }
        }

        $definitions = [
            ['key' => 'plates', 'label' => 'Placas RX', 'value' => $totals['plates'], 'unit' => 'unidades', 'accent' => 'blue', 'icon' => '▣'],
            ['key' => 'plate_envelopes', 'label' => 'Sobres de placas', 'value' => $totals['plate_envelopes'], 'unit' => 'unidades', 'accent' => 'amber', 'icon' => '✉'],
            ['key' => 'cds', 'label' => 'CD entregados', 'value' => $totals['cds'], 'unit' => 'unidades', 'accent' => 'green', 'icon' => '◉'],
            ['key' => 'iopamidol', 'label' => 'Iopamidol', 'value' => $totals['iopamidol'], 'unit' => 'ml / unid.', 'accent' => 'purple', 'icon' => '◆'],
        ];

        $cards = collect($definitions)->map(function (array $card) use ($previousTotals) {
            $previous = (float) ($previousTotals[$card['key']] ?? 0);
            $card['change'] = $previous > 0 ? (($card['value'] - $previous) / $previous) * 100 : null;
            return $card;
        })->all();

        return compact('totals', 'cards');
    }

    private function monthlyConsumption(Collection $orders): Collection
    {
        return $orders->filter(fn (Order $order) => $order->fecha_orden !== null)
            ->sortBy('fecha_orden')
            ->groupBy(fn (Order $order) => $order->fecha_orden->format('Y-m'))
            ->map(function (Collection $monthOrders) {
                $rows = $monthOrders->map(fn (Order $order) => $this->orderConsumptionRow($order));
                return [
                    'label' => Str::ucfirst($monthOrders->first()->fecha_orden->translatedFormat('M y')),
                    'plates' => $rows->sum('plates'),
                    'plate_envelopes' => $rows->sum('plate_envelopes'),
                    'cds' => $rows->sum('cds'),
                    'iopamidol' => $rows->sum('iopamidol'),
                ];
            })->values();
    }

    private function orderConsumptionRow(Order $order): array
    {
        $data = $order->admissionForm?->data ?? [];
        $quantities = (array) ($data['delivery_quantities'] ?? []);
        $consumables = $order->consumables ?? collect();
        $plates = (float) ($quantities['PLACAS'] ?? $data['plates_count'] ?? $this->sumConsumableLike($consumables, ['placa']));
        $cds = (float) ($quantities['CD'] ?? $this->fallbackSelectedDeliveryQuantity($data, 'CD'));
        $plateEnvelopes = (float) ($quantities['SOBRES'] ?? $quantities['SOBRE'] ?? $this->sumConsumableLike($consumables, ['sobre']));
        if ($plateEnvelopes <= 0 && $plates > 0) {
            $plateEnvelopes = $plates;
        }

        return ['order' => $order, 'plates' => $plates, 'plate_envelopes' => $plateEnvelopes, 'cds' => $cds, 'iopamidol' => $this->sumConsumableLike($consumables, ['iopamidol'])];
    }

    private function sumConsumableLike(Collection $consumables, array $needles): float
    {
        return (float) $consumables->filter(function ($consumable) use ($needles) {
            $name = Str::lower($consumable->reagent->nombre ?? '');
            return collect($needles)->contains(fn ($needle) => str_contains($name, $needle));
        })->sum('cantidad');
    }

    private function fallbackSelectedDeliveryQuantity(array $data, string $item): int
    {
        $selected = array_merge((array) ($data['delivery_options'] ?? []), (array) ($data['delivery_media_options'] ?? []));
        return in_array($item, $selected, true) ? 1 : 0;
    }

    private function topConsumables(Collection $orderIds): Collection
    {
        if ($orderIds->isEmpty()) {
            return collect();
        }

        return OrderConsumable::query()
            ->select('reagent_id', DB::raw('SUM(cantidad) as total_used'), DB::raw('COUNT(DISTINCT order_id) as orders_count'))
            ->whereIn('order_id', $orderIds)
            ->with('reagent')->groupBy('reagent_id')->orderByDesc('total_used')->limit(6)->get();
    }
}
