<?php

declare(strict_types = 1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DemoSeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:demo-seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with demo data';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->call('db:seed', ['--class' => 'DemoSeeder']);
    }
}
