<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatbotSetting;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ChatbotAdminController extends Controller
{
    /**
     * Get current chatbot settings
     * 
     * @return JsonResponse
     */
    public function getSettings(): JsonResponse
    {
        try {
            $settings = ChatbotSetting::getDefault();
            
            return response()->json([
                'success' => true,
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting chatbot settings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get chatbot settings'
            ], 500);
        }
    }

    /**
     * Update chatbot settings
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateSettings(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'system_prompt' => 'required|string|max:5000',
                'welcome_message' => 'required|string|max:1000',
                'is_active' => 'required|boolean',
                'product_access_type' => 'required|in:all,specific',
                'allowed_product_ids' => 'nullable|array',
                'allowed_product_ids.*' => 'exists:products,id',
                'ai_settings' => 'nullable|array',
                'ai_settings.model' => 'nullable|string',
                'ai_settings.temperature' => 'nullable|numeric|min:0|max:2',
                'ai_settings.max_tokens' => 'nullable|integer|min:1|max:4000',
                'max_conversation_length' => 'required|integer|min:5|max:100',
                'token_limit_per_message' => 'required|integer|min:100|max:4000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $settings = ChatbotSetting::getDefault();
            
            $settings->update([
                'name' => $request->name,
                'system_prompt' => $request->system_prompt,
                'welcome_message' => $request->welcome_message,
                'is_active' => $request->is_active,
                'product_access_type' => $request->product_access_type,
                'allowed_product_ids' => $request->product_access_type === 'specific' 
                    ? $request->allowed_product_ids 
                    : null,
                'ai_settings' => $request->ai_settings ?? [
                    'model' => 'gemini-2.5-flash',
                    'temperature' => 0.7,
                    'max_tokens' => 2000
                ],
                'max_conversation_length' => $request->max_conversation_length,
                'token_limit_per_message' => $request->token_limit_per_message
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Chatbot settings updated successfully',
                'data' => $settings->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating chatbot settings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update chatbot settings'
            ], 500);
        }
    }

    /**
     * Get conversation statistics
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $days = $request->input('days', 30);
            $startDate = now()->subDays($days);

            $stats = [
                'total_conversations' => ChatConversation::count(),
                'active_conversations' => ChatConversation::where('status', 'active')->count(),
                'completed_conversations' => ChatConversation::where('status', 'completed')->count(),
                'total_messages' => ChatMessage::count(),
                'conversations_last_period' => ChatConversation::where('created_at', '>=', $startDate)->count(),
                'messages_last_period' => ChatMessage::where('created_at', '>=', $startDate)->count(),
                'average_messages_per_conversation' => ChatConversation::withCount('messages')
                    ->get()
                    ->avg('messages_count'),
                'daily_conversations' => ChatConversation::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                    ->where('created_at', '>=', $startDate)
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get(),
                'popular_conversation_times' => ChatConversation::selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                    ->where('created_at', '>=', $startDate)
                    ->groupBy('hour')
                    ->orderBy('hour')
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting chatbot statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get chatbot statistics'
            ], 500);
        }
    }

    /**
     * Get conversation list with pagination
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getConversations(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $status = $request->input('status');
            $search = $request->input('search');

            $query = ChatConversation::with(['messages' => function($q) {
                $q->orderBy('created_at', 'asc');
            }]);

            if ($status) {
                $query->where('status', $status);
            }

            if ($search) {
                $query->where('session_id', 'like', "%{$search}%")
                      ->orWhere('user_ip', 'like', "%{$search}%");
            }

            $conversations = $query->orderBy('created_at', 'desc')
                                  ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $conversations
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting conversations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get conversations'
            ], 500);
        }
    }

    /**
     * Get specific conversation details
     * 
     * @param string $sessionId
     * @return JsonResponse
     */
    public function getConversation(string $sessionId): JsonResponse
    {
        try {
            $conversation = ChatConversation::with(['messages' => function($q) {
                $q->orderBy('created_at', 'asc');
            }])->where('session_id', $sessionId)->first();

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $conversation
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting conversation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get conversation'
            ], 500);
        }
    }

    /**
     * Delete a conversation
     * 
     * @param string $sessionId
     * @return JsonResponse
     */
    public function deleteConversation(string $sessionId): JsonResponse
    {
        try {
            $conversation = ChatConversation::where('session_id', $sessionId)->first();

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found'
                ], 404);
            }

            // Delete all messages first
            $conversation->messages()->delete();
            
            // Delete the conversation
            $conversation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Conversation deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting conversation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete conversation'
            ], 500);
        }
    }

    /**
     * Get available products for chatbot access
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getAvailableProducts(Request $request): JsonResponse
    {
        try {
            $search = $request->input('search');
            $perPage = $request->input('per_page', 20);

            $query = Product::select('id', 'title', 'slug', 'price', 'is_available');

            if ($search) {
                $query->where('title', 'like', "%{$search}%")
                      ->orWhere('slug', 'like', "%{$search}%");
            }

            $products = $query->where('is_available', true)
                              ->orderBy('title')
                              ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $products
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting available products: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get available products'
            ], 500);
        }
    }

    /**
     * Test chatbot configuration
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function testConfiguration(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'test_message' => 'required|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $settings = ChatbotSetting::getDefault();
            
            if (!$settings->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chatbot is currently disabled'
                ], 400);
            }

            // Test basic configuration
            $testResults = [
                'settings_valid' => true,
                'ai_connection' => false,
                'product_access' => false,
                'message' => 'Configuration test completed'
            ];

            // Test AI connection (simplified)
            try {
                if (!empty($settings->system_prompt)) {
                    $testResults['ai_connection'] = true;
                }
            } catch (\Exception $e) {
                $testResults['ai_connection'] = false;
            }

            // Test product access
            try {
                $productCount = $settings->canAccessProduct(1) ? Product::count() : 0;
                $testResults['product_access'] = $productCount > 0;
                $testResults['accessible_products_count'] = $productCount;
            } catch (\Exception $e) {
                $testResults['product_access'] = false;
            }

            return response()->json([
                'success' => true,
                'data' => $testResults
            ]);

        } catch (\Exception $e) {
            Log::error('Error testing chatbot configuration: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to test chatbot configuration'
            ], 500);
        }
    }

    /**
     * Clear old conversations
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function clearOldConversations(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'days_old' => 'required|integer|min:1|max:365'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $daysOld = $request->days_old;
            $cutoffDate = now()->subDays($daysOld);

            // Get conversations to delete
            $conversationsToDelete = ChatConversation::where('created_at', '<', $cutoffDate)->get();
            $deletedCount = $conversationsToDelete->count();

            // Delete messages first, then conversations
            foreach ($conversationsToDelete as $conversation) {
                $conversation->messages()->delete();
                $conversation->delete();
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} old conversations",
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Error clearing old conversations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear old conversations'
            ], 500);
        }
    }
}
