<?php

namespace App\Entity;

enum LeagueType: string
{
    case TEAM = 'team';
    case INDIVIDUAL = 'individual';
}
