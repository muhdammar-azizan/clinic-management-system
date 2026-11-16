<?php

namespace App\Enums;

enum SlotStatus: string
{
    case Available = 'available';
    case Occupied = 'occupied';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
