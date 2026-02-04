<?php

namespace App\Query\Filters;

class OrCurationsStrong extends AbstractOrCurationsFilter
{
    protected static function column(): string
    {
        return 'curations_strong';
    }
}
