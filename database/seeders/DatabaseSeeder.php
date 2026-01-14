<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User - REQUIRED
        // Default login: email: mpratamagpt@gmail.com, password: Anonymous263
        $adminPassword = 'Anonymous263';
        User::updateOrCreate(
            ['email' => 'mpratamagpt@gmail.com'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'password' => Hash::make($adminPassword),
                'email_verified_at' => now(),
                'role' => 'admin',
                'bio' => 'Platform Administrator',
                'is_active' => true,
                'is_banned' => false, // Explicitly set to allow admin panel access
                'is_creator' => true, // Admin can upload videos
            ]
        );

        // Create Default Categories
        $categories = [
            [
                'name' => 'Music',
                'slug' => 'music',
                'description' => 'Music videos, songs, and musical performances',
                'icon' => 'musical-note',
            ],
            [
                'name' => 'Gaming',
                'slug' => 'gaming',
                'description' => 'Gaming videos, walkthroughs, and gameplay',
                'icon' => 'puzzle-piece',
            ],
            [
                'name' => 'Education',
                'slug' => 'education',
                'description' => 'Educational content and tutorials',
                'icon' => 'academic-cap',
            ],
            [
                'name' => 'Entertainment',
                'slug' => 'entertainment',
                'description' => 'Entertainment, comedy, and fun videos',
                'icon' => 'face-smile',
            ],
            [
                'name' => 'Sports',
                'slug' => 'sports',
                'description' => 'Sports highlights and athletic content',
                'icon' => 'trophy',
            ],
            [
                'name' => 'News',
                'slug' => 'news',
                'description' => 'News and current events',
                'icon' => 'newspaper',
            ],
            [
                'name' => 'Science & Technology',
                'slug' => 'science-technology',
                'description' => 'Science and technology content',
                'icon' => 'beaker',
            ],
            [
                'name' => 'Travel',
                'slug' => 'travel',
                'description' => 'Travel vlogs and destination guides',
                'icon' => 'globe-alt',
            ],
            [
                'name' => 'Food',
                'slug' => 'food',
                'description' => 'Cooking tutorials and food reviews',
                'icon' => 'cake',
            ],
            [
                'name' => 'Lifestyle',
                'slug' => 'lifestyle',
                'description' => 'Lifestyle, fashion, and daily vlogs',
                'icon' => 'heart',
            ],
            [
                'name' => 'Pets & Animals',
                'slug' => 'pets-animals',
                'description' => 'Cute pets and animal videos',
                'icon' => 'heart',
            ],
            [
                'name' => 'How-to & DIY',
                'slug' => 'how-to-diy',
                'description' => 'How-to guides and DIY projects',
                'icon' => 'wrench-screwdriver',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        // Create Default Settings
        $settings = [
            ['key' => 'site_name', 'value' => 'PlayTube'],
            ['key' => 'site_description', 'value' => 'Share and discover amazing videos'],
            ['key' => 'max_upload_size', 'value' => '500'],
            ['key' => 'allow_registration', 'value' => 'true'],
            ['key' => 'require_email_verification', 'value' => 'false'],
            ['key' => 'videos_per_page', 'value' => '24'],
            ['key' => 'comments_per_page', 'value' => '20'],
            ['key' => 'default_video_visibility', 'value' => 'public'],
            ['key' => 'allow_comments', 'value' => 'true'],
            ['key' => 'moderate_comments', 'value' => 'false'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        // Create sample user for testing
        User::updateOrCreate(
            ['email' => 'john@example.com'],
            [
                'name' => 'John Doe',
                'username' => 'johndoe',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'user',
                'bio' => 'Content creator and video enthusiast',
                'is_active' => true,
            ]
        );
    }
}
