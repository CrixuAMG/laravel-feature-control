<?php

namespace CrixuAMG\FeatureControl\Http\Controllers\Api;

use Illuminate\Support\Collection;
use CrixuAMG\FeatureControl\Http\Resource\FeatureResource;
use CrixuAMG\FeatureControl\Models\Feature;

class FeatureController
{
    public function index()
    {
        $method = request()->per_page > 0 ? 'paginate' : 'get';

        /** @var Collection $features */
        $features = Feature::where('retired', false)
            ->$method();

        if ($user = request()->user()) {
            $userEnabledFeatures = $user->features()
                ->whereNotIn('id', $features->where('enabled', true)->pluck('id')->all())
                ->where('retired', false)
                ->get();

            foreach ($userEnabledFeatures as $feature) {
                if ($features->contains($feature)) {
                    $features = $features->map(function ($featureItem) use ($feature) {
                        if ($featureItem->id === $feature->id) {
                            $feature->enabled = true;
                            return $feature;
                        }

                        return $featureItem;
                    });
                } else {
                    $features->push($feature);
                }
            }
        }

        return FeatureResource::collection($features);
    }
}
