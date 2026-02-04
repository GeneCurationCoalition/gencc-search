<?php

namespace App\Query\Filters;

class OrCurationsModerate extends AbstractOrCurationsFilter
{
    protected static function column(): string
    {
        return 'curations_moderate';
    }
}
