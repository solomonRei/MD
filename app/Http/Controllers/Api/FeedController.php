<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feed;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FeedController extends Controller
{

    public function index(): \Illuminate\Http\JsonResponse
    {
        $feeds = Feed::all();
        if ($feeds->isEmpty()) {
            return response()->json(['error' => 'Feeds not found'], 404);
        }
        return response()->json($feeds, 201);
    }

    public function getByUser(): \Illuminate\Http\JsonResponse
    {
        $user_id = auth()->user()->id;

        $feeds = Feed::where('user_id', $user_id)->get();

        if ($feeds->isEmpty()) {
            return response()->json(['error' => 'Feeds not found'], 404);
        }

        return response()->json($feeds, 201);
    }

    public function show($id): \Illuminate\Http\JsonResponse
    {
        $feed = Feed::find($id);

        if (!$feed) {
            return response()->json(['error' => 'Feed not found'], 404);
        }

        return response()->json($feed, 201);
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
                'files.*' => 'file|mimes:jpeg,png,pdf|max:2048',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            DB::beginTransaction();
            $file = $request->file('file');
            $file_id = null;
            if ($file) {
                $this->authorize('create', [File::class, $user_id]);
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
