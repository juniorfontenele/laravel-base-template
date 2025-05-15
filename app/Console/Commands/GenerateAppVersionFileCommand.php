<?php

declare(strict_types = 1);

namespace App\Console\Commands;

use App\Services\VersionService;
use Illuminate\Console\Command;

class GenerateAppVersionFileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-version-file';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates the app version file';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $appVersion = VersionService::version();
        VersionService::writeVersion();
        $this->info("Wrote file version: $appVersion");
    }
}
