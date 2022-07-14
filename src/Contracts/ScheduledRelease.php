<?php

namespace CrixuAMG\FeatureControl\Contracts;

interface ScheduledRelease
{
    public function releaseAtDate(): \DateTime;
}
