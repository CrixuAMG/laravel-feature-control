<?php

namespace CrixuAMG\FeatureControl\Services;

use CrixuAMG\FeatureControl\Contracts\ReleasesInWaves;
use CrixuAMG\FeatureControl\Contracts\ScheduledRelease;
use CrixuAMG\FeatureControl\Enums\WaveInterval;
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
        $interfaces = collect(class_implements(get_called_class()));

        if (!$feature || $feature->enabled) {
            Feature::create([
                'key'               => $this->getKey(),
                'released_at'      => now(),
                'roll_out_per_user' => $interfaces->contains(ScheduledRelease::class),
            ]);

            return;
        }

        if ($feature->roll_out_per_user) {
            /** @var ScheduledRelease $this */
            throw_unless(
                $interfaces->contains(ScheduledRelease::class),
                'Implement '.ScheduledRelease::class.' to enable rolling out to users.'
            );

            if (now() < $this->releaseAtDate()) {
                return;
            }

            $amount = config('feature-control.user_model')::whereDoesntHave('features', function ($query) {
                $query->where('key', $this->getKey());
            })->count();

            if ($interfaces->contains(ReleasesInWaves::class)) {
                /** @var ReleasesInWaves $this */
                $lastRelease = $feature->released_at;
                $intervalType = $this->waveIntervalPeriod()->value;
                $interval = $this->waveInterval();

                $interval = [
                                WaveInterval::INTERVAL_MINUTES->value => $interval * 1,
                                WaveInterval::INTERVAL_HOURS->value   => $interval * 60,
                                WaveInterval::INTERVAL_DAYS->value    => $interval * 24 * 60,
                                WaveInterval::INTERVAL_WEEKS->value   => $interval * 7 * 24 * 60,
                            ][$intervalType];

                dd($this->releaseAtDate()->diffInMinutes($feature->released_at), $this->releaseAtDate()->diffInMinutes($feature->released_at) - $interval, $interval, $intervalType);

                if ($this->releaseAtDate()->diffInMinutes($feature->released_at) % $interval !== 0) {
                    return;
                }

                $feature->update([
                    'released_at' => now(),
                ]);
                $amount = $this->usersPerWave();
            }

            return $feature->rollOutToUsers($amount);
        }

        return $feature->update([
            'enabled'      => true,
            'released_at' => now(),
        ]);
    }
}
