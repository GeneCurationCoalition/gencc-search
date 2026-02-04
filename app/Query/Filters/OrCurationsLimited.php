<?php

namespace App\Query\Filters;

class OrCurationsLimited extends AbstractOrCurationsFilter
{
    protected static function column(): string
    {
        return 'curations_limited';
    }
}
