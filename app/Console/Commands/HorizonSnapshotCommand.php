<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class HorizonSnapshotCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:horizon-snapshot-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Take Horizon snapshot for metrics and monitoring';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info('Taking Horizon snapshot...');

            // Call the built-in horizon:snapshot command
            $this->call('horizon:snapshot');

            $this->info('Horizon snapshot taken successfully!');
            
            Log::info('Horizon snapshot completed', [
                'timestamp' => now()->toDateTimeString(),
                'command' => 'app:horizon-snapshot-command',
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to take Horizon snapshot: ' . $e->getMessage());
            
            Log::error('Horizon snapshot failed', [
                'error' => $e->getMessage(),
                'command' => 'app:horizon-snapshot-command',
            ]);

            return Command::FAILURE;
        }
    }
}
