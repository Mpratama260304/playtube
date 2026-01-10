<x-filament-panels::page>
    @php
        $uploadHealth = $this->getUploadHealth();
        $queueStatus = $this->getQueueStatus();
        $diskSpace = $this->getDiskSpace();
    @endphp

    {{-- Status Banner --}}
    @if($uploadHealth['status'] === 'error')
        <div class="bg-danger-50 dark:bg-danger-400/10 border border-danger-300 dark:border-danger-400/20 rounded-xl p-4 mb-6">
            <div class="flex items-center gap-3">
                <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-danger-500" />
                <div>
                    <h3 class="font-semibold text-danger-700 dark:text-danger-400">System Has Critical Issues</h3>
                    <p class="text-sm text-danger-600 dark:text-danger-300">Uploads may fail. Please review the errors below.</p>
                </div>
            </div>
        </div>
    @elseif($uploadHealth['status'] === 'warning')
        <div class="bg-warning-50 dark:bg-warning-400/10 border border-warning-300 dark:border-warning-400/20 rounded-xl p-4 mb-6">
            <div class="flex items-center gap-3">
                <x-heroicon-o-exclamation-circle class="w-6 h-6 text-warning-500" />
                <div>
                    <h3 class="font-semibold text-warning-700 dark:text-warning-400">System Has Warnings</h3>
                    <p class="text-sm text-warning-600 dark:text-warning-300">Some settings may cause issues. Review recommendations below.</p>
                </div>
            </div>
        </div>
    @else
        <div class="bg-success-50 dark:bg-success-400/10 border border-success-300 dark:border-success-400/20 rounded-xl p-4 mb-6">
            <div class="flex items-center gap-3">
                <x-heroicon-o-check-circle class="w-6 h-6 text-success-500" />
                <div>
                    <h3 class="font-semibold text-success-700 dark:text-success-400">System Health: OK</h3>
                    <p class="text-sm text-success-600 dark:text-success-300">All upload and processing systems are properly configured.</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Errors --}}
    @if(count($uploadHealth['errors']) > 0)
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6 mb-6">
            <h2 class="text-lg font-semibold text-danger-600 dark:text-danger-400 mb-4 flex items-center gap-2">
                <x-heroicon-o-x-circle class="w-5 h-5" />
                Errors (Require Attention)
            </h2>
            <ul class="space-y-2">
                @foreach($uploadHealth['errors'] as $error)
                    <li class="flex items-start gap-2 text-sm text-danger-600 dark:text-danger-400">
                        <span class="mt-1">•</span>
                        <span>{{ $error }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Warnings --}}
    @if(count($uploadHealth['warnings']) > 0)
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6 mb-6">
            <h2 class="text-lg font-semibold text-warning-600 dark:text-warning-400 mb-4 flex items-center gap-2">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
                Warnings
            </h2>
            <ul class="space-y-2">
                @foreach($uploadHealth['warnings'] as $warning)
                    <li class="flex items-start gap-2 text-sm text-warning-600 dark:text-warning-400">
                        <span class="mt-1">•</span>
                        <span>{{ $warning }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Upload Limits --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <x-heroicon-o-cloud-arrow-up class="w-5 h-5 text-primary-500" />
                Upload Limits
            </h2>
            
            <div class="space-y-4">
                <div class="p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg">
                    <div class="text-sm text-gray-600 dark:text-gray-400">Effective Max Upload Size</div>
                    <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                        {{ $uploadHealth['effective_limit']['formatted'] }}
                    </div>
                    <div class="text-xs text-gray-500 mt-1">This is the actual limit users can upload</div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Site Setting (Admin)</div>
                        <div class="font-semibold text-gray-900 dark:text-white">
                            {{ $uploadHealth['site_settings']['max_upload_size_mb'] }} MB
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">PHP upload_max_filesize</div>
                        <div class="font-semibold text-gray-900 dark:text-white">
                            {{ $uploadHealth['php_limits']['upload_max_filesize'] }}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">PHP post_max_size</div>
                        <div class="font-semibold text-gray-900 dark:text-white">
                            {{ $uploadHealth['php_limits']['post_max_size'] }}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">PHP max_execution_time</div>
                        <div class="font-semibold text-gray-900 dark:text-white">
                            {{ $uploadHealth['php_limits']['max_execution_time'] }}s
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Required Tools --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <x-heroicon-o-wrench-screwdriver class="w-5 h-5 text-primary-500" />
                Required Tools
            </h2>
            
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 rounded-lg {{ $uploadHealth['tools']['ffmpeg'] ? 'bg-success-50 dark:bg-success-900/20' : 'bg-danger-50 dark:bg-danger-900/20' }}">
                    <div class="flex items-center gap-2">
                        @if($uploadHealth['tools']['ffmpeg'])
                            <x-heroicon-o-check-circle class="w-5 h-5 text-success-500" />
                        @else
                            <x-heroicon-o-x-circle class="w-5 h-5 text-danger-500" />
                        @endif
                        <span class="font-medium text-gray-900 dark:text-white">FFmpeg</span>
                    </div>
                    <span class="text-sm {{ $uploadHealth['tools']['ffmpeg'] ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                        {{ $uploadHealth['tools']['ffmpeg'] ? 'Installed' : 'Not Found' }}
                    </span>
                </div>

                <div class="flex items-center justify-between p-3 rounded-lg {{ $uploadHealth['tools']['ffprobe'] ? 'bg-success-50 dark:bg-success-900/20' : 'bg-danger-50 dark:bg-danger-900/20' }}">
                    <div class="flex items-center gap-2">
                        @if($uploadHealth['tools']['ffprobe'])
                            <x-heroicon-o-check-circle class="w-5 h-5 text-success-500" />
                        @else
                            <x-heroicon-o-x-circle class="w-5 h-5 text-danger-500" />
                        @endif
                        <span class="font-medium text-gray-900 dark:text-white">FFprobe</span>
                    </div>
                    <span class="text-sm {{ $uploadHealth['tools']['ffprobe'] ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                        {{ $uploadHealth['tools']['ffprobe'] ? 'Installed' : 'Not Found' }}
                    </span>
                </div>

                <div class="flex items-center justify-between p-3 rounded-lg {{ $uploadHealth['storage']['writable'] ? 'bg-success-50 dark:bg-success-900/20' : 'bg-danger-50 dark:bg-danger-900/20' }}">
                    <div class="flex items-center gap-2">
                        @if($uploadHealth['storage']['writable'])
                            <x-heroicon-o-check-circle class="w-5 h-5 text-success-500" />
                        @else
                            <x-heroicon-o-x-circle class="w-5 h-5 text-danger-500" />
                        @endif
                        <span class="font-medium text-gray-900 dark:text-white">Storage Writable</span>
                    </div>
                    <span class="text-sm {{ $uploadHealth['storage']['writable'] ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                        {{ $uploadHealth['storage']['writable'] ? 'Yes' : 'No' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Queue Status --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <x-heroicon-o-queue-list class="w-5 h-5 text-primary-500" />
                Queue Status
            </h2>
            
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Queue Driver</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $queueStatus['connection'] }}</span>
                </div>
                
                <div class="flex items-center justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Workers Running</span>
                    <span class="flex items-center gap-2">
                        @if($queueStatus['workers_running'])
                            <span class="w-2 h-2 bg-success-500 rounded-full animate-pulse"></span>
                            <span class="text-success-600 dark:text-success-400 font-semibold">Active</span>
                        @else
                            <span class="w-2 h-2 bg-warning-500 rounded-full"></span>
                            <span class="text-warning-600 dark:text-warning-400 font-semibold">Not Detected</span>
                        @endif
                    </span>
                </div>
            </div>
            
            @if(!$queueStatus['workers_running'])
                <div class="mt-4 p-3 bg-warning-50 dark:bg-warning-900/20 rounded-lg text-sm text-warning-700 dark:text-warning-300">
                    <strong>Note:</strong> If using sync driver, this is expected. For database/redis queues, ensure workers are running: <code class="bg-warning-100 dark:bg-warning-800 px-1 rounded">php artisan queue:work</code>
                </div>
            @endif
        </div>

        {{-- Disk Space --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <x-heroicon-o-circle-stack class="w-5 h-5 text-primary-500" />
                Disk Space
            </h2>
            
            <div class="space-y-4">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Storage Usage</span>
                        <span class="text-sm font-semibold {{ $diskSpace['warning'] ? 'text-danger-600 dark:text-danger-400' : 'text-gray-900 dark:text-white' }}">
                            {{ $diskSpace['usage_percent'] }}%
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                        <div class="h-3 rounded-full transition-all {{ $diskSpace['warning'] ? 'bg-danger-500' : ($diskSpace['usage_percent'] > 70 ? 'bg-warning-500' : 'bg-success-500') }}" 
                             style="width: {{ min($diskSpace['usage_percent'], 100) }}%"></div>
                    </div>
                </div>
                
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Total</div>
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $diskSpace['total'] }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Used</div>
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $diskSpace['used'] }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Free</div>
                        <div class="font-semibold {{ $diskSpace['warning'] ? 'text-danger-600 dark:text-danger-400' : 'text-success-600 dark:text-success-400' }}">{{ $diskSpace['free'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Help Section --}}
    <div class="mt-6 bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Troubleshooting Upload Issues</h2>
        
        <div class="prose dark:prose-invert prose-sm max-w-none">
            <h3>If uploads fail silently or with "file too large" errors:</h3>
            <ol>
                <li><strong>Check PHP limits</strong>: The <code>upload_max_filesize</code> and <code>post_max_size</code> in php.ini must be >= your Site Setting max upload size.</li>
                <li><strong>Check Nginx/Apache limits</strong>: Web servers have their own limits (e.g., <code>client_max_body_size</code> in Nginx).</li>
                <li><strong>Check Cloudflare/CDN limits</strong>: If using Cloudflare, free plans have 100MB upload limit.</li>
                <li><strong>Check the Laravel log</strong>: See <code>storage/logs/laravel.log</code> for detailed error messages.</li>
            </ol>
            
            <h3>Common Solutions:</h3>
            <ul>
                <li><strong>Docker/Dev Container</strong>: Edit <code>docker/php.ini</code> and rebuild the container.</li>
                <li><strong>Shared Hosting</strong>: Edit <code>.htaccess</code> or use <code>ini_set()</code> in PHP code.</li>
                <li><strong>VPS/Dedicated</strong>: Edit <code>/etc/php/X.X/fpm/php.ini</code> and restart PHP-FPM.</li>
            </ul>
        </div>
    </div>
</x-filament-panels::page>
