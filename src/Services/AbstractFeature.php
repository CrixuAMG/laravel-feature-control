<?php

namespace CrixuAMG\FeatureControl\Services;

abstract class AbstractFeature
{
    abstract public function getKey(): string;

    public function shouldRelease(): bool
    {
        return FeatureControl::where('key', $this->getKey())
            ->where('roll_out_per_user', false)
            ->first()
            ?->enabled === false;
    }
}
