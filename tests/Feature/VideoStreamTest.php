<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VideoStreamTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Video $video;
    protected string $testFilePath;
    protected int $fileSize;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Use fake storage
        Storage::fake('public');
        
        // Create a test user
        $this->user = User::factory()->create();
        
        // Create a dummy video file (5KB of random data simulating MP4)
        $this->fileSize = 5120; // 5KB
        $testContent = random_bytes($this->fileSize);
        
        // Store the test file
        $this->testFilePath = 'videos/test-uuid/test.mp4';
        Storage::disk('public')->put($this->testFilePath, $testContent);
        
        // Create video record
        $this->video = Video::factory()->create([
            'uuid' => 'test-uuid',
            'user_id' => $this->user->id,
            'original_path' => $this->testFilePath,
            'visibility' => 'public',
            'status' => 'published',
        ]);
    }

    /**
     * Test full file request without Range header.
     */
    public function test_full_file_request_returns_200(): void
    {
        $response = $this->get("/stream/{$this->video->uuid}");
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'video/mp4');
        $response->assertHeader('Accept-Ranges', 'bytes');
        $response->assertHeader('Content-Length', (string) $this->fileSize);
    }

    /**
     * Test standard range: bytes=0-1023
     */
    public function test_standard_range_returns_206(): void
    {
        $response = $this->get("/stream/{$this->video->uuid}", [
            'Range' => 'bytes=0-1023',
        ]);
        
        $response->assertStatus(206);
        $response->assertHeader('Content-Type', 'video/mp4');
        $response->assertHeader('Accept-Ranges', 'bytes');
        $response->assertHeader('Content-Length', '1024');
        $response->assertHeader('Content-Range', "bytes 0-1023/{$this->fileSize}");
    }

    /**
     * Test open-ended range: bytes=1024-
     */
    public function test_open_ended_range_returns_206(): void
    {
        $expectedLength = $this->fileSize - 1024;
        $expectedEnd = $this->fileSize - 1;
        
        $response = $this->get("/stream/{$this->video->uuid}", [
            'Range' => 'bytes=1024-',
        ]);
        
        $response->assertStatus(206);
        $response->assertHeader('Content-Length', (string) $expectedLength);
        $response->assertHeader('Content-Range', "bytes 1024-{$expectedEnd}/{$this->fileSize}");
    }

    /**
     * Test suffix range: bytes=-1024 (CRITICAL for MP4 moov atom)
     */
    public function test_suffix_range_returns_206(): void
    {
        $suffixLength = 1024;
        $expectedStart = $this->fileSize - $suffixLength;
        $expectedEnd = $this->fileSize - 1;
        
        $response = $this->get("/stream/{$this->video->uuid}", [
            'Range' => 'bytes=-1024',
        ]);
        
        $response->assertStatus(206);
        $response->assertHeader('Content-Length', '1024');
        $response->assertHeader('Content-Range', "bytes {$expectedStart}-{$expectedEnd}/{$this->fileSize}");
    }

    /**
     * Test suffix range larger than file returns entire file.
     */
    public function test_suffix_range_larger_than_file_returns_full_content(): void
    {
        $response = $this->get("/stream/{$this->video->uuid}", [
            'Range' => 'bytes=-999999',
        ]);
        
        $response->assertStatus(206);
        $response->assertHeader('Content-Length', (string) $this->fileSize);
        $expectedEnd = $this->fileSize - 1;
        $response->assertHeader('Content-Range', "bytes 0-{$expectedEnd}/{$this->fileSize}");
    }

    /**
     * Test invalid range (start > file size) returns 416.
     */
    public function test_invalid_range_start_beyond_file_returns_416(): void
    {
        $response = $this->get("/stream/{$this->video->uuid}", [
            'Range' => 'bytes=999999-',
        ]);
        
        $response->assertStatus(416);
        $response->assertHeader('Content-Range', "bytes */{$this->fileSize}");
    }

    /**
     * Test invalid range (start > end) returns 416.
     */
    public function test_invalid_range_start_greater_than_end_returns_416(): void
    {
        $response = $this->get("/stream/{$this->video->uuid}", [
            'Range' => 'bytes=1000-500',
        ]);
        
        $response->assertStatus(416);
        $response->assertHeader('Content-Range', "bytes */{$this->fileSize}");
    }

    /**
     * Test HEAD request returns headers without body.
     */
    public function test_head_request_returns_headers_only(): void
    {
        $response = $this->head("/stream/{$this->video->uuid}");
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'video/mp4');
        $response->assertHeader('Accept-Ranges', 'bytes');
        $response->assertHeader('Content-Length', (string) $this->fileSize);
        
        // Body should be empty for HEAD request
        $this->assertEmpty($response->getContent());
    }

    /**
     * Test HEAD request with Range header.
     */
    public function test_head_request_with_range_returns_206(): void
    {
        $response = $this->head("/stream/{$this->video->uuid}", [
            'Range' => 'bytes=0-1023',
        ]);
        
        $response->assertStatus(206);
        $response->assertHeader('Content-Range', "bytes 0-1023/{$this->fileSize}");
        $response->assertHeader('Content-Length', '1024');
        
        // Body should be empty for HEAD request
        $this->assertEmpty($response->getContent());
    }

    /**
     * Test response includes no-cache headers.
     */
    public function test_response_includes_no_cache_headers(): void
    {
        $response = $this->get("/stream/{$this->video->uuid}");
        
        $response->assertStatus(200);
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    /**
     * Test response includes Content-Encoding identity.
     */
    public function test_response_includes_identity_encoding(): void
    {
        $response = $this->get("/stream/{$this->video->uuid}");
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Encoding', 'identity');
    }

    /**
     * Test private video returns 404 for non-owner.
     */
    public function test_private_video_returns_404_for_non_owner(): void
    {
        $this->video->update(['visibility' => 'private']);
        
        $response = $this->get("/stream/{$this->video->uuid}");
        
        $response->assertStatus(404);
    }

    /**
     * Test private video accessible by owner.
     */
    public function test_private_video_accessible_by_owner(): void
    {
        $this->video->update(['visibility' => 'private']);
        
        $response = $this->actingAs($this->user)
            ->get("/stream/{$this->video->uuid}");
        
        $response->assertStatus(200);
    }

    /**
     * Test quality parameter selects correct rendition.
     */
    public function test_quality_parameter_selects_rendition(): void
    {
        // Create a rendition file
        $renditionPath = 'videos/test-uuid/renditions/360p.mp4';
        $renditionContent = random_bytes(2048);
        Storage::disk('public')->put($renditionPath, $renditionContent);
        
        // Update video with renditions
        $this->video->update([
            'renditions' => [
                '360' => ['path' => $renditionPath, 'width' => 640, 'height' => 360],
            ],
        ]);
        
        $response = $this->get("/stream/{$this->video->uuid}?quality=360");
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Length', '2048');
    }

    /**
     * Test non-existent video returns 404.
     */
    public function test_non_existent_video_returns_404(): void
    {
        $response = $this->get("/stream/non-existent-uuid");
        
        $response->assertStatus(404);
    }

    /**
     * Test multiple ranges in header only processes first range.
     */
    public function test_multiple_ranges_processes_first_only(): void
    {
        $response = $this->get("/stream/{$this->video->uuid}", [
            'Range' => 'bytes=0-100, 200-300',
        ]);
        
        // Should process first range only
        $response->assertStatus(206);
        $response->assertHeader('Content-Length', '101');
        $response->assertHeader('Content-Range', "bytes 0-100/{$this->fileSize}");
    }

    /**
     * Test range that extends beyond file size is capped.
     */
    public function test_range_end_beyond_file_is_capped(): void
    {
        $response = $this->get("/stream/{$this->video->uuid}", [
            'Range' => 'bytes=0-999999',
        ]);
        
        $expectedEnd = $this->fileSize - 1;
        
        $response->assertStatus(206);
        $response->assertHeader('Content-Length', (string) $this->fileSize);
        $response->assertHeader('Content-Range', "bytes 0-{$expectedEnd}/{$this->fileSize}");
    }
}
