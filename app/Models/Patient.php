<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = ['dni', 'nombres', 'apellidos', 'telefono', 'fecha_nacimiento', 'edad'];

    protected $casts = ['fecha_nacimiento' => 'date'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
