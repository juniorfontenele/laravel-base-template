<?php

declare(strict_types = 1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:init {--seed} {--fresh}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize the application';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->runMigrations();
        $this->syncPermissions();
        $this->syncEvents();

        if ($this->option('seed')) {
            $this->seedDatabase();
        }

        $this->generateIdeHelpers();
    }

    protected function runMigrations(): void
    {
        if ($this->option('fresh')) {
            $this->call('migrate:fresh');

            return;
        }

        $this->call('migrate');
    }

    protected function syncPermissions(): void
    {
        $this->call('permissions:sync');
    }

    protected function syncEvents(): void
    {
        $this->call('events:sync-from-file', ['--force' => true]);
    }

    protected function seedDatabase(): void
    {
        $this->call('db:demo-seed');
    }

    protected function generateIdeHelpers(): void
    {
        $this->call('ide-helper:generate');
        $this->call('ide-helper:models', ['--nowrite' => true]);
        $this->call('ide-helper:meta');
    }
}
