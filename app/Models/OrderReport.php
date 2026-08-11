<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderReport extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'titulo', 'tecnica', 'informe', 'impresion', 'recomendaciones', 'contenido', 'medico_firmante_id'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(OrderReportAttachment::class);
    }

    public function medicoFirmante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'medico_firmante_id');
    }
}
