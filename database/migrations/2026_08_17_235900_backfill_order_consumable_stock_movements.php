<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const MOVEMENT_REASON = 'Consumo automático de orden';

    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('order_consumables')
                ->join('orders', 'orders.id', '=', 'order_consumables.order_id')
                ->select([
                    'order_consumables.order_id',
                    'order_consumables.reagent_id',
                    'order_consumables.cantidad',
                    'order_consumables.created_at',
                    'orders.fecha_orden',
                    'orders.created_by',
                ])
                ->orderBy('order_consumables.id')
                ->chunk(200, function ($consumables): void {
                    foreach ($consumables as $consumable) {
                        $alreadyRecorded = DB::table('stock_movements')
                            ->where('order_id', $consumable->order_id)
                            ->where('reagent_id', $consumable->reagent_id)
                            ->where('motivo', self::MOVEMENT_REASON)
                            ->exists();

                        if ($alreadyRecorded) {
                            continue;
                        }

                        DB::table('stock_movements')->insert([
                            'reagent_id' => $consumable->reagent_id,
                            'tipo_movimiento' => 'Salida',
                            'cantidad' => $consumable->cantidad,
                            'motivo' => self::MOVEMENT_REASON,
                            'order_id' => $consumable->order_id,
                            'user_id' => $consumable->created_by,
                            'fecha_movimiento' => $consumable->fecha_orden,
                            'created_at' => $consumable->created_at,
                            'updated_at' => $consumable->created_at,
                        ]);

                        DB::table('reagents')
                            ->where('id', $consumable->reagent_id)
                            ->decrement('stock_actual', $consumable->cantidad);
                    }
                });
        });
    }

    public function down(): void
    {
        // This is an intentional data repair. Reverting it could erase legitimate
        // movements created after deployment and incorrectly increase stock.
    }
};
