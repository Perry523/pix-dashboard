<?php

namespace App\Console\Commands;

use App\Models\Pix;
use App\Models\User;
use App\Services\PixExpirationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PixPerformanceTest extends Command
{
    protected $signature = 'pix:performance-test {--records=1000}';
    protected $description = 'Test PIX expiration performance with large datasets';

    public function handle(PixExpirationService $expirationService)
    {
        $recordCount = $this->option('records');
        $user = User::first();
        
        if (!$user) {
            $this->error('No users found. Please create a user first.');
            return 1;
        }

        $this->info("Creating {$recordCount} test PIX records...");

        $start = microtime(true);
        $records = [];
        for ($i = 0; $i < $recordCount; $i++) {
            $records[] = [
                'user_id' => $user->id,
                'token' => Str::uuid(),
                'status' => 'generated',
                'expires_at' => now()->subMinutes(rand(0, 60)),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        DB::table('pix')->insert($records);
        $createTime = microtime(true) - $start;
        
        $this->info("Created {$recordCount} records in " . round($createTime * 1000, 2) . "ms");

        $this->info("Testing expiration performance...");

        $start = microtime(true);
        $expiredCount = $expirationService->expireTokens();
        $expireTime = microtime(true) - $start;

        $this->info("Expired {$expiredCount} tokens in " . round($expireTime * 1000, 2) . "ms");

        $this->info("Testing list query performance...");
        
        $start = microtime(true);
        Pix::where('user_id', $user->id)->latest()->paginate(20);
        $queryTime = microtime(true) - $start;
        
        $this->info("Fetched paginated list in " . round($queryTime * 1000, 2) . "ms");

        $this->newLine();
        $this->info("Performance Summary:");
        $this->table(
            ['Operation', 'Time (ms)', 'Records', 'Performance'],
            [
                ['Create Records', round($createTime * 1000, 2), $recordCount, $createTime < 1 ? '✅ Good' : '⚠️ Slow'],
                ['Expire Tokens', round($expireTime * 1000, 2), $expiredCount, $expireTime < 0.3 ? '✅ Good' : '⚠️ Slow'],
                ['List Query', round($queryTime * 1000, 2), 20, $queryTime < 0.3 ? '✅ Good' : '⚠️ Slow'],
            ]
        );

        if ($this->confirm('Clean up test records?', true)) {
            DB::table('pix')->where('user_id', $user->id)->delete();
            $this->info('Test records cleaned up.');
        }
        
        return 0;
    }
}
