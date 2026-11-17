<?php

namespace ItmoSoft\Companies\Enums;

enum CompanyStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Inactive = 'inactive';
}
