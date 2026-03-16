<?php

namespace App\Enums;

enum HouseholdMemberRole: string
{
    case Owner = 'Owner';
    case Member = 'Member';
    case Viewer = 'Viewer';
}
