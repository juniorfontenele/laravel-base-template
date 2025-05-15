<?php

declare(strict_types = 1);

namespace App\Console\Commands;

use App\Events\System\ApplicationStarted;
use Illuminate\Console\Command;

class StartedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:started';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send application started event';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $host = gethostname() ?: 'unknown';
        $ip = gethostbyname($host);
        event(new ApplicationStarted($host, $ip, config('container.role', 'app')));
    }
}
