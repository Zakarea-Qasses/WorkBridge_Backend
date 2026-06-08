<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'contract_id' => ['required', 'exists:contracts,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string'],
        ]);

        $contract = Contract::findOrFail($data['contract_id']);
        $user = $request->user();

        if ($contract->status !== 'completed') {
            return response()->json([
                'message' => 'You can review only after contract completion.',
            ], 422);
        }

        if (! in_array($user->id, [$contract->client_id, $contract->freelancer_id], true)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $reviewedUserId = $user->id === $contract->client_id
            ? $contract->freelancer_id
            : $contract->client_id;

        $review = Review::create([
            'contract_id' => $contract->id,
            'reviewer_id' => $user->id,
            'reviewed_user_id' => $reviewedUserId,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        return response()->json([
            'message' => 'Review added successfully.',
            'review' => $review,
        ], 201);
    }

    public function userReviews(int $userId)
    {
        $reviews = Review::with('reviewer:id,name,email')
            ->where('reviewed_user_id', $userId)
            ->latest()
            ->get();

        return response()->json([
            'rating_avg' => round((float) $reviews->avg('rating'), 2),
            'reviews_count' => $reviews->count(),
            'reviews' => $reviews,
        ]);
    }
}
