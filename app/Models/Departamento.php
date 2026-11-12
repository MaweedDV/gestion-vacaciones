<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departamento extends Model
{
    protected $table = 'departamentos';
    protected $fillable = ['nombre', 'dotacion_minima', 'activo'];
    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function funcionarios(): HasMany { return $this->hasMany(Funcionario::class); }
}
