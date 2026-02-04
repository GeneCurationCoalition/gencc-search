<?php

namespace App\Query\Filters;

class OrCurationsAnimal extends AbstractOrCurationsFilter
{
    protected static function column(): string
    {
        return 'curations_animal';
    }
}
