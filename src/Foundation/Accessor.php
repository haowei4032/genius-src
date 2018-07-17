<?php

namespace Genius\Foundation;

interface Accessor
{
    public function get($key);

    public function getString($key);

    public function getInt($key);

    public function getBoolean($key);

    public function optString($key, $failureValue);

    public function optInt($key, $failureValue);

    public function optBoolean($key, $failureValue);

    public function put($key, $value);

    public function putString($key, $value);

    public function putInt($key, $value);

    public function putBoolean($key, $value);


}