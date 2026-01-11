<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateVideoRequest;
use App\Http\Requests\UploadVideoRequest;
use App\Jobs\ProcessVideoJob;
use App\Models\Category;
use App\Models\Video;
use App\Models\Reaction;
use App\Services\VideoService;
use Illuminate\Http\Request;

class StudioController extends Controller
{
    public function __construct(
        protected VideoService $videoService
    ) {}

    public function dashboard()
    {
        $user = auth()->user();

        $totalViews = (int) $user->videos()->sum('views_count');
        $subscriberCount = $user->subscribers()->count();
        $videoCount = $user->videos()->count();
        $totalLikes = Reaction::where('target_type', 'video')
            ->whereIn('target_id', $user->videos()->pluck('id'))
            ->where('reaction', 'like')
            ->count();

        $recentVideos = $user->videos()
            ->latest()
            ->take(5)
            ->get();

        // Get processing videos separately for status display
        $processingVideos = $user->videos()
            ->whereIn('status', ['pending', 'processing', 'uploaded'])
            ->latest()
            ->get();

        return view('studio.dashboard', compact(
            'totalViews',
            'subscriberCount',
            'videoCount',
            'totalLikes',
            'recentVideos',
            'processingVideos'
        ));
    }

    public function upload()
    {
        $user = auth()->user();
        $categories = Category::orderBy('name')->get();
        
        // Pass creator status to view
        $isCreator = $user->isCreator();
        $hasPendingRequest = $user->hasPendingCreatorRequest();
        $latestRequest = $user->latestCreatorRequest;
        
        return view('studio.upload', compact('categories', 'isCreator', 'hasPendingRequest', 'latestRequest'));
    }

