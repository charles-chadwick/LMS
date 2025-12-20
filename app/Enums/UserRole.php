<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin    = 'Admin';
    case Manager  = 'Manager';
    case SalesRep = 'Sales Rep';
}