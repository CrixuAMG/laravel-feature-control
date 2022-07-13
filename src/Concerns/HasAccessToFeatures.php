<?php

namespace CrixuAMG\FeatureControl\Concerns;

use CrixuAMG\FeatureControl\Models\Feature;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasAccessToFeatures
{
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class);
    }

    /**
     * > If the feature is a feature object, get the key, then check if the user has access to that feature
     *
     * @param $feature The feature you want to check access to. This can be a string or a Feature object.
     *
     * @return bool A boolean value.
     */
    public function hasAccessToFeature($feature): bool
    {
        if ($feature instanceof Feature) {
            /* @var $feature Feature */
            $feature = $feature->key;
        }

        return $this->features()->where('key', $feature)->exists();
    }

    /**
     * If the user has access to the feature, remove it
     *
     * @param  feature The feature you want to check access to. This can be a string or a Feature model.
     *
     * @return bool|int A boolean value.
     */
    public function revokeAccessToFeature($feature): bool|int
    {
        if ($feature instanceof Feature) {
            /* @var $feature Feature */
            $feature = $feature->key;
        }

        if (!$this->hasAccessToFeature($feature)) {
            return true;
        }

        return $this->features()->where('key', $feature)->detach();
    }

    /**
     * "If the user doesn't have access to the feature, give them access to it."
     *
     * The first thing we do is check if the feature is an instance of the Feature model. If it isn't, we'll try to find it
     * by its key. If we can't find it, we'll throw an exception
     *
     * @param feature The feature you want to enable access to. This can be a Feature object or a string of the feature's
     * key.
     *
     * @return bool|null A boolean value.
     */
    public function enableAccessToFeature($feature): ?bool
    {
        if (!$feature instanceof Feature) {
            $feature = Feature::where('key', $feature)->firstOrFail();
        }

        if ($this->hasAccessToFeature($feature)) {
            return true;
        }

        return $this->features()->attach($feature);
    }
}
