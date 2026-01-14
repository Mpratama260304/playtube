<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Video;
use App\Services\EmbedService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmbedController extends Controller
{
    public function __construct(
        protected EmbedService $embedService
    ) {}

    /**
     * Show the embed video form.
     */
    public function create()
    {
        $user = auth()->user();
        $categories = Category::orderBy('name')->get();
        
        // Pass creator status to view
        $isCreator = $user->isCreator();
        $hasPendingRequest = $user->hasPendingCreatorRequest();
        $latestRequest = $user->latestCreatorRequest;
        $supportedPlatforms = $this->embedService->getSupportedPlatforms();
        
        return view('studio.embed', compact(
            'categories',
            'isCreator',
            'hasPendingRequest',
            'latestRequest',
            'supportedPlatforms'
        ));
    }

    /**
     * Parse an embed URL and return video info (AJAX).
     */
    public function parse(Request $request)
    {
        $request->validate([
            'url' => 'required|string|url|max:2048',
        ]);

        $result = $this->embedService->parseUrl($request->url);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Unsupported URL. Please provide a valid video URL from YouTube, Dailymotion, Google Drive, Vimeo, or other supported platforms.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'platform' => $result['platform'],
                'platform_name' => $this->embedService->getPlatformName($result['platform']),
                'video_id' => $result['video_id'],
                'embed_url' => $result['embed_url'],
                'thumbnail_url' => $result['thumbnail_url'],
            ],
        ]);
    }

    /**
     * Store an embedded video.
     */
    public function store(Request $request)
    {
        // Check if user can upload
        $user = auth()->user();
        if (!$user->canUploadVideos()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to add videos. Please request creator access first.',
            ], 403);
        }

        $request->validate([
            'url' => 'required|string|url|max:2048',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'category_id' => 'nullable|exists:categories,id',
            'tags' => 'nullable|string|max:500',
            'visibility' => 'required|in:public,unlisted,private',
            'is_short' => 'nullable|boolean',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:51200', // 50MB max
        ]);

        // Parse the embed URL
        $embedData = $this->embedService->parseUrl($request->url);

        if (!$embedData) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or unsupported video URL.',
            ], 422);
        }

        try {
            $uuid = (string) Str::uuid();
            
            // Handle thumbnail: user upload takes priority over auto-download
            $thumbnailPath = null;
            if ($request->hasFile('thumbnail')) {
                // User uploaded custom thumbnail
                $thumbnailPath = $request->file('thumbnail')->store('thumbnails', 'public');
            } elseif ($embedData['thumbnail_url']) {
                // Try to download thumbnail from embed platform
                $thumbnailPath = $this->embedService->downloadThumbnail($embedData['thumbnail_url'], $uuid);
            }

            // Create the video record
            $video = Video::create([
                'uuid' => $uuid,
                'user_id' => auth()->id(),
                'title' => $request->title,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'visibility' => $request->visibility,
                'is_short' => $request->boolean('is_short'),
                'status' => 'published', // Embeds are published immediately
                'source_type' => 'embed',
                'embed_url' => $embedData['original_url'],
                'embed_platform' => $embedData['platform'],
                'embed_video_id' => $embedData['video_id'],
                'embed_iframe_url' => $embedData['embed_url'],
                'thumbnail_path' => $thumbnailPath ?? null,
                'published_at' => now(),
                'processing_state' => 'ready', // No processing needed
            ]);

            // Sync tags if provided
            if ($request->tags) {
                $tags = array_map('trim', explode(',', $request->tags));
                $video->syncTags($tags);
            }

            \Log::info('Embed video created', [
                'video_id' => $video->id,
                'platform' => $embedData['platform'],
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Video added successfully!',
                'video' => [
                    'id' => $video->id,
                    'slug' => $video->slug,
                    'url' => route('video.watch', $video),
                    'edit_url' => route('studio.edit', $video),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to create embed video', [
                'user_id' => auth()->id(),
                'url' => $request->url,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add video: ' . $e->getMessage(),
            ], 500);
        }
    }
}
