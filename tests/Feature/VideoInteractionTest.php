<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Reaction;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoInteractionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Video $video;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->video = Video::factory()->create([
            'status' => Video::STATUS_PUBLISHED,
            'visibility' => 'public',
        ]);
    }

    /** @test */
    public function user_can_like_a_video(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('video.react', $this->video), [
                'reaction' => 'like',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'reaction' => 'like',
            ]);

        $this->assertDatabaseHas('reactions', [
            'user_id' => $this->user->id,
            'target_type' => 'video',
            'target_id' => $this->video->id,
            'reaction' => 'like',
        ]);
    }

    /** @test */
    public function user_can_dislike_a_video(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('video.react', $this->video), [
                'reaction' => 'dislike',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'reaction' => 'dislike',
            ]);

        $this->assertDatabaseHas('reactions', [
            'user_id' => $this->user->id,
            'target_type' => 'video',
            'target_id' => $this->video->id,
            'reaction' => 'dislike',
        ]);
    }

    /** @test */
    public function user_can_toggle_reaction(): void
    {
        // First like
        Reaction::create([
            'user_id' => $this->user->id,
            'target_type' => 'video',
            'target_id' => $this->video->id,
            'reaction' => 'like',
        ]);

        // Toggle to dislike
        $response = $this->actingAs($this->user)
            ->postJson(route('video.react', $this->video), [
                'reaction' => 'dislike',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'reaction' => 'dislike',
            ]);

        $this->assertDatabaseHas('reactions', [
            'user_id' => $this->user->id,
            'target_type' => 'video',
            'target_id' => $this->video->id,
            'reaction' => 'dislike',
        ]);
    }

    /** @test */
    public function user_can_remove_reaction_by_clicking_same_button(): void
    {
        Reaction::create([
            'user_id' => $this->user->id,
            'target_type' => 'video',
            'target_id' => $this->video->id,
            'reaction' => 'like',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('video.react', $this->video), [
                'reaction' => 'like',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'action' => 'removed',
                'reaction' => null,
            ]);

        $this->assertDatabaseMissing('reactions', [
            'user_id' => $this->user->id,
            'target_type' => 'video',
            'target_id' => $this->video->id,
        ]);
    }

    /** @test */
    public function user_can_add_video_to_watch_later(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('video.watch-later', $this->video));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'added' => true,
            ]);

        $this->assertTrue(
            $this->user->watchLater()->where('videos.id', $this->video->id)->exists()
        );
    }

    /** @test */
    public function user_can_remove_video_from_watch_later(): void
    {
        $this->user->watchLater()->attach($this->video->id);

        $response = $this->actingAs($this->user)
            ->postJson(route('video.watch-later', $this->video));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'added' => false,
            ]);

        $this->assertFalse(
            $this->user->watchLater()->where('videos.id', $this->video->id)->exists()
        );
    }

    /** @test */
    public function user_can_post_comment(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('video.comment', $this->video), [
                'body' => 'This is a test comment',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'comment' => [
                    'id',
                    'body',
                    'user',
                ],
            ]);

        $this->assertDatabaseHas('comments', [
            'video_id' => $this->video->id,
            'user_id' => $this->user->id,
            'body' => 'This is a test comment',
        ]);
    }

    /** @test */
    public function guest_cannot_react_to_video(): void
    {
        $response = $this->postJson(route('video.react', $this->video), [
            'reaction' => 'like',
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function guest_cannot_post_comment(): void
    {
        $response = $this->postJson(route('video.comment', $this->video), [
            'body' => 'This should fail',
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function guest_cannot_toggle_watch_later(): void
    {
        $response = $this->postJson(route('video.watch-later', $this->video));

        $response->assertUnauthorized();
    }

    /** @test */
    public function comment_requires_body(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('video.comment', $this->video), [
                'body' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    }

    /** @test */
    public function reaction_requires_valid_type(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('video.react', $this->video), [
                'reaction' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reaction']);
    }
}
