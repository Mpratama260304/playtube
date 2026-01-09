<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Video>
 */
class VideoFactory extends Factory
{
    protected $model = Video::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(6);
        
        return [
            'uuid' => Str::uuid(),
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(8),
            'description' => fake()->paragraphs(3, true),
            'original_path' => 'videos/' . Str::uuid() . '/original.mp4',
            'thumbnail_path' => 'videos/' . Str::uuid() . '/thumb.jpg',
            'duration_seconds' => fake()->numberBetween(30, 3600),
            'views_count' => fake()->numberBetween(0, 10000),
            'likes_count' => fake()->numberBetween(0, 1000),
            'dislikes_count' => fake()->numberBetween(0, 100),
            'comments_count' => fake()->numberBetween(0, 500),
            'status' => Video::STATUS_PUBLISHED,
            'visibility' => 'public',
            'is_short' => false,
            'published_at' => now(),
        ];
    }

    /**
     * Indicate that the video is pending processing.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Video::STATUS_PROCESSING,
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the video is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Video::STATUS_PROCESSING,
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the video is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Video::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the video is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'private',
        ]);
    }

    /**
     * Indicate that the video is unlisted.
     */
    public function unlisted(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'unlisted',
        ]);
    }

    /**
     * Indicate that the video is a short.
     */
    public function short(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_short' => true,
            'duration_seconds' => fake()->numberBetween(15, 60),
        ]);
    }
}
