<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum MaintenanceServiceStatus: string implements HasLabel
{
    case Planned = 'planned';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Planned => 'Planeado',
            self::Active => 'En ejecución',
            self::Completed => 'Finalizado',
            self::Cancelled => 'Cancelado',
        };
    }
}
