<?php

namespace Edenlife\Superban\Facades;


class Superban
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() : string
    {
        return 'superban';
    }
}
