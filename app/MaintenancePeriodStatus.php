<?php

namespace App;

enum MaintenancePeriodStatus: string
{
    case Open = 'open';
    case Submitted = 'submitted';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';
    case Closed = 'closed';
}
