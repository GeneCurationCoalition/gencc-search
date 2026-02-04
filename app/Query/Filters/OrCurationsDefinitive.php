<?php

namespace App\Query\Filters;

class OrCurationsDefinitive extends AbstractOrCurationsFilter
{
    protected static function column(): string
    {
        return 'curations_definitive';
    }
}
