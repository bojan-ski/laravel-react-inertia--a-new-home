<?php

namespace App\Enums;

enum UserRole: string
{
    case RegularUser = 'regular_user';
    case Agency = 'agency';
    case Admin = 'admin';
}
