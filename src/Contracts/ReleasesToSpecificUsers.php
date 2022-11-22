<?php

namespace CrixuAMG\FeatureControl\Contracts;

interface ReleasesToSpecificUsers
{
    public function users(): \ArrayAccess;
}
