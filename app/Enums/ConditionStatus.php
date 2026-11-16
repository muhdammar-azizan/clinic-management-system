<?php

namespace App\Enums;

enum ConditionStatus: string
{
    case Stable = 'stable';
    case Recovering = 'recovering';
    case Critical = 'critical';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
