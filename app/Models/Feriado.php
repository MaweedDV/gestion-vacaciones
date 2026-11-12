<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Feriado extends Model
{
    protected $table = 'feriados';
    protected $fillable = ['fecha', 'nombre'];
    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }
}
