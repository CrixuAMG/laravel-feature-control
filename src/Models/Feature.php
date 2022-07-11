<?php

namespace CrixuAMG\FeatureControl\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Feature extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * > If all of the features in the array are enabled, return true
     *
     * @param string|string[] $features This is the feature key or an array of feature keys.
     *
     * @return bool A collection of features that are enabled.
     */
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

    /**
     * > Returns true if any of the given features are disabled
     *
     * @param string|string[] $features The features to check.
     *
     * @return bool A boolean value.
     */
    public static function isDisabled($features): bool
    {
        return !self::isEnabled($features);
    }
}
