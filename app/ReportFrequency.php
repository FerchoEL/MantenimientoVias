<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum ReportFrequency: string implements HasLabel
{
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case Monthly = 'monthly';
    case None = 'none';

    public function getLabel(): string
    {
        return match ($this) {
            self::Weekly => 'Semanal',
            self::Biweekly => 'Quincenal',
            self::Monthly => 'Mensual',
            self::None => 'Sin reporte',
        };
    }
}
