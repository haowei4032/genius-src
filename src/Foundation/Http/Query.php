<?php

namespace Genius\Foundation\Http;

use Genius\Foundation\Accessor;

class Query
{
    private $source;

    public function __construct()
    {
    }

    public function getString()
    {
    }

    public function __toString()
    {
        $return = [];
        foreach ($this->source as $name => $value) $return[] = $name . '=' . $value;
        return implode('&', $return);
    }
}