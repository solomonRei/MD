<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feed;
use App\Models\RatingCommentShare;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'updateRating', 'getById', 'getByType']]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        if (!$token = auth()->attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return $this->createNewToken($token);
    }

    public function getById($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        return response()->json([
            'user' => $user,
        ], 201);
    }

    public function getByType($type)
    {
        $users = User::where('type_of_account', $type)->get();

        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'No users found with the given account type.',
            ], 404);
        }

        return response()->json([
            'users' => $users,
        ], 201);
    }




    public function register(Request $request)
    {
        // Validate the input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'surname' => 'required|string|max:255',
            'idnp' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'type_of_account' => 'required|string|in:candidate,user,media',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Create a new user in the database
        $user = User::create([
            'name' => $request->name,
            'surname' => $request->surname,
            'idnp' => $request->idnp,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'type_of_account' => $request->type_of_account,
        ]);

        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user
        ], 201);
    }

    public function logout(): \Illuminate\Http\JsonResponse
    {
        Auth::logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        return $this->createNewToken(auth()->refresh());
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile()
    {
        return response()->json(auth()->user());
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeCity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'city' => 'required|string|min:3',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::find(auth()->user()->id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        $user->city = $request->city;
        $user->save();

        return response()->json(['message' => 'City was changed']);
    }

    private function calculateCandidateRating($likes, $comments_rate, $shares): float
    {
        return $likes * 0.4 +
            $comments_rate * 0.4 +
            $shares * 0.3;
    }

    public function uploadImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image.*' => 'file|mimes:jpeg,png,pdf',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $file = $request->file('image');
//        $file = $image[0];
        $user = User::find(auth()->user()->id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        $randomName = Str::random(40);
        $extension = $file->getClientOriginalExtension();
        $fileName = "{$randomName}.{$extension}";

        $user->image = url('/uploads/' . $fileName);
        $user->save();

        $file->move(public_path('uploads'), $fileName);

        return response()->json(['message' => 'Image updated successfully'], 201);
    }


    public function updateRating() {
        // Get all users with type_of_account = 'candidate'
        $candidates = User::where('type_of_account', '=','candidate')->get();

        $candidateRatings = [];

        // Loop through candidates
        foreach ($candidates as $candidate) {
            // Find all feeds resolved by the candidate
            $feeds = Feed::where('resolved_by', $candidate->id)->get();

            $likes = 0;
            $comments = 0;
            $shares = 0;

            // Loop through feeds resolved by the candidate
            $rating_com = 1;
            foreach ($feeds as $feed) {
                // Find the rating, comments, and shares for the feed
                $ratingCommentShare = RatingCommentShare::where('feed_id', $feed->id)->first();

                if ($ratingCommentShare) {
                    $likes += $ratingCommentShare->likes;
                    $shares += $ratingCommentShare->shares;

                    // Find the comments for the rating comment share
                    $feed_comments = Comment::where('rating_comment_share_id', $ratingCommentShare->id)->get();

                    // Loop through comments and calculate the total rating
                    foreach ($feed_comments as $comment) {
                        $rating_com += $comment->rating;
                    }

                    $comments += count($feed_comments);
                }
            }
            $comments_rate = $rating_com/$comments;

            // Calculate the candidate's rating
            $rating = $this->calculateCandidateRating($likes, $comments_rate, $shares);

            // Add the candidate's rating to the array
            $candidateRatings[] = [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'rating' => $rating,
            ];
        }

        // Sort the candidate ratings by rating, in descending order
        usort($candidateRatings, function($a, $b) {
            return $b['rating'] - $a['rating'];
        });

        return response()->json([
            'data' => $candidateRatings,
        ]);
    }
}
