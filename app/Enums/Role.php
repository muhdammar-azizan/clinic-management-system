<?php

namespace App\Enums;

enum Role: string
{
    case Patient = 'patient';
    case Doctor = 'doctor';
    case Admin = 'admin';

    /**
     * Human readable label for the UI.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Route name of the dashboard for this role.
     */
    public function dashboardRoute(): string
    {
        return $this->value.'.dashboard';
    }
}
