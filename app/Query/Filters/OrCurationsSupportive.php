<?php

namespace App\Query\Filters;

class OrCurationsSupportive extends AbstractOrCurationsFilter
{
    protected static function column(): string
    {
        return 'curations_supportive';
    }
}
