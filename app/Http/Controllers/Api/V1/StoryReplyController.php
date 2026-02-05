<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessStoryReplyChat;
use App\Models\ChatMessage;
use App\Models\Story;
use App\Models\StoryReply;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StoryReplyController extends Controller
{
    /**
     * Get replies for a story (only for story owner)
     */
    public function index(Request $request, Story $story): JsonResponse
    {
        $currentUser = $request->user();

        // Only story owner can see replies
        if ($story->user_id !== $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé',
            ], 403);
        }

        $replies = $story->replies()
            ->with(['user:id,username,first_name,last_name,avatar,is_premium'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $replies->getCollection()->transform(function ($reply) {
            if ($reply->is_anonymous) {
                $reply->user = null;
            }
            return $reply;
        });

        return response()->json([
            'success' => true,
            'data' => $replies,
        ]);
    }

    /**
     * Reply to a story
     */
    public function store(Request $request, Story $story): JsonResponse
    {
        Log::info('Story reply attempt', [
            'story_id' => $story->id,
            'user_id' => $request->user()?->id,
            'type' => $request->input('type'),
            'has_content' => $request->filled('content'),
            'has_media' => $request->hasFile('media'),
            'story_active' => $story->isActive(),
        ]);

        $validator = Validator::make($request->all(), [
            'content' => 'nullable|string|max:1000',
            'type' => 'required|in:text,voice,image,emoji',
            'media' => 'nullable|file|max:10240', // 10MB max
            'voice_effect' => 'nullable|in:pitch_up,pitch_down,robot,chipmunk,deep',
            'is_anonymous' => 'nullable|boolean',
        ]);

        $validator->after(function ($validator) use ($request) {
            $hasContent = $request->filled('content');
            $hasMedia = $request->hasFile('media');
            $type = $request->input('type');

            // Pour les types text/emoji, le contenu est requis
            if (in_array($type, ['text', 'emoji']) && !$hasContent) {
                $validator->errors()->add('content', 'Le contenu est requis pour ce type de réponse.');
            }
            // Pour les types voice/image, le média est requis
            if (in_array($type, ['voice', 'image']) && !$hasMedia) {
                $validator->errors()->add('media', 'Le fichier média est requis pour ce type de réponse.');
            }
        });

        if ($validator->fails()) {
            Log::warning('Story reply validation failed', [
                'story_id' => $story->id,
                'errors' => $validator->errors()->toArray(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors(),
            ], 422);
        }

        $currentUser = $request->user();

        // Check if story is still active
        if (!$story->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette story a expiré',
            ], 400);
        }

        // Check if blocked
        $storyOwner = $story->user;
        if ($storyOwner && ($currentUser->hasBlocked($storyOwner) || $storyOwner->hasBlocked($currentUser))) {
            return response()->json([
                'success' => false,
                'message' => 'Action impossible',
            ], 400);
        }

        $mediaUrl = null;
        if ($request->hasFile('media')) {
            $path = $request->file('media')->store('story-replies', 'public');
            $mediaUrl = $path;
        }

        $reply = StoryReply::create([
            'story_id' => $story->id,
            'user_id' => $currentUser->id,
            'content' => $request->content,
            'type' => $request->type,
            'media_url' => $mediaUrl,
            'voice_effect' => $request->voice_effect,
            'is_anonymous' => $request->boolean('is_anonymous', true),
        ]);

        // Dispatch chat creation as async job to avoid latency
        if ($storyOwner && $currentUser->id !== $storyOwner->id) {
            $chatType = match ($request->type) {
                'voice' => ChatMessage::TYPE_VOICE,
                'image' => ChatMessage::TYPE_IMAGE,
                default => ChatMessage::TYPE_TEXT,
            };

            ProcessStoryReplyChat::dispatch(
                senderId: $currentUser->id,
                storyOwnerId: $storyOwner->id,
                storyReplyId: $reply->id,
                messageType: $chatType,
                content: $request->content,
                mediaUrl: $mediaUrl,
                voiceEffect: $request->voice_effect,
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Réponse envoyée',
            'data' => $reply,
        ], 201);
    }

    /**
     * Delete a reply
     */
    public function destroy(Request $request, StoryReply $reply): JsonResponse
    {
        $currentUser = $request->user();

        // Only reply author or story owner can delete
        if ($reply->user_id !== $currentUser->id && $reply->story->user_id !== $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé',
            ], 403);
        }

        // Delete media if exists
        if ($reply->media_url) {
            Storage::disk('public')->delete($reply->media_url);
        }

        $reply->delete();

        return response()->json([
            'success' => true,
            'message' => 'Réponse supprimée',
        ]);
    }
}
