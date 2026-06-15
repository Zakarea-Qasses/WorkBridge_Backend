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
                'message' => 'You can add a review only after the contract is completed.',
            ], 422);
        }

        if (! in_array($user->id, [$contract->client_id, $contract->freelancer_id], true)) {
            return response()->json(['message' => 'You are not allowed to do this action.'], 403);
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

    public function update(Request $request, int $id)
    {
        $review = Review::findOrFail($id);

        if ($review->reviewer_id !== $request->user()->id) {
            return response()->json(['message' => 'You are not allowed to update this review.'], 403);
        }

        $data = $request->validate([
            'rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string'],
        ]);

        $review->update($data);

        return response()->json([
            'message' => 'Review updated successfully.',
            'review' => $review,
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $review = Review::findOrFail($id);

        if ($review->reviewer_id !== $request->user()->id) {
            return response()->json(['message' => 'You are not allowed to delete this review.'], 403);
        }

        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully.',
        ]);
    }

    public function userReviews(int $userId)
    {
        $reviews = Review::with('reviewer:id,name,email')
            ->where('reviewed_user_id', $userId)
            ->whereHas('contract', fn ($query) => $query->where('freelancer_id', $userId))
            ->latest()
            ->get();

        return response()->json([
            'rating_avg' => round((float) $reviews->avg('rating'), 2),
            'reviews_count' => $reviews->count(),
            'reviews' => $reviews,
        ]);
    }
}
