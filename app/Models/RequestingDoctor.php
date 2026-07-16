<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestingDoctor extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'medico_solicitante_id');
    }
}
