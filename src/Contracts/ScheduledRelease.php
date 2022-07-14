<?php

namespace Crixuamg\FeatureControl\Contracts;

interface ScheduledRelease
{
    public function releaseAtDate(): \DateTime;
}
