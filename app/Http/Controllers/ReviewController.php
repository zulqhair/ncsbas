<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Review;
use App\Models\ReviewComment;
use App\Models\User;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function request(Request $request, Assessment $assessment)
    {
        $user = $request->user();
        abort_unless($assessment->user_id === $user->id || $user->isAdmin(), 403);
        $data = $request->validate(['reviewer_id' => 'required|exists:users,id']);
        $reviewer = User::findOrFail($data['reviewer_id']);
        abort_unless($reviewer->role === User::ROLE_REVIEWER || $reviewer->isAdmin(), 422);

        $review = $assessment->reviews()->whereIn('status', ['pending', 'accepted'])->first();
        if ($review) {
            $review->update([
                'reviewer_id' => $reviewer->id,
                'requested_by_user_id' => $user->id,
                'status' => 'pending',
                'decline_reason' => null,
            ]);
        } else {
            $review = Review::create([
                'assessment_id' => $assessment->id,
                'reviewer_id' => $reviewer->id,
                'requested_by_user_id' => $user->id,
                'status' => 'pending',
            ]);
        }
        $assessment->update(['status' => 'open']);

        return back()->with('status', 'Review assigned and waiting for reviewer acceptance.');
    }

    public function update(Request $request, Review $review)
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $review->reviewer_id === $user->id, 403);
        $data = $request->validate([
            'action' => 'required|in:accept,decline,complete',
            'decline_reason' => 'nullable|string|max:1000',
        ]);
        $status = match ($data['action']) {
            'accept' => 'accepted', 'complete' => 'completed', default => 'open'
        };
        $review->update([
            'status' => $status,
            'decline_reason' => $data['decline_reason'] ?? null,
        ]);
        $review->assessment->update([
            'status' => $status === 'completed'
                ? 'completed'
                : ($status === 'accepted' ? 'in_review' : 'open'),
        ]);

        return back()->with('status', 'Review status updated.');
    }

    public function comment(Request $request, Review $review)
    {
        $user = $request->user();
        abort_unless(
            $user->isAdmin()
                || $review->reviewer_id === $user->id
                || $review->assessment->user_id === $user->id,
            403
        );
        $data = $request->validate(['body' => 'required|string|max:5000']);
        ReviewComment::create([
            'review_id' => $review->id,
            'user_id' => $user->id,
            'body' => $data['body'],
        ]);

        return back()->with('status', 'Comment added.');
    }
}
