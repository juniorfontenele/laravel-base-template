<?php

declare(strict_types = 1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateEnvBackupFileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-env-backup-file';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a backup of the .env file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $file = fopen(base_path('.env.example'), 'r');

        if (! $file) {
            $this->error('Could not open .env.example file');

            return self::FAILURE;
        }

        $backupFile = fopen(base_path('.env.backup'), 'w');

        if (! $backupFile) {
            $this->error('Could not create .env.backup file');

            return self::FAILURE;
        }

        while ($line = fgetcsv($file, null, '=')) {
            if (count($line) > 1) {
                $writeLine = $line[0] . '=' . (string)env((string) $line[0]) . PHP_EOL; // @phpstan-ignore-line
                fwrite($backupFile, $writeLine);
            }
        }

        fclose($file);
        fclose($backupFile);

        $this->info('Backup env file created successfully');

        return self::SUCCESS;
    }
}
