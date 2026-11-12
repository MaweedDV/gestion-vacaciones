<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Auditoria extends Model {
    public $timestamps = false;
    protected $table = 'auditorias';
    protected $fillable = ['user_id', 'evento', 'entidad', 'entidad_id', 'detalle'];
    protected function casts(): array { return ['detalle' => 'array', 'created_at' => 'datetime']; }
    public function usuario(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public static function registrar(string $evento, Model $modelo, array $detalle = []): void {
        self::create(['user_id' => auth()->id(), 'evento' => $evento,
            'entidad' => $modelo->getMorphClass(), 'entidad_id' => $modelo->getKey(), 'detalle' => $detalle]);
    }
}
