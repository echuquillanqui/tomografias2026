<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashExpense extends Model
{
    use HasFactory;

    protected $fillable = ['cash_fixed_expense_id', 'fixed_expense_period', 'fecha_egreso', 'descripcion', 'monto', 'archivo_path', 'created_by'];

    protected $casts = ['fecha_egreso' => 'date', 'monto' => 'decimal:2'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fixedExpense(): BelongsTo
    {
        return $this->belongsTo(CashFixedExpense::class, 'cash_fixed_expense_id');
    }
}
