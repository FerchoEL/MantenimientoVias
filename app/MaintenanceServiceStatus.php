<?php

namespace App;

enum MaintenanceServiceStatus: string
{
    case Planned = 'planned';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
