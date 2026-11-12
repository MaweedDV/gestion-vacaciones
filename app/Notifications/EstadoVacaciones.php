<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EstadoVacaciones extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $solicitudId,
        private readonly string $titulo,
        private readonly string $mensaje,
        private readonly string $tipo
    ) {}

    public function via(object $notifiable): array { return ['database']; }

    public function toArray(object $notifiable): array
    {
        return [
            'solicitud_id' => $this->solicitudId,
            'titulo' => $this->titulo,
            'mensaje' => $this->mensaje,
            'tipo' => $this->tipo,
            'url' => route('vacaciones.index'),
        ];
    }
}
