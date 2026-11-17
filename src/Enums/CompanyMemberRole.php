<?php

namespace ItmoSoft\Companies\Enums;

enum CompanyMemberRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Manager = 'manager';
    case Member = 'member';
}
