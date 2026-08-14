<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = ['ruc', 'razon_social', 'direccion', 'telefono', 'logo_path', 'sale_note_series', 'next_receipt_number'];

    protected $casts = ['next_receipt_number' => 'integer'];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'razon_social' => config('app.name', 'Tomografías 2026'),
        ]);
    }
}
