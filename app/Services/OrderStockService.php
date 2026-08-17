<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Reagent;
use App\Models\StockMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderStockService
{
    private const MOVEMENT_REASON = 'Consumo automático de orden';

    /**
     * Replace an order's consumables and reconcile their stock movements atomically.
     */
    public function sync(Order $order, Collection|array $rows): void
    {
        DB::transaction(function () use ($order, $rows): void {
            $this->syncWithinTransaction($order, $rows);
        });
    }

    private function syncWithinTransaction(Order $order, Collection|array $rows): void
    {
        $desired = collect($rows)
            ->filter(fn ($row) => (float) ($row['cantidad'] ?? 0) > 0)
            ->groupBy(fn ($row) => (int) $row['reagent_id'])
            ->map(fn ($items) => (float) $items->sum(fn ($row) => (float) $row['cantidad']));

        $movements = StockMovement::query()
            ->where('order_id', $order->id)
            ->where('motivo', self::MOVEMENT_REASON)
            ->get()
            ->keyBy('reagent_id');

        $reagentIds = $desired->keys()->merge($movements->keys())->unique()->sort()->values();
        $reagents = Reagent::query()->whereIn('id', $reagentIds)->lockForUpdate()->get()->keyBy('id');

        foreach ($reagentIds as $reagentId) {
            $quantity = (float) ($desired[$reagentId] ?? 0);
            $movement = $movements->get($reagentId);
            $previouslyConsumed = (float) ($movement?->cantidad ?? 0);
            $difference = $quantity - $previouslyConsumed;
            $reagent = $reagents->get($reagentId);

            if ($reagent && $difference !== 0.0) {
                $reagent->stock_actual = (float) $reagent->stock_actual - $difference;
                $reagent->save();
            }

            if ($quantity > 0) {
                StockMovement::updateOrCreate(
                    ['order_id' => $order->id, 'reagent_id' => $reagentId, 'motivo' => self::MOVEMENT_REASON],
                    ['tipo_movimiento' => 'Salida', 'cantidad' => $quantity, 'user_id' => auth()->id(), 'fecha_movimiento' => now()]
                );
            } elseif ($movement) {
                $movement->delete();
            }
        }

        $order->consumables()->delete();
        foreach ($desired as $reagentId => $quantity) {
            $order->consumables()->create(['reagent_id' => $reagentId, 'cantidad' => $quantity]);
        }
    }
}
