<?php

namespace CrixuAMG\FeatureControl\Console\Commands;

use CrixuAMG\FeatureControl\Services\FeatureControl;
use Illuminate\Console\Command;

class FeatureCheckRelease extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'feature-control:release:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate all data migrations';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        FeatureControl::checkReleases();

        return 0;
    }
}
