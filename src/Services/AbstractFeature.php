<?php

namespace CrixuAMG\FeatureControl\Services;

use CrixuAMG\FeatureControl\Models\Feature;
use CrixuAMG\FeatureControl\Enums\WaveInterval;
use CrixuAMG\FeatureControl\Contracts\ManualRelease;
use CrixuAMG\FeatureControl\Contracts\ReleasesInWaves;
use CrixuAMG\FeatureControl\Contracts\RetiringFeature;
use CrixuAMG\FeatureControl\Contracts\ScheduledRelease;
use CrixuAMG\FeatureControl\Contracts\ReleasesToSpecificUsers;

abstract class AbstractFeature
{
    abstract public function getKey(): string;

    public function getDescription(): ?string
    {
        return null;
    }

    protected function getFeature(): ?Feature
    {
        $interfaces = $this->contracts();
        $feature = Feature::firstOrCreate([
            'key' => $this->getKey(),
        ]);
        $releaseIsScheduled = $interfaces->contains(ScheduledRelease::class);
        
        if ($feature && $feature->scheduled_release !== $releaseIsScheduled) {
            $feature->update([
                'scheduled_release' => $releaseIsScheduled,
            ]);
        }
        
        return $feature;
    }

    /**
     * It returns a collection of all the interfaces that the class implements
     *
     * @return A collection of the interfaces that the class implements.
     */
    public function contracts()
    {
        return collect(class_implements(get_called_class()));
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
     * @return bool|null return value of the update method.
     */
    public function release(): ?bool
    {
        $interfaces = $this->contracts();
        $feature = $this->getFeature() ?: Feature::create([
            'key'               => $this->getKey(),
            'scheduled_release' => $interfaces->contains(ScheduledRelease::class),
        ]);

        if ($interfaces->contains(RetiringFeature::class)) {
            /** @var RetiringFeature $this */
            $retiresOption = $this->retires();
            if ($retiresOption !== null && $retiresOption >= now()) {
                $feature->retired = true;
            }
        }

        if ($feature->description !== $this->getDescription()) {
            $feature->update([
                'description' => $this->getDescription(),
            ]);
        }

        if ($feature->enabled || $interfaces->contains(ManualRelease::class)) {
            return false;
        }

        if ($feature->scheduled_release) {
            /** @var ScheduledRelease $this */
            throw_unless(
                $interfaces->contains(ScheduledRelease::class),
                'Implement ' . ScheduledRelease::class . ' to enable rolling out to users.'
            );

            if (now() < $this->releaseAtDate()) {
                return false;
            }

            if ($interfaces->contains(ReleasesToSpecificUsers::class)) {
                return $this->releaseToSpecificUsers($feature);
            }

            return $this->releaseInWaves($feature);
        }

        if ($interfaces->contains(ReleasesToSpecificUsers::class)) {
            return $this->releaseToSpecificUsers($feature);
        }

        return $feature->update([
            'enabled'     => true,
            'released_at' => now(),
        ]);
    }

    /**
     * > It returns the number of minutes between each wave
     *
     * @return int The number of minutes between each wave.
     */
    private function calculateInterval(): int
    {
        return $this->waveInterval() * [
                                           WaveInterval::INTERVAL_MINUTES->value => 1,
                                           WaveInterval::INTERVAL_HOURS->value   => 60,
                                           WaveInterval::INTERVAL_DAYS->value    => 24 * 60,
                                           WaveInterval::INTERVAL_WEEKS->value   => 7 * 24 * 60,
                                       ][$this->waveIntervalPeriod()->value];
    }

    /**
     * > It rolls out a feature to a list of users and then updates the feature's `released_at` timestamp
     *
     * @param Feature $feature The feature object that is being released.
     */
    protected function releaseToSpecificUsers(Feature $feature)
    {
        /** @var ReleasesToSpecificUsers $this */
        $users = $this->users();

        $feature->rollOutToUsers($users);

        $feature->update([
            'released_at' => now(),
        ]);
    }

    protected function releaseInWaves(Feature $feature)
    {
        $amount = config('feature-control.user_model')::whereDoesntHave('features', function ($query) {
            $query->where('key', $this->getKey());
        })->count();

        if ($this->contracts()->contains(ReleasesInWaves::class)) {
            /** @var ReleasesInWaves $this */
            if ($feature->released_at && $feature->released_at->diffInMinutes(now()) - $this->calculateInterval() < 0) {
                return false;
            }

            $feature->update([
                'released_at' => now(),
            ]);
            $amount = $this->usersPerWave();
        }

        return $feature->rollOutToUsers($amount);
    }
}
