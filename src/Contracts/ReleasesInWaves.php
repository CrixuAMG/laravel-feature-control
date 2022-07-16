<?php

namespace CrixuAMG\FeatureControl\Contracts;

use CrixuAMG\FeatureControl\Enums\WaveInterval;

interface ReleasesInWaves
{
    public function usersPerWave(): int;

    public function waveInterval(): int;

    public function waveIntervalPeriod(): WaveInterval;
}
