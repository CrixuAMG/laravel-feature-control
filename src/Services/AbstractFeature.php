<?php

namespace CrixuAMG\FeatureControl\Services;

use CrixuAMG\FeatureControl\Models\Feature;

abstract class AbstractFeature
{
    abstract public function getKey(): string;

    public function shouldRelease(): bool
    {
        $feature = Feature::where('key', $this->getKey())->first();

        if (!$feature) return true;

        return $feature->enabled === false;
    }

    public function release()
    {
        return Feature::firstOrCreate([
            'key' => $this->getKey(),
        ]);
    }
}
