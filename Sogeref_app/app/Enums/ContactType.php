<?php

namespace App\Enums;

enum ContactType: string
{
    case Client = 'client';
    case Supplier = 'supplier';
    case Prospect = 'prospect';
}
