<?php

namespace Genius\Adapter;

use Genius\Foundation\Http\Query;
use Genius\Foundation\Http\Property;

class Request
{
    public function getProperty()
    {
        return new Property($_SERVER);
    }

    public function getQuery()
    {
        return new Query();
    }
}