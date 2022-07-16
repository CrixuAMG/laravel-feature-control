<?php

namespace CrixuAMG\FeatureControl\Enums;

enum WaveInterval: string
{
    case INTERVAL_MINUTES = 'minutes';
    case INTERVAL_HOURS = 'hours';
    case INTERVAL_DAYS = 'days';
    case INTERVAL_WEEKS = 'weeks';
}
