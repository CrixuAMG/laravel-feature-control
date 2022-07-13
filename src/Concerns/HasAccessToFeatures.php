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
     * @param  string|Feature  $feature  The feature you want to check access to. This can be a string or a Feature object.
     *
     * @return bool A boolean value.
     */
    public function hasAccessToFeature(string|Feature $feature): bool
    {
        if (!$feature instanceof Feature) {
            $feature = Feature::where('key', $feature)->firstOrFail();
        }

        if ($feature->roll_out_per_user) {
            return $this->features()->where('id', $feature->id)->exists();
        }

        return $feature->enabled;
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
     * If the user has access to the feature, return true. Otherwise, attach the feature to the user.
     *
     * @param Feature|string $feature The feature you want to enable access to.
     *
     * @return ?bool A boolean value.
     */
    public function enableAccessToFeature(string|Feature $feature): ?bool
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
