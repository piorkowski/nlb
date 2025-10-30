<?php

declare(strict_types=1);

namespace App\Entity;

enum LeagueType: string
{
    case TEAM = 'team';
    case INDIVIDUAL = 'individual';
}
