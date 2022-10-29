<?php

namespace CrixuAMG\FeatureControl\Http\Controllers\Api;

use CrixuAMG\FeatureControl\Http\Resource\FeatureResource;
use CrixuAMG\FeatureControl\Models\Feature;

class FeatureController
{
    public function index()
    {
        $method = request()->per_page > 0 ? 'paginate' : 'get';

        return FeatureResource::collection(
            Feature::where('retired', false)
                ->$method()
        );
    }
}
