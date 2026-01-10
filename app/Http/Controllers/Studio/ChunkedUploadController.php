<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessVideoJob;
use App\Models\Setting;
use App\Models\Video;
use App\Services\VideoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChunkedUploadController extends Controller
{
    public function __construct(
        protected VideoService $videoService
    ) {}

    /**
     * Initialize a chunked upload session
     */
    public function init(Request $request)
    {
        $request->validate([
            'filename' => 'required|string|max:255',
            'filesize' => 'required|integer|min:1',
            'mimetype' => 'required|string',
            'title' => 'required|string|max:255',
        ]);

        // Check file size against site setting
        $maxSizeMb = (int) Setting::get('max_upload_size', 2048);
        $maxSizeBytes = $maxSizeMb * 1024 * 1024;
        
        if ($request->filesize > $maxSizeBytes) {
            return response()->json([
                'success' => false,
                'message' => "File too large. Maximum allowed size is {$maxSizeMb}MB.",
            ], 422);
        }

        // Validate mimetype
        $allowedTypes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm', 'video/x-matroska', 'video/mpeg'];
        if (!in_array($request->mimetype, $allowedTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file type. Allowed types: MP4, MOV, AVI, WebM, MKV, MPEG.',
            ], 422);
        }

        // Generate upload session ID
        $uploadId = Str::uuid()->toString();
        $userId = auth()->id();
        
        // Create temp directory for chunks
        $chunkDir = "chunks/{$userId}/{$uploadId}";
        Storage::disk('local')->makeDirectory($chunkDir);

        // Store upload metadata in cache
        $metadata = [
            'upload_id' => $uploadId,
            'user_id' => $userId,
            'filename' => $request->filename,
            'filesize' => $request->filesize,
            'mimetype' => $request->mimetype,
            'title' => $request->title,
            'description' => $request->input('description', ''),
            'category_id' => $request->input('category_id'),
            'tags' => $request->input('tags', ''),
            'visibility' => $request->input('visibility', 'public'),
            'is_short' => $request->boolean('is_short'),
            'chunks_received' => [],
            'total_chunks' => null,
            'created_at' => now()->timestamp,
        ];

        cache()->put("chunked_upload:{$uploadId}", $metadata, now()->addHours(24));

        \Log::info('Chunked upload initialized', [
            'upload_id' => $uploadId,
            'user_id' => $userId,
            'filename' => $request->filename,
            'filesize' => $request->filesize,
        ]);

        return response()->json([
            'success' => true,
            'upload_id' => $uploadId,
            'chunk_size' => 5 * 1024 * 1024, // 5MB chunks
            'message' => 'Upload session initialized',
        ]);
    }

    /**
     * Receive a chunk
     */
    public function chunk(Request $request)
    {
        $request->validate([
            'upload_id' => 'required|uuid',
            'chunk_index' => 'required|integer|min:0',
            'total_chunks' => 'required|integer|min:1',
            'chunk' => 'required|file',
        ]);

        $uploadId = $request->upload_id;
        $chunkIndex = $request->chunk_index;
        $totalChunks = $request->total_chunks;
        
        // Get upload metadata
        $metadata = cache()->get("chunked_upload:{$uploadId}");
        
        if (!$metadata) {
            return response()->json([
                'success' => false,
                'message' => 'Upload session not found or expired.',
            ], 404);
        }

        // Verify user owns this upload
        if ($metadata['user_id'] !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        // Store chunk
        $chunkFile = $request->file('chunk');
        $chunkDir = "chunks/{$metadata['user_id']}/{$uploadId}";
        $chunkPath = "{$chunkDir}/chunk_{$chunkIndex}";
        
        Storage::disk('local')->put($chunkPath, file_get_contents($chunkFile->getRealPath()));

        // Update metadata
        $metadata['chunks_received'][$chunkIndex] = true;
        $metadata['total_chunks'] = $totalChunks;
        cache()->put("chunked_upload:{$uploadId}", $metadata, now()->addHours(24));

        $receivedCount = count($metadata['chunks_received']);
        
        \Log::debug('Chunk received', [
            'upload_id' => $uploadId,
            'chunk_index' => $chunkIndex,
            'total_chunks' => $totalChunks,
            'received_count' => $receivedCount,
        ]);

        return response()->json([
            'success' => true,
            'chunk_index' => $chunkIndex,
            'chunks_received' => $receivedCount,
            'total_chunks' => $totalChunks,
            'complete' => $receivedCount >= $totalChunks,
        ]);
    }

    /**
     * Complete the chunked upload and assemble file
     */
    public function complete(Request $request)
    {
        $request->validate([
            'upload_id' => 'required|uuid',
        ]);

        $uploadId = $request->upload_id;
        
        // Get upload metadata
        $metadata = cache()->get("chunked_upload:{$uploadId}");
        
        if (!$metadata) {
            return response()->json([
                'success' => false,
                'message' => 'Upload session not found or expired.',
            ], 404);
        }

        // Verify user owns this upload
        if ($metadata['user_id'] !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        // Verify all chunks received
        $receivedCount = count($metadata['chunks_received']);
        $totalChunks = $metadata['total_chunks'];
        
        if ($receivedCount < $totalChunks) {
            return response()->json([
                'success' => false,
                'message' => "Not all chunks received. Got {$receivedCount} of {$totalChunks}.",
            ], 422);
        }

        try {
            // Assemble file
            $chunkDir = "chunks/{$metadata['user_id']}/{$uploadId}";
            $uuid = Str::uuid()->toString();
            $extension = pathinfo($metadata['filename'], PATHINFO_EXTENSION) ?: 'mp4';
            $finalPath = "videos/{$uuid}/original.{$extension}";
            
            // Create video directory
            Storage::disk('public')->makeDirectory("videos/{$uuid}");
            
            // Assemble chunks into final file
            $finalFullPath = Storage::disk('public')->path($finalPath);
            $output = fopen($finalFullPath, 'wb');
            
            if (!$output) {
                throw new \Exception('Failed to create output file');
            }

            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = Storage::disk('local')->path("{$chunkDir}/chunk_{$i}");
                
                if (!file_exists($chunkPath)) {
                    fclose($output);
                    throw new \Exception("Chunk {$i} not found at: {$chunkPath}");
                }
                
                $chunk = fopen($chunkPath, 'rb');
                stream_copy_to_stream($chunk, $output);
                fclose($chunk);
            }
            
            fclose($output);

            // Verify file size
            $actualSize = filesize($finalFullPath);
            $expectedSize = $metadata['filesize'];
            
            if (abs($actualSize - $expectedSize) > 1024) { // Allow 1KB tolerance
                \Log::warning('File size mismatch', [
                    'expected' => $expectedSize,
                    'actual' => $actualSize,
                ]);
            }

            // Clean up chunks
            Storage::disk('local')->deleteDirectory($chunkDir);
            cache()->forget("chunked_upload:{$uploadId}");

            // Create video record
            $video = Video::create([
                'uuid' => $uuid,
                'user_id' => $metadata['user_id'],
                'title' => $metadata['title'],
                'slug' => Str::slug($metadata['title']) . '-' . Str::random(8),
                'description' => $metadata['description'],
                'category_id' => $metadata['category_id'] ?: null,
                'visibility' => $metadata['visibility'],
                'is_short' => $metadata['is_short'],
                'original_path' => $finalPath,
                'status' => 'uploaded',
            ]);

            // Handle tags
            if (!empty($metadata['tags'])) {
                $tagNames = array_map('trim', explode(',', $metadata['tags']));
                $tagNames = array_filter($tagNames);
                
                foreach ($tagNames as $tagName) {
                    $tag = \App\Models\Tag::firstOrCreate(['name' => $tagName]);
                    $video->tags()->attach($tag->id);
                }
            }

            // Generate thumbnail
            $this->videoService->generateThumbnailSync($video);

            // Dispatch processing job
            ProcessVideoJob::dispatch($video);

            \Log::info('Chunked upload completed', [
                'upload_id' => $uploadId,
                'video_id' => $video->id,
                'uuid' => $uuid,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Video uploaded successfully! Processing will begin shortly.',
                'video' => [
                    'id' => $video->id,
                    'uuid' => $video->uuid,
                    'title' => $video->title,
                    'status' => $video->status,
                ],
                'redirect' => route('studio.edit', $video),
            ]);

        } catch (\Exception $e) {
            \Log::error('Chunked upload assembly failed', [
                'upload_id' => $uploadId,
                'error' => $e->getMessage(),
            ]);

            // Clean up on failure
            Storage::disk('local')->deleteDirectory("chunks/{$metadata['user_id']}/{$uploadId}");
            cache()->forget("chunked_upload:{$uploadId}");

            return response()->json([
                'success' => false,
                'message' => 'Failed to assemble video file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel/abort an upload
     */
    public function abort(Request $request)
    {
        $request->validate([
            'upload_id' => 'required|uuid',
        ]);

        $uploadId = $request->upload_id;
        $metadata = cache()->get("chunked_upload:{$uploadId}");
        
        if ($metadata && $metadata['user_id'] === auth()->id()) {
            Storage::disk('local')->deleteDirectory("chunks/{$metadata['user_id']}/{$uploadId}");
            cache()->forget("chunked_upload:{$uploadId}");
        }

        return response()->json([
            'success' => true,
            'message' => 'Upload cancelled',
        ]);
    }
}
