<?php

namespace Genius\Foundation\Http;

class Property
{
    private $source;

    public function __construct($source)
    {
        $this->source = $source;
    }

    public function getString($key)
    {
        return (string)$this->source[$key];
    }

    public function optString($key, $failureValue = '')
    {
        return (string)!isset($this->source[$key]) ? $failureValue : $this->source[$key];
    }

    public function getInt($key)
    {
        return (int)$this->source[$key];
    }

    public function optInt($key, $failureValue = 0)
    {
        return (int)!isset($this->source[$key]) ? $failureValue : $this->source[$key];
    }

    public function __toString()
    {
        return '';
    }

    public function toString()
    {

    }
}