<?php

namespace CrixuAMG\FeatureControl\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Feature extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * > It updates or creates a new state with the given key and enabled value
     *
     * @param string key The key of the state.
     * @param bool enabled true/false
     *
     * @return Feature The state of the key.
     */
    public static function registerState(string $key, bool $enabled = true): Feature
    {
        return self::updateOrCreate(
            [
                'key' => $key,
            ],
            [
                'enabled' => $enabled,
            ]
        );
    }

    /**
     * > If all of the features in the array are enabled, return true
     *
     * @param  string|string[]  $features  This is the feature key or an array of feature keys.
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
                    if (auth()->check() && auth()->user()->hasAccessToFeature($feature)) {
                        return true;
                    }

                    return $feature->enabled;
                })
                ->count() === count($features);
    }

    /**
     * > Returns true if any of the given features are disabled
     *
     * @param  string|string[]  $features  The features to check.
     *
     * @return bool A boolean value.
     */
    public static function isDisabled($features): bool
    {
        return !self::isEnabled($features);
    }

    /**
     * For each user in the collection, call the `enableAccessToFeature` method on that user.
     *
     * @param Collection users An array of users or a collection of users.
     */
    public function rollOutToUsers(array|Collection $users)
    {
        if (!is_a($users, Collection::class)) {
            $users = collect($users);
        }

        $users->each->enableAccessToFeature($this);
    }

    /**
     * "For each user in the collection, revoke their access to this feature."
     *
     * @param Collection users An array or collection of users to be granted access to the feature.
     */
    public function rollBackUsers(array|Collection $users)
    {
        if (!is_a($users, Collection::class)) {
            $users = collect($users);
        }

        $users->each->revokeAccessToFeature($this);
    }
}
