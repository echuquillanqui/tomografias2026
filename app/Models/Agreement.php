<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agreement extends Model
{
    use HasFactory;

    protected $fillable = ['nombre_institucion', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(AgreementPrice::class);
    }
}
