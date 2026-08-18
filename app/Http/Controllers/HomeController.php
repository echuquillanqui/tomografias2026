<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderConsumable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $orders = Order::with(['admissionForm', 'consumables.reagent', 'patient', 'agreement'])
            ->latest('fecha_orden')
            ->get();

        $summary = $this->consumptionSummary($orders);
        $monthlyConsumption = $this->monthlyConsumption($orders);
        $recentOrders = $orders->take(8)->map(fn (Order $order) => $this->orderConsumptionRow($order));
        $topConsumables = $this->topConsumables();

        return view('home', compact('summary', 'monthlyConsumption', 'recentOrders', 'topConsumables'));
    }

    private function consumptionSummary(Collection $orders): array
    {
        $totals = ['plates' => 0.0, 'plate_envelopes' => 0.0, 'cds' => 0.0, 'iopamidol' => 0.0, 'orders' => $orders->count()];

        foreach ($orders as $order) {
            $row = $this->orderConsumptionRow($order);
            $totals['plates'] += $row['plates'];
            $totals['plate_envelopes'] += $row['plate_envelopes'];
            $totals['cds'] += $row['cds'];
            $totals['iopamidol'] += $row['iopamidol'];
        }

        $cards = [
            ['key' => 'plates', 'label' => 'Placas RX', 'value' => $totals['plates'], 'unit' => 'unid.', 'accent' => 'primary'],
            ['key' => 'plate_envelopes', 'label' => 'Sobres de placas', 'value' => $totals['plate_envelopes'], 'unit' => 'unid.', 'accent' => 'warning'],
            ['key' => 'cds', 'label' => 'CD entregados', 'value' => $totals['cds'], 'unit' => 'unid.', 'accent' => 'success'],
            ['key' => 'iopamidol', 'label' => 'Iopamidol', 'value' => $totals['iopamidol'], 'unit' => 'ml/unid.', 'accent' => 'info'],
        ];

        $max = max(1, ...array_map(fn ($card) => $card['value'], $cards));

        return compact('totals', 'cards', 'max');
    }

    private function monthlyConsumption(Collection $orders): Collection
    {
        return $orders
            ->filter(fn (Order $order) => $order->fecha_orden !== null)
            ->sortBy('fecha_orden')
            ->groupBy(fn (Order $order) => $order->fecha_orden->format('Y-m'))
            ->map(function (Collection $monthOrders, string $month) {
                $rows = $monthOrders->map(fn (Order $order) => $this->orderConsumptionRow($order));

                return [
                    'label' => $monthOrders->first()->fecha_orden->translatedFormat('M Y'),
                    'plates' => $rows->sum('plates'),
                    'plate_envelopes' => $rows->sum('plate_envelopes'),
                    'cds' => $rows->sum('cds'),
                    'iopamidol' => $rows->sum('iopamidol'),
                ];
            })
            ->values()
            ->take(-6);
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
        $iopamidol = $this->sumConsumableLike($consumables, ['iopamidol']);

        return [
            'order' => $order,
            'plates' => $plates,
            'plate_envelopes' => $plateEnvelopes,
            'cds' => $cds,
            'iopamidol' => $iopamidol,
        ];
    }

    private function sumConsumableLike(Collection $consumables, array $needles): float
    {
        return (float) $consumables
            ->filter(function ($consumable) use ($needles) {
                $name = Str::lower($consumable->reagent->nombre ?? '');

                foreach ($needles as $needle) {
                    if (str_contains($name, $needle)) {
                        return true;
                    }
                }

                return false;
            })
            ->sum('cantidad');
    }

    private function fallbackSelectedDeliveryQuantity(array $data, string $item): int
    {
        $selected = array_merge((array) ($data['delivery_options'] ?? []), (array) ($data['delivery_media_options'] ?? []));

        return in_array($item, $selected, true) ? 1 : 0;
    }

    private function topConsumables(): Collection
    {
        return OrderConsumable::query()
            ->select('reagent_id', DB::raw('SUM(cantidad) as total_used'), DB::raw('COUNT(DISTINCT order_id) as orders_count'))
            ->with('reagent')
            ->groupBy('reagent_id')
            ->orderByDesc('total_used')
            ->limit(6)
            ->get();
    }
}
