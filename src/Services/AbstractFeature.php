<?php

namespace CrixuAMG\FeatureControl\Services;

use CrixuAMG\FeatureControl\Contracts\ReleasesInWaves;
use CrixuAMG\FeatureControl\Contracts\ScheduledRelease;
use CrixuAMG\FeatureControl\Models\Feature;

abstract class AbstractFeature
{
    abstract public function getKey(): string;

    protected function getFeature(): ?Feature
    {
        return Feature::where('key', $this->getKey())->first();
    }

    public function shouldRelease(): bool
    {
        $feature = $this->getFeature();

        if (!$feature) {
            return true;
        }

        return $feature->enabled === false;
    }

    /**
     * "If the feature is not enabled, and it's a per-user roll out, then roll it out to users."
     *
     * The first thing we do is get the feature. If it doesn't exist, or it's already enabled, we return
     *
     * @return The return value of the update method.
     */
    public function release()
    {
        $feature = $this->getFeature();

        if (!$feature || $feature->enabled) {
            return;
        }

        if ($feature->roll_out_per_user) {
            $interfaces = collect(class_implements(get_called_class()));

            /** @var ScheduledRelease $this */
            if ($interfaces->contains(ScheduledRelease::class) && now() < $this->releaseAtDate()) {
                return;
            }

            $amount = config('feature-control.user_model')::whereDoesntHave('features', function ($query) {
                $query->where('key', $this->getKey());
            })->count();

            if ($interfaces->contains(ReleasesInWaves::class)) {
                /** @var ReleasesInWaves $this */
                $amount = $this->usersPerWave();
            }

            return $feature->rollOutToUsers($amount);
        }

        return $feature->update([
            'enabled' => true,
        ]);
    }
}
