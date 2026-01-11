<?php

namespace App\Http\Controllers;

use App\Models\CreatorRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CreatorRequestController extends Controller
{
    /**
     * Get the current user's creator status
     */
    public function status(): JsonResponse
    {
        $user = auth()->user();
        
        return response()->json([
            'is_creator' => $user->isCreator(),
            'can_upload' => $user->canUploadVideos(),
            'status' => $user->creator_status,
            'has_pending_request' => $user->hasPendingCreatorRequest(),
            'latest_request' => $user->latestCreatorRequest ? [
                'status' => $user->latestCreatorRequest->status,
                'reason' => $user->latestCreatorRequest->reason,
                'admin_notes' => $user->latestCreatorRequest->admin_notes,
                'created_at' => $user->latestCreatorRequest->created_at->diffForHumans(),
                'reviewed_at' => $user->latestCreatorRequest->reviewed_at?->diffForHumans(),
            ] : null,
        ]);
    }

    /**
     * Submit a creator request
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Check if already a creator
        if ($user->isCreator()) {
            return response()->json([
                'success' => false,
                'message' => 'You are already a creator!',
            ], 400);
        }

        // Check for pending request
        if ($user->hasPendingCreatorRequest()) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending creator request. Please wait for admin review.',
            ], 400);
        }

        $request->validate([
            'reason' => 'required|string|min:20|max:1000',
        ], [
            'reason.required' => 'Please explain why you want to become a creator.',
            'reason.min' => 'Please provide more detail (at least 20 characters).',
            'reason.max' => 'Your reason is too long (maximum 1000 characters).',
        ]);

        $creatorRequest = CreatorRequest::create([
            'user_id' => $user->id,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Your creator request has been submitted! An administrator will review it soon.',
            'request' => [
                'id' => $creatorRequest->id,
                'status' => $creatorRequest->status,
                'created_at' => $creatorRequest->created_at->diffForHumans(),
            ],
        ]);
    }

    /**
     * Cancel a pending creator request
     */
    public function cancel(): JsonResponse
    {
        $user = auth()->user();
        
        $pendingRequest = $user->creatorRequests()->where('status', 'pending')->first();
        
        if (!$pendingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'No pending request found.',
            ], 404);
        }

        $pendingRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Your creator request has been cancelled.',
        ]);
    }
}
