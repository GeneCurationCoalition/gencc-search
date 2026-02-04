<?php

namespace App\Query\Filters;

class OrCurationsDisputed extends AbstractOrCurationsFilter
{
    protected static function column(): string
    {
        return 'curations_disputed';
    }
}
