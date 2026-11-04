<?php

namespace App;

enum ReportFrequency: string
{
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case Monthly = 'monthly';
    case None = 'none';
}