    public function store(UploadVideoRequest $request)
    {
        // Check if user can upload
        $user = auth()->user();
        if (!$user->canUploadVideos()) {
            return back()->withErrors(['video' => 'You do not have permission to upload videos. Please request creator access first.']);
        }

        try {
            $video = $this->videoService->createVideo(
                $request->validated(),
                $request->file('video'),
                auth()->id()
            );

            // Handle thumbnail: user upload OR auto-generate
            if ($request->hasFile('thumbnail')) {
                $this->videoService->updateThumbnail($video, $request->file('thumbnail'));
            } else {
                // Auto-generate thumbnail from video frame (sync, fast)
                $this->videoService->generateThumbnailSync($video);
            }

            // Set status to published immediately (video is playable)
            // Processing happens in background
            $video->update([
                'status' => 'published',
                'processing_state' => 'processing',
            ]);

            // Queue background processing (non-blocking)
            ProcessVideoJob::dispatch($video)->onQueue('high');
            \App\Jobs\PrepareStreamMp4Job::dispatch($video)->onQueue('high');
            
            \Log::info('Video uploaded and queued for processing', [
                'video_id' => $video->id,
            ]);

            return redirect()
                ->route('studio.edit', $video)
                ->with('success', 'Video uploaded! Processing in background...');
                
        } catch (\Exception $e) {
            \Log::error('Video upload failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return back()
                ->withInput()
                ->withErrors(['video' => 'Upload failed: ' . $e->getMessage()]);
        }
    }

    public function videos()
    {
        $videos = auth()->user()->videos()
            ->with('category')
            ->latest()
            ->paginate(20);

        return view('studio.videos', compact('videos'));
    }

    public function edit(Video $video)
    {
        $this->authorize('update', $video);

        $categories = Category::orderBy('name')->get();
        $tags = $video->tags->pluck('name')->implode(', ');

        return view('studio.edit', compact('video', 'categories', 'tags'));
    }

    public function update(UpdateVideoRequest $request, Video $video)
    {
        $this->authorize('update', $video);

        $video = $this->videoService->updateVideo($video, $request->validated());

        if ($request->hasFile('thumbnail')) {
            $this->videoService->updateThumbnail($video, $request->file('thumbnail'));
        }

        return redirect()
            ->route('studio.edit', $video)
            ->with('success', 'Video updated successfully.');
    }

    public function destroy(Video $video)
    {
        $this->authorize('delete', $video);

        $this->videoService->deleteVideo($video);

        return redirect()
            ->route('studio.videos')
            ->with('success', 'Video deleted successfully.');
    }

    public function analytics()
    {
        $user = auth()->user();

        $totalViews = (int) $user->videos()->sum('views_count');
        $subscriberCount = $user->subscribers()->count();
        $videoCount = $user->videos()->count();
        $totalLikes = Reaction::where('target_type', 'video')
            ->whereIn('target_id', $user->videos()->pluck('id'))
            ->where('reaction', 'like')
            ->count();

        $videos = $user->videos()
            ->with('category')
            ->latest()
            ->take(10)
            ->get();

        return view('studio.analytics', compact(
            'totalViews',
            'subscriberCount',
            'videoCount',
            'totalLikes',
            'videos'
        ));
    }

    /**
     * Handle AJAX video upload with progress support
     */
    public function ajaxStore(UploadVideoRequest $request)
    {
        // Debug: Log upload request info
        $contentLength = $request->header('Content-Length');
        $hasVideoFile = $request->hasFile('video');
        $videoFile = $request->file('video');
        
        \Log::info('Video upload request received', [
            'user_id' => auth()->id(),
            'content_length' => $contentLength,
            'content_length_mb' => $contentLength ? round($contentLength / 1024 / 1024, 2) . 'MB' : 'N/A',
            'has_video_file' => $hasVideoFile,
            'video_size' => $videoFile ? $videoFile->getSize() : null,
            'video_size_mb' => $videoFile ? round($videoFile->getSize() / 1024 / 1024, 2) . 'MB' : 'N/A',
            'video_mime' => $videoFile ? $videoFile->getMimeType() : null,
            'php_upload_max_filesize' => ini_get('upload_max_filesize'),
            'php_post_max_size' => ini_get('post_max_size'),
        ]);

        try {
            $video = $this->videoService->createVideo(
                $request->validated(),
                $request->file('video'),
                auth()->id()
            );

            // Handle thumbnail: user upload OR auto-generate
            if ($request->hasFile('thumbnail')) {
                $this->videoService->updateThumbnail($video, $request->file('thumbnail'));
            } else {
                // Auto-generate thumbnail from video frame (sync, fast)
                $this->videoService->generateThumbnailSync($video);
            }

            // Set status to published immediately (video is playable)
            // Processing happens in background
            $video->update([
                'status' => 'published',
                'processing_state' => 'processing',
            ]);

            // Queue background processing (non-blocking)
            ProcessVideoJob::dispatch($video)->onQueue('high');
            \App\Jobs\PrepareStreamMp4Job::dispatch($video)->onQueue('high');

            return response()->json([
                'success' => true,
                'message' => 'Video uploaded! Processing in background...',
                'video' => [
                    'id' => $video->id,
                    'uuid' => $video->uuid,
                    'title' => $video->title,
                    'status' => $video->fresh()->status,
                    'stream_ready' => $video->fresh()->stream_ready,
                ],
                'redirect' => route('studio.edit', $video),
            ]);
                
        } catch (\Exception $e) {
            \Log::error('Video AJAX upload failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get upload status for a video
     */
    public function uploadStatus(Video $video)
    {
        $this->authorize('view', $video);

        return response()->json([
            'id' => $video->id,
            'uuid' => $video->uuid,
            'title' => $video->title,
            'status' => $video->status,
            'thumbnail' => $video->thumbnail_url ?? null,
            'duration' => $video->duration,
            'created_at' => $video->created_at->diffForHumans(),
        ]);
    }

    /**
     * Get processing videos status for polling (JSON endpoint)
     */
    public function processingStatus()
    {
        $user = auth()->user();
        
        $processingVideos = $user->videos()
            ->whereIn('status', ['pending', 'processing', 'uploaded'])
            ->latest()
            ->get()
            ->map(function ($video) {
                return [
                    'id' => $video->id,
                    'uuid' => $video->uuid,
                    'title' => $video->title,
                    'status' => $video->status,
                    'thumbnail_url' => $video->thumbnail_url,
                    'created_at' => $video->created_at->diffForHumans(),
                    'edit_url' => route('studio.edit', $video),
                    'processing_error' => $video->processing_error,
                ];
            });

        return response()->json([
            'success' => true,
            'videos' => $processingVideos,
            'count' => $processingVideos->count(),
        ]);
    }

    /**
     * Get detailed video status for edit page polling
     */
    public function videoStatus(Video $video)
    {
        $this->authorize('update', $video);

        return response()->json([
            'success' => true,
            'id' => $video->id,
            'uuid' => $video->uuid,
            'status' => $video->status,
            'processing_state' => $video->processing_state ?? 'ready',
            'hls_ready' => !empty($video->hls_master_path),
            'thumbnail_ready' => !empty($video->thumbnail_path),
            'thumbnail_url' => $video->thumbnail_url,
            'hls_url' => $video->hls_url,
            'duration_seconds' => $video->duration_seconds,
            'duration_formatted' => $video->duration_formatted,
            'processing_error' => $video->processing_error,
            'processed_at' => $video->processed_at?->toIso8601String(),
            'published_at' => $video->published_at?->toIso8601String(),
        ]);
    }

    /**
     * Retry processing for a failed video
     */
    public function retryProcessing(Video $video)
    {
        $this->authorize('update', $video);

        // Only allow retry for failed or stuck videos
        if (!in_array($video->status, ['failed', 'processing', 'published'])) {
            return response()->json([
                'success' => false,
                'message' => 'Video cannot be reprocessed.',
            ], 422);
        }

        // Check if original file exists
        if (!$video->original_path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($video->original_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Original video file not found. Please re-upload the video.',
            ], 422);
        }

        // Update status and queue processing
        $video->update([
            'status' => 'published',
            'processing_state' => 'processing',
            'processing_error' => null,
        ]);

        // Queue background processing (non-blocking)
        ProcessVideoJob::dispatch($video)->onQueue('high');
        \App\Jobs\PrepareStreamMp4Job::dispatch($video)->onQueue('high');
        
        \Log::info('Video reprocessing queued', [
            'video_id' => $video->id,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Video reprocessing started in background!',
        ]);
    }
}
