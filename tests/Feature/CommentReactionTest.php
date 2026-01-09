<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Reaction;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentReactionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Video $video;
    protected Comment $comment;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->video = Video::factory()->create([
            'status' => Video::STATUS_PUBLISHED,
        ]);
        $this->comment = Comment::create([
            'video_id' => $this->video->id,
            'user_id' => $this->user->id,
            'body' => 'Test comment',
        ]);
    }

    /** @test */
    public function user_can_like_a_comment_via_api(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/comments/{$this->comment->id}/react", [
                'reaction' => 'like',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'reaction' => 'like',
            ]);

        $this->assertDatabaseHas('reactions', [
            'user_id' => $this->user->id,
            'target_type' => 'comment',
            'target_id' => $this->comment->id,
            'reaction' => 'like',
        ]);
    }

    /** @test */
    public function user_can_dislike_a_comment_via_api(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/comments/{$this->comment->id}/react", [
                'reaction' => 'dislike',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'reaction' => 'dislike',
            ]);

        $this->assertDatabaseHas('reactions', [
            'user_id' => $this->user->id,
            'target_type' => 'comment',
            'target_id' => $this->comment->id,
            'reaction' => 'dislike',
        ]);
    }

    /** @test */
    public function user_can_toggle_comment_reaction(): void
    {
        // First like
        Reaction::create([
            'user_id' => $this->user->id,
            'target_type' => 'comment',
            'target_id' => $this->comment->id,
            'reaction' => 'like',
        ]);

        // Toggle to dislike
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/comments/{$this->comment->id}/react", [
                'reaction' => 'dislike',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'reaction' => 'dislike',
            ]);

        $this->assertDatabaseHas('reactions', [
            'user_id' => $this->user->id,
            'target_type' => 'comment',
            'target_id' => $this->comment->id,
            'reaction' => 'dislike',
        ]);
    }

    /** @test */
    public function user_can_remove_comment_reaction(): void
    {
        Reaction::create([
            'user_id' => $this->user->id,
            'target_type' => 'comment',
            'target_id' => $this->comment->id,
            'reaction' => 'like',
        ]);

        // Click like again to remove
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/comments/{$this->comment->id}/react", [
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
            'target_type' => 'comment',
            'target_id' => $this->comment->id,
        ]);
    }

    /** @test */
    public function guest_cannot_react_to_comment(): void
    {
        $response = $this->postJson("/api/v1/comments/{$this->comment->id}/react", [
            'reaction' => 'like',
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function reaction_counts_are_updated(): void
    {
        $otherUser = User::factory()->create();

        // First user likes
        $this->actingAs($this->user)
            ->postJson("/api/v1/comments/{$this->comment->id}/react", [
                'reaction' => 'like',
            ]);

        // Second user likes
        $response = $this->actingAs($otherUser)
            ->postJson("/api/v1/comments/{$this->comment->id}/react", [
                'reaction' => 'like',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'likes_count' => 2,
                'dislikes_count' => 0,
            ]);
    }
}
