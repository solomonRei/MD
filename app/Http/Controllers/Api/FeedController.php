<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Feed;
use App\Models\File;
use App\Models\RatingCommentShare;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FeedController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['index', 'show', 'getByUser']]);
    }

    public function index(): \Illuminate\Http\JsonResponse
    {
        $feeds = Feed::all();
        if ($feeds->isEmpty()) {
            return response()->json(['error' => 'Feeds not found'], 404);
        }

        $results = [];

        foreach ($feeds as $feed) {
            $ratingCommentShare = RatingCommentShare::where('feed_id', $feed->id)->first();
            if ($ratingCommentShare !== null) {
                $commentsCount = Comment::where('rating_comment_share_id', $ratingCommentShare->id)->count();
                $results[] = [
                    'id' => $feed->id,
                    'file' => url('/uploads/' . $feed->file->filename),
                    'title' => '',
                    'status' => $feed->status,
                    'description' => $feed->description,
                    'rating' => $ratingCommentShare->rating,
                    'comments' => $commentsCount,
                    'shares' => $ratingCommentShare->shares,
                    'user' => $feed->user()
                ];
            } else {
                $results[] = [
                    'id' => $feed->id,
                    'file' => url('/uploads/' . $feed->file->filename),
                    'title' => '',
                    'status' => $feed->status,
                    'description' => $feed->description,
                    'rating' => 0,
                    'comments' => 0,
                    'shares' => 0,
                    'user' => $feed->user()
                ];
            }
        }

        return response()->json(['feeds' => $results, 'user' => auth()->user() === null ? [] : auth()->user()], 201);

    }


    public function getByUser($id = null): \Illuminate\Http\JsonResponse
    {
        if ($id) {
            $user_id = $id;
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'message' => 'User not found.',
                ], 404);
            }
        } else {
            if (!auth()->user()) {
                return response()->json(['error' => ' Unthorized'], 404);
            }
            $user = auth()->user();
            $user_id = auth()->user()->id;
        }

        $feeds = Feed::where('user_id', $user_id)->get();

        if ($feeds->isEmpty()) {
            return response()->json(['error' => 'Feeds not found'], 404);
        }

        $results = [];

        foreach ($feeds as $feed) {
            $ratingCommentShare = RatingCommentShare::where('feed_id', $feed->id)->first();
            if ($ratingCommentShare !== null) {
                $commentsCount = Comment::where('rating_comment_share_id', $ratingCommentShare->id)->count();
                $results[] = [
                    'id' => $feed->id,
                    'file' => url('/uploads/' . $feed->file->filename),
                    'title' => '',
                    'status' => $feed->status,
                    'description' => $feed->description,
                    'rating' => $ratingCommentShare->rating,
                    'comments' => $commentsCount,
                    'shares' => $ratingCommentShare->shares,
                    'user' => $feed->user()
                ];
            } else {
                $results[] = [
                    'id' => $feed->id,
                    'file' => url('/uploads/' .$feed->file->filename),
                    'title' => '',
                    'status' => $feed->status,
                    'description' => $feed->description,
                    'rating' => 0,
                    'comments' => 0,
                    'shares' => 0,
                    'user' => $feed->user()
                ];
            }
        }

        return response()->json(['feeds' => $results, 'user' => $user], 201);
    }




    public function show($id): \Illuminate\Http\JsonResponse
    {
        $feed = Feed::find($id);

        if (!$feed) {
            return response()->json(['error' => 'Feed not found'], 404);
        }

        $ratingCommentShare = RatingCommentShare::where('feed_id', $feed->id)->first();
        if ($ratingCommentShare !== null) {
            $commentsCount = Comment::where('rating_comment_share_id', $ratingCommentShare->id)->count();
            $results = [
                'id' => $feed->id,
                'file' => url('/uploads/' . $feed->file->filename),
                'title' => '',
                'status' => $feed->status,
                'description' => $feed->description,
                'rating' => $ratingCommentShare->rating,
                'comments' => $commentsCount,
                'shares' => $ratingCommentShare->shares,
                'user' => $feed->user()
            ];
        } else {
            $results = [
                'id' => $feed->id,
                'file' => url('/uploads/' . $feed->file->filename),
                'title' => '',
                'status' => $feed->status,
                'description' => $feed->description,
                'rating' => 0,
                'comments' => 0,
                'shares' => 0,
                'user' => $feed->user()
            ];
        }

        return response()->json(['feed' => $results, 'user' => auth()->user() === null ? [] : auth()->user()], 201);
    }

    private function getComments($feedId)
    {
        $ratingCommentShare = RatingCommentShare::where('feed_id', $feedId)->first();

        if (!$ratingCommentShare) {
            return [];
        }

        return Comment::where('rating_comment_share_id', $ratingCommentShare->id)->get();
    }

    private function getLikes($feedId)
    {
        $ratingCommentShareCount = RatingCommentShare::where('feed_id', $feedId)->first();

        if (!$ratingCommentShareCount) {
            return [];
        }

        return $ratingCommentShareCount->rating;
    }

    private function getShares($feedId)
    {
        $ratingCommentShareCount = RatingCommentShare::where('feed_id', $feedId)->first();

        if (!$ratingCommentShareCount) {
           return [];
        }

        return $ratingCommentShareCount->shares;
    }

    /**
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create(Request $request): ?\Illuminate\Http\JsonResponse
    {
//        $user = JWTAuth::parseToken()->authenticate();
        $user_id = auth()->user()->id;
        try {
            $this->authorize('create', [Feed::class, $user_id]);

            $validator = Validator::make($request->all(), [
                'content' => 'required|string|min:50',
                'image.*' => 'file|mimes:jpeg,png,pdf',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            DB::beginTransaction();
            $image = $request->file('image');
            $file = $image[0];
            $file_id = 1;
            if ($file) {
                $fileModel = new File();

                $randomName = Str::random(40);
                $extension = $file->getClientOriginalExtension();
                $fileName = "{$randomName}.{$extension}";

                $fileModel->filename = $fileName;
                $fileModel->save();

                $file->move(public_path('uploads'), $fileName);

                $file_id = $fileModel->id;
            }
            $feed = new Feed();
            $feed->user_id = $user_id;
            $feed->content = $request->input('content');
            $feed->file_id = $file_id;
            $feed->save();
            DB::commit();

            return response()->json(['message' => 'Feed created successfully'], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id): ?\Illuminate\Http\JsonResponse
    {
        try {
            $feed = Feed::find($id);
            if (!$feed) {
                return response()->json(['error' => 'Feed not found'], 404);
            }
            $user_id = auth()->user()->id;
            $this->authorize('update', [$feed, $user_id]);

            $validator = Validator::make($request->all(), [
                'content' => 'required|string|min:50',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $feed->content = $request->input('content');
            $feed->save();

            return response()->json(['message' => 'Feed updated successfully'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'You are not authorized to delete this feed'], 403);
        }
    }

    public function destroy($id): ?\Illuminate\Http\JsonResponse
    {
        try {
            $feed = Feed::find($id);
            if (!$feed) {
                return response()->json(['error' => 'Feed not found'], 404);
            }
            $user_id = auth()->user()->id;
            $this->authorize('delete', [$feed, $user_id]);

            $feed->delete();

            return response()->json(['message' => 'Feed deleted successfully'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'You are not authorized to delete this feed'], 403);
        }
    }
}
