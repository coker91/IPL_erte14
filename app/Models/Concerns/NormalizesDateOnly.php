<?php

namespace App\Models\Concerns;

use Carbon\Carbon;

trait NormalizesDateOnly
{
    public function setAttribute($key, $value)
    {
        if (($this->getCasts()[$key] ?? null) === 'date' && $value !== null) {
            $this->attributes[$key] = Carbon::parse($value)->toDateString();

            return $this;
        }

        return parent::setAttribute($key, $value);
    }
}
