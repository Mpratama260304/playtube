<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessVideoJob;
use Tests\TestCase;

class StudioUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_authenticated_user_can_access_upload_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/studio/upload');

        $response->assertStatus(200);
        $response->assertViewIs('studio.upload');
    }

    public function test_guest_cannot_access_upload_page(): void
    {
        $response = $this->get('/studio/upload');

        $response->assertRedirect('/login');
    }

    public function test_user_can_upload_video(): void
    {
        Queue::fake();
        
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $videoFile = UploadedFile::fake()->create('test-video.mp4', 1024, 'video/mp4');

        $response = $this->actingAs($user)->post('/studio/upload', [
            'title' => 'My Test Video',
            'description' => 'A test video description',
            'video' => $videoFile,
            'category_id' => $category->id,
            'visibility' => 'public',
            'tags' => 'test, demo, sample',
        ]);

        // Should redirect after successful upload
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Video should be in database
        $this->assertDatabaseHas('videos', [
            'user_id' => $user->id,
            'title' => 'My Test Video',
            'status' => 'processing',
            'visibility' => 'public',
        ]);

        // Video file should be stored
        $video = Video::where('title', 'My Test Video')->first();
        Storage::disk('public')->assertExists($video->original_path);

        // Processing job should be dispatched
        Queue::assertPushed(ProcessVideoJob::class, function ($job) use ($video) {
            return $job->video->id === $video->id;
        });
    }

    public function test_upload_requires_title(): void
    {
        $user = User::factory()->create();
        $videoFile = UploadedFile::fake()->create('test.mp4', 1024, 'video/mp4');

        $response = $this->actingAs($user)->post('/studio/upload', [
            'video' => $videoFile,
            'visibility' => 'public',
        ]);

        $response->assertSessionHasErrors('title');
    }

    public function test_upload_requires_video_file(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/studio/upload', [
            'title' => 'My Video',
            'visibility' => 'public',
        ]);

        $response->assertSessionHasErrors('video');
    }

    public function test_upload_rejects_non_video_files(): void
    {
        $user = User::factory()->create();
        $textFile = UploadedFile::fake()->create('document.txt', 100, 'text/plain');

        $response = $this->actingAs($user)->post('/studio/upload', [
            'title' => 'My Video',
            'video' => $textFile,
            'visibility' => 'public',
        ]);

        $response->assertSessionHasErrors('video');
    }

    public function test_upload_requires_visibility(): void
    {
        $user = User::factory()->create();
        $videoFile = UploadedFile::fake()->create('test.mp4', 1024, 'video/mp4');

        $response = $this->actingAs($user)->post('/studio/upload', [
            'title' => 'My Video',
            'video' => $videoFile,
        ]);

        $response->assertSessionHasErrors('visibility');
    }

    public function test_banned_user_cannot_upload(): void
    {
        $user = User::factory()->create(['is_banned' => true]);
        $videoFile = UploadedFile::fake()->create('test.mp4', 1024, 'video/mp4');

        $response = $this->actingAs($user)->post('/studio/upload', [
            'title' => 'My Video',
            'video' => $videoFile,
            'visibility' => 'public',
        ]);

        $response->assertStatus(403);
    }
}
