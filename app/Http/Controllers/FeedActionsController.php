<?php

namespace App\Http\Controllers;

use App\Models\Feed;
use App\Models\RatingCommentShare;
use Illuminate\Http\Request;

class FeedActionsController extends Controller
{
    public function like(Request $request, $feedId)
    {
        $user = $request->user();

        $ratingCommentShare = RatingCommentShare::firstOrNew([
            'feed_id' => $feedId,
            'user_id' => $user->id,
        ]);

        if ($ratingCommentShare->rating == 1) {
            $ratingCommentShare->rating = 0;
        } else {
            $ratingCommentShare->rating = 1;
        }
        $ratingCommentShare->save();

        return response()->json([
            'message' => 'Post liked successfully.',
            'data' => $ratingCommentShare,
        ]);
    }

    public function comment(Request $request, $feedId)
    {
        $user = $request->user();

        $ratingCommentShare = RatingCommentShare::firstOrNew([
            'feed_id' => $feedId,
            'user_id' => $user->id,
        ]);

        ++$ratingCommentShare->comments;
        $ratingCommentShare->save();

        return response()->json([
            'message' => 'Comment added successfully.',
            'data' => $ratingCommentShare,
        ]);
    }

    public function share(Request $request, $feedId)
    {
        $user = $request->user();

        $ratingCommentShare = RatingCommentShare::firstOrNew([
            'feed_id' => $feedId,
            'user_id' => $user->id,
        ]);

        $ratingCommentShare->shares += 1;
        $ratingCommentShare->save();

        // Generate link to post
        $url = route('post.show', ['id' => $feedId]);

        return response()->json([
            'message' => 'Post shared successfully.',
            'data' => [
                'rating_comment_share' => $ratingCommentShare,
                'post_url' => $url,
            ],
        ]);
    }

}
