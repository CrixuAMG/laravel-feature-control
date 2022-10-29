<?php

namespace CrixuAMG\FeatureControl\Contracts;

use DateTime;

interface RetiringFeature
{
    public function retires(): DateTime|null;
}
