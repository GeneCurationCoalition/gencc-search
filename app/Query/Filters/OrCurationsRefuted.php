<?php

namespace App\Query\Filters;

class OrCurationsRefuted extends AbstractOrCurationsFilter
{
    protected static function column(): string
    {
        return 'curations_refuted';
    }
}
