<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Competencia extends Model
{
    protected $table = 'competencias';
    protected $fillable = ['nombre'];

    public function funcionarios(): BelongsToMany { return $this->belongsToMany(Funcionario::class, 'competencia_funcionario')->withPivot('nivel'); }
}
