<?php

namespace CrixuAMG\FeatureControl\Services;

use CrixuAMG\FeatureControl\Contracts\ReleasesInWaves;
use CrixuAMG\FeatureControl\Contracts\ScheduledRelease;
use CrixuAMG\FeatureControl\Enums\WaveInterval;
use CrixuAMG\FeatureControl\Models\Feature;

abstract class AbstractFeature
{
    abstract public function getKey(): string;

    public function getDescription(): ?string
    {
        //
    }

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
        $interfaces = collect(class_implements(get_called_class()));
        $feature = $this->getFeature() ?: Feature::create([
            'key'               => $this->getKey(),
            'scheduled_release' => $interfaces->contains(ScheduledRelease::class),
        ]);

        if ($feature->description !== $this->getDescription()) {
            $feature->update([
                'description' => $this->getDescription(),
            ]);
        }

        if ($feature->enabled) {
            return;
        }

        if ($feature->scheduled_release) {
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
                if ($feature->released_at && $feature->released_at->diffInMinutes(now()) - $this->calculateInterval() < 0) {
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
}
