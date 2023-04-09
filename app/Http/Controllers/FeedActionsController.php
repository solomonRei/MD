<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Feed;
use App\Models\RatingCommentShare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeedActionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['index', 'show']]);
    }

    public function like(Request $request, $feedId)
    {

        $ratingCommentShare = RatingCommentShare::where('feed_id', $feedId)->first();

        if (!$ratingCommentShare) {
            return response()->json([
                'message' => 'Feed not found.',
            ], 404);
        }

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
        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $ratingCommentShare = RatingCommentShare::firstOrNew([
            'feed_id' => $feedId
        ]);

        $ratingCommentShare->save();

        $commentText = $request->input('comment');

        $comment = new Comment();
        $comment->comment = $commentText;
        $comment->rating = $comment->ai($commentText);
        $comment->rating_comment_share_id = $ratingCommentShare->id;
        $comment->save();

        return response()->json([
            'message' => 'Comment added successfully.',
            'data' => [
                'comment' => $comment,
                'ratingCommentShare' => $ratingCommentShare,
            ],
        ], 201);
    }

    public function getComments($feedId)
    {
        $ratingCommentShare = RatingCommentShare::where('feed_id', $feedId)->first();

        if (!$ratingCommentShare) {
            return response()->json([
                'message' => 'Comments not found.',
            ], 404);
        }

        $comments = Comment::where('rating_comment_share_id', $ratingCommentShare->id)->get();

        return response()->json([
            'data' => $comments,
        ]);
    }

    public function getLikes($feedId)
    {
        $ratingCommentShareCount = RatingCommentShare::where('feed_id', $feedId)->first();

        if (!$ratingCommentShareCount) {
            return response()->json([
                'message' => 'Feed not found.',
            ], 404);
        }

        return response()->json([
            'feed_id' => $feedId,
            'count' => $ratingCommentShareCount->rating
        ]);
    }

    public function getShares($feedId)
    {
        $ratingCommentShareCount = RatingCommentShare::where('feed_id', $feedId)->first();

        if (!$ratingCommentShareCount) {
            return response()->json([
                'message' => 'Feed not found.',
            ], 404);
        }

        return response()->json([
            'feed_id' => $feedId,
            'count' => $ratingCommentShareCount->shares
        ]);
    }

    public function share(Request $request, $feedId)
    {

        $ratingCommentShare = RatingCommentShare::where('feed_id', $feedId)->first();

        if (!$ratingCommentShare) {
            return response()->json([
                'message' => 'Feed not found.',
            ], 404);
        }

        $ratingCommentShare->shares += 1;
        $ratingCommentShare->save();

        return response()->json([
            'message' => 'Post shared successfully.',
            'data' => [
                'rating_comment_share' => $ratingCommentShare,
                'feed_id' => $feedId,
            ],
        ]);
    }

}
