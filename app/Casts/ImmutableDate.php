<?php

namespace App\Casts;


use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Carbon;

class ImmutableDate implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        // Parse the date string to a Carbon instance
        return $value ? Carbon::createFromFormat('Y-M-D', $value) : null;
    }

    public function set($model, $key, $value, $attributes)
    {
        // Format the date to the desired string format
        return $value ? $value->format('Y-M-D') : null;
    }
}
