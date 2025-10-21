<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatbotSetting;
use App\Services\ChatbotService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    protected $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    /**
     * Start a new chat conversation
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function startChat(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_agent' => 'nullable|string|max:500',
                'initial_message' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if chatbot is active
            $settings = ChatbotSetting::getDefault();
            if (!$settings->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chatbot is currently disabled'
                ], 503);
            }

            $result = $this->chatbotService->startConversation(
                $request->ip(),
                $request->header('User-Agent', ''),
                $request->input('initial_message')
            );

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Error starting chat: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to start chat conversation'
            ], 500);
        }
    }

    /**
     * Send a message in an existing conversation
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function sendMessage(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|string|exists:chat_conversations,session_id',
                'message' => 'required|string|max:2000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $conversation = ChatConversation::where('session_id', $request->session_id)
                ->where('status', 'active')
                ->first();

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found or inactive'
                ], 404);
            }

            // Check if conversation is still active (within 30 minutes)
            if (!$conversation->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation has expired. Please start a new conversation.'
                ], 410);
            }

            $result = $this->chatbotService->sendMessage(
                $conversation,
                $request->message
            );

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending message: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message'
            ], 500);
        }
    }

    /**
     * Get conversation history
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getHistory(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|string|exists:chat_conversations,session_id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $conversation = ChatConversation::where('session_id', $request->session_id)
                ->with('orderedMessages')
                ->first();

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found'
                ], 404);
            }

            $history = $this->chatbotService->getConversationHistory($conversation);

            return response()->json([
                'success' => true,
                'data' => [
                    'conversation' => [
                        'session_id' => $conversation->session_id,
                        'status' => $conversation->status,
                        'message_count' => $conversation->message_count,
                        'created_at' => $conversation->created_at,
                        'last_activity_at' => $conversation->last_activity_at
                    ],
                    'messages' => $history
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting history: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get conversation history'
            ], 500);
        }
    }

    /**
     * End a conversation
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function endChat(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|string|exists:chat_conversations,session_id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $conversation = ChatConversation::where('session_id', $request->session_id)->first();

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found'
                ], 404);
            }

            $this->chatbotService->endConversation($conversation);

            return response()->json([
                'success' => true,
                'message' => 'Conversation ended successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error ending chat: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to end conversation'
            ], 500);
        }
    }

    /**
     * Get chatbot settings (public information only)
     * 
     * @return JsonResponse
     */
    public function getSettings(): JsonResponse
    {
        try {
            $settings = ChatbotSetting::getDefault();

            return response()->json([
                'success' => true,
                'data' => [
                    'is_active' => $settings->is_active,
                    'welcome_message' => $settings->welcome_message,
                    'max_conversation_length' => $settings->max_conversation_length
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting settings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get chatbot settings'
            ], 500);
        }
    }
}
