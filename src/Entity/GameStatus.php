<?php

namespace App\Entity;

enum GameStatus: string
{
    case CANCELLED = 'cancelled';
    case CONFIRMED = 'confirmed';
    case DRAFT = 'draft';
    case FINISHED = 'finished';
    case IN_PROGRESS = 'in_progress';
    case PLANNED = 'planned';
}
