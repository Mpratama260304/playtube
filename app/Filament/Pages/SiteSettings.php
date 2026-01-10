<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SiteSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationGroup = 'Settings';
    
    protected static ?int $navigationSort = 1;
    
    protected static string $view = 'filament.pages.site-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'site_name' => Setting::get('site_name', 'PlayTube'),
            'site_description' => Setting::get('site_description', 'A modern video sharing platform'),
            'max_upload_size' => Setting::get('max_upload_size', 2048),
            'allow_registration' => Setting::get('allow_registration', true),
            'require_email_verification' => Setting::get('require_email_verification', false),
            // HLS Settings
            'enable_hls_processing' => Setting::get('enable_hls_processing', true),
            'auto_generate_hls' => Setting::get('auto_generate_hls', true),
            'hls_default_enabled' => Setting::get('hls_default_enabled', true),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Settings')
                    ->schema([
                        Forms\Components\TextInput::make('site_name')
                            ->label('Site Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('site_description')
                            ->label('Site Description')
                            ->maxLength(500),
                    ]),

                Forms\Components\Section::make('Upload Settings')
                    ->schema([
                        Forms\Components\TextInput::make('max_upload_size')
                            ->label('Max Upload Size (MB)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(10240)
                            ->suffix('MB')
                            ->helperText('Controls Laravel validation limit. Server limits (PHP upload_max_filesize, post_max_size, Nginx client_max_body_size) must also be configured to support this size. Check System Health page for current server limits.'),
                    ]),

                Forms\Components\Section::make('Registration Settings')
                    ->schema([
                        Forms\Components\Toggle::make('allow_registration')
                            ->label('Allow User Registration'),
                        Forms\Components\Toggle::make('require_email_verification')
                            ->label('Require Email Verification'),
                    ]),

                Forms\Components\Section::make('HLS (Adaptive Streaming) Settings')
                    ->description('Configure HLS transcoding for adaptive bitrate streaming. HLS provides better playback experience by adapting video quality to viewer bandwidth.')
                    ->schema([
                        Forms\Components\Toggle::make('enable_hls_processing')
                            ->label('Enable HLS Processing')
                            ->helperText('Master switch to enable/disable HLS transcoding system-wide. When disabled, videos will only be served as MP4.'),
                        Forms\Components\Toggle::make('auto_generate_hls')
                            ->label('Auto-generate HLS for New Uploads')
                            ->helperText('When enabled, HLS transcoding will automatically start after video upload is complete.'),
                        Forms\Components\Toggle::make('hls_default_enabled')
                            ->label('Enable HLS by Default for New Videos')
                            ->helperText('Sets the default value of the "HLS Enabled" flag when creating new videos.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }
}
