<?php

namespace App\Query\Filters;

class OrCurationsNoknown extends AbstractOrCurationsFilter
{
    protected static function column(): string
    {
        return 'curations_noknown';
    }
}
