<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class DoctorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:doctor {--fix : Attempt to fix issues automatically}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check system requirements and application health';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('🩺 PlayTube Doctor - System Health Check');
        $this->info('=========================================');
        $this->info('');

        $hasErrors = false;

        // 1. Check PHP Extensions
        $this->info('📦 PHP Extensions');
        $this->line('------------------');
        
        $requiredExtensions = [
            'intl' => [
                'required' => true,
                'description' => 'Required for number/currency formatting (Filament)',
                'install' => [
                    'ubuntu' => 'sudo apt-get install php8.3-intl',
                    'alpine' => 'apk add icu-dev && docker-php-ext-install intl',
                    'windows' => 'Uncomment extension=intl in php.ini',
                    'macos' => 'brew install php@8.3 (includes intl)',
                ],
            ],
            'pdo_sqlite' => [
                'required' => true,
                'description' => 'Required for SQLite database',
                'install' => [
                    'ubuntu' => 'sudo apt-get install php8.3-sqlite3',
                    'alpine' => 'docker-php-ext-install pdo_sqlite',
                    'windows' => 'Uncomment extension=pdo_sqlite in php.ini',
                    'macos' => 'brew install php@8.3 (includes sqlite)',
                ],
            ],
            'gd' => [
                'required' => false,
                'description' => 'Recommended for image processing',
                'install' => [
                    'ubuntu' => 'sudo apt-get install php8.3-gd',
                    'alpine' => 'apk add libpng-dev libjpeg-turbo-dev && docker-php-ext-install gd',
                    'windows' => 'Uncomment extension=gd in php.ini',
                    'macos' => 'brew install php@8.3 (includes gd)',
                ],
            ],
            'fileinfo' => [
                'required' => true,
                'description' => 'Required for file type detection',
                'install' => [
                    'ubuntu' => 'Usually bundled with PHP',
                    'alpine' => 'docker-php-ext-install fileinfo',
                    'windows' => 'Uncomment extension=fileinfo in php.ini',
                    'macos' => 'Bundled with PHP',
                ],
            ],
        ];

        foreach ($requiredExtensions as $ext => $info) {
            $loaded = extension_loaded($ext);
            $status = $loaded ? '✅' : ($info['required'] ? '❌' : '⚠️');
            $label = $info['required'] ? 'REQUIRED' : 'OPTIONAL';
            
            $this->line("  {$status} {$ext} [{$label}]");
            
            if (!$loaded) {
                $this->line("     └─ {$info['description']}");
                if ($info['required']) {
                    $hasErrors = true;
                    $this->error("     └─ MISSING! Install instructions:");
                    foreach ($info['install'] as $os => $cmd) {
                        $this->line("        • {$os}: {$cmd}");
                    }
                }
            }
        }
        $this->info('');

        // 2. Check External Tools
        $this->info('🔧 External Tools');
        $this->line('------------------');

        $tools = [
            'ffmpeg' => [
                'required' => false,
                'description' => 'Required for video processing/transcoding',
                'install' => 'sudo apt-get install ffmpeg (Ubuntu) | brew install ffmpeg (macOS)',
            ],
            'ffprobe' => [
                'required' => false,
                'description' => 'Required for video metadata extraction',
                'install' => 'Bundled with ffmpeg',
            ],
        ];

        foreach ($tools as $tool => $info) {
            $path = $this->findExecutable($tool);
            $status = $path ? '✅' : ($info['required'] ? '❌' : '⚠️');
            
            $this->line("  {$status} {$tool}");
            
            if ($path) {
                $this->line("     └─ Found: {$path}");
            } else {
                $this->line("     └─ {$info['description']}");
                if ($info['required']) {
                    $hasErrors = true;
                }
                $this->line("     └─ Install: {$info['install']}");
            }
        }
        $this->info('');

        // 3. Check Database
        $this->info('🗄️  Database');
        $this->line('------------------');

        $dbPath = database_path('database.sqlite');
        $dbExists = File::exists($dbPath);
        
        $this->line("  " . ($dbExists ? '✅' : '❌') . " SQLite file exists");
        if ($dbExists) {
            $this->line("     └─ Path: {$dbPath}");
            $this->line("     └─ Size: " . $this->formatBytes(File::size($dbPath)));
            
            try {
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
                $this->line("     └─ Tables: " . count($tables));
            } catch (\Exception $e) {
                $this->error("     └─ Cannot connect: {$e->getMessage()}");
                $hasErrors = true;
            }
        } else {
            $hasErrors = true;
            $this->error("     └─ Run: touch database/database.sqlite && php artisan migrate");
        }
        $this->info('');

        // 4. Check Storage Link
        $this->info('📁 Storage');
        $this->line('------------------');

        $storageLinkPath = public_path('storage');
        $storageLinked = is_link($storageLinkPath) || File::isDirectory($storageLinkPath);
        
        $this->line("  " . ($storageLinked ? '✅' : '❌') . " Storage symlink");
        if (!$storageLinked) {
            $this->error("     └─ Run: php artisan storage:link");
            if ($this->option('fix')) {
                $this->call('storage:link');
                $this->info("     └─ Fixed!");
            }
        } else {
            $this->line("     └─ public/storage -> storage/app/public");
        }
        $this->info('');

        // 5. Check Environment
        $this->info('⚙️  Environment');
        $this->line('------------------');

        $envChecks = [
            'APP_KEY' => [
                'check' => fn() => !empty(config('app.key')),
                'error' => 'Run: php artisan key:generate',
            ],
            'APP_DEBUG' => [
                'check' => fn() => true,
                'value' => config('app.debug') ? 'true (dev mode)' : 'false (production)',
            ],
            'QUEUE_CONNECTION' => [
                'check' => fn() => true,
                'value' => config('queue.default'),
            ],
            'FILESYSTEM_DISK' => [
                'check' => fn() => true,
                'value' => config('filesystems.default'),
            ],
        ];

        foreach ($envChecks as $key => $info) {
            $ok = $info['check']();
            $this->line("  " . ($ok ? '✅' : '❌') . " {$key}");
            
            if (isset($info['value'])) {
                $this->line("     └─ Value: {$info['value']}");
            }
            
            if (!$ok && isset($info['error'])) {
                $hasErrors = true;
                $this->error("     └─ {$info['error']}");
            }
        }
        $this->info('');

        // 6. Check Directories
        $this->info('📂 Directories');
        $this->line('------------------');

        $directories = [
            storage_path('app/public/videos') => 'Video uploads',
            storage_path('app/public/thumbnails') => 'Video thumbnails',
            storage_path('app/public/avatars') => 'User avatars',
            storage_path('logs') => 'Application logs',
            storage_path('framework/cache') => 'Cache storage',
            storage_path('framework/sessions') => 'Session storage',
            storage_path('framework/views') => 'Compiled views',
        ];

        foreach ($directories as $path => $description) {
            $exists = File::isDirectory($path);
            $writable = $exists && is_writable($path);
            
            $status = $exists && $writable ? '✅' : '⚠️';
            $this->line("  {$status} {$description}");
            
            if (!$exists) {
                $this->line("     └─ Creating: {$path}");
                if ($this->option('fix') || true) {
                    File::makeDirectory($path, 0755, true);
                }
            } elseif (!$writable) {
                $this->warn("     └─ Not writable: {$path}");
            }
        }
        $this->info('');

        // Summary
        $this->info('=========================================');
        if ($hasErrors) {
            $this->error('❌ Some issues need attention!');
            $this->line('   Run with --fix to auto-fix some issues');
            return Command::FAILURE;
        } else {
            $this->info('✅ All checks passed! PlayTube is ready.');
            return Command::SUCCESS;
        }
    }

    /**
     * Find executable in PATH
     */
    private function findExecutable(string $name): ?string
    {
        $result = Process::run("which {$name} 2>/dev/null");
        
        if ($result->successful()) {
            return trim($result->output());
        }

        // Windows fallback
        $result = Process::run("where {$name} 2>NUL");
        if ($result->successful()) {
            return trim(explode("\n", $result->output())[0]);
        }

        return null;
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
}
