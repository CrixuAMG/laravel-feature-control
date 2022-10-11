<?php

namespace CrixuAMG\FeatureControl\Http\Controllers\Api;

use CrixuAMG\FeatureControl\Models\Feature;

class FeatureController
{
    public function index()
    {
        return request()->per_page > 0 ? Feature::paginate() : Feature::all();
    }
}
