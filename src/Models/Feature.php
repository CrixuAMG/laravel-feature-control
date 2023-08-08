<?php

namespace CrixuAMG\FeatureControl\Models;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Feature extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $dates = [
        'released_at',
    ];

    protected $casts = [
        'enabled'           => 'boolean',
        'retired'           => 'boolean',
        'scheduled_release' => 'boolean',
    ];

    private static $cache = [];

    public static function resetCache()
    {
        self::$cache = [];
    }

    public function users()
    {
        return $this->belongsToMany(config('feature-control.user_model'));
    }

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
     * @param string|string[] $features This is the feature key or an array of feature keys.
     *
     * @return bool A collection of features that are enabled.
     */
    public static function isEnabled($features): bool
    {
        $featureKey = json_encode($features);
        if (array_key_exists($featureKey, self::$cache)) {
            return self::$cache[$featureKey];
        }

        if (is_string($features)) {
            $features = (array)$features;
        }

        $featureCheck = self::whereIn('key', $features)->get()
                ->filter(function (Feature $feature) {
                    if ($feature->scheduled_release) {
                        return auth()->check() && auth()->user()->hasAccessToFeature($feature);
                    }

                    return $feature->enabled;
                })
                ->count() === count($features);

        self::$cache[$featureKey] = $featureCheck;

        return $featureCheck;
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

    /**
     * It takes a collection of users and enables access to the feature for each of them
     *
     * @param int|array|Collection $users This can be an integer, an array of user ids, or a collection of users. If
     *                                    it's an integer, it will randomly select that many users from the database.
     */
    public function rollOutToUsers(int|array|Collection $users)
    {
        if (is_int($users)) {
            $users = config('feature-control.user_model')::whereDoesntHave('features', function ($query) {
                $query->where('key', $this->key);
            })
                ->inRandomOrder()
                ->take($users)
                ->get();
        }

        if (!is_a($users, Collection::class)) {
            $users = collect($users);
        }

        $users->each(function ($user) {
            if (!$user->hasAccessToFeature($this)) {
                $user->enableAccessToFeature($this);
            }
        });
    }

    /**
     * "Remove the feature from the specified users."
     *
     * The function accepts a single user, an array of users, or a collection of users. If an integer is passed, it
     * will
     * randomly select that many users from the database
     *
     * @param int|array|Collection $users This can be an integer, an array of user IDs, or a collection of users. If
     *                                    it's an integer, it will randomly select that many users from the database.
     */
    public function rollBackUsers(int|array|Collection $users)
    {
        if (is_int($users)) {
            $users = config('feature-control.user_model')::whereHas('features', function ($query) {
                $query->where('key', $this->key);
            })
                ->inRandomOrder()
                ->take($users)
                ->get();
        }

        if (!is_a($users, Collection::class)) {
            $users = collect($users);
        }

        $users->each(function ($user) {
            if ($user->hasAccessToFeature($this)) {
                $user->revokeAccessToFeature($this);
            }
        });
    }
}
