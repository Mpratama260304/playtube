<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function store(StoreReportRequest $request)
    {
        // Check if user has already reported this target
        $existing = Report::where([
            'reporter_id' => auth()->id(),
            'target_type' => $request->target_type,
            'target_id' => $request->target_id,
        ])->where('status', 'pending')->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reported this content.',
            ], 400);
        }

        Report::create([
            'reporter_id' => auth()->id(),
            'target_type' => $request->target_type,
            'target_id' => $request->target_id,
            'reason' => $request->reason,
            'details' => $request->details,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report submitted. We will review it shortly.',
        ]);
    }
}
