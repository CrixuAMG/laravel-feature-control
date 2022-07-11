<?php

namespace CrixuAMG\FeatureControl\Models;

use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    protected $guarded = [];

    public static function isEnabled($features): bool
    {
        if (is_string($features)) {
            $features = (array) $features;
        }

        return self::whereIn('key', $features)->get()
                ->filter(function (Feature $feature) {
                    // TODO: allow for user checking or other application specific logic
                    return $feature->enabled;
                })
                ->count() === count($features);
    }
}
