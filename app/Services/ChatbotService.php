<?php

namespace App\Services;

use App\Models\ChatbotSetting;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Product;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    protected $settings;

    public function __construct()
    {
        $this->settings = ChatbotSetting::getDefault();
    }

    /**
     * Start a new conversation
     */
    public function startConversation($userIp = null, $userAgent = null)
    {
        // Get products context based on settings
        $productsContext = $this->getProductsContext();
        
        $contextData = [
            'products' => $productsContext,
            'system_prompt' => $this->settings->system_prompt,
            'welcome_message' => $this->settings->welcome_message,
        ];

        $conversation = ChatConversation::createNew($userIp, $userAgent, $contextData);

        return [
            'conversation_id' => $conversation->id,
            'session_id' => $conversation->session_id,
            'welcome_message' => $this->settings->welcome_message,
            'chatbot_name' => $this->settings->name,
        ];
    }

    /**
     * Send message to chatbot
     */
    public function sendMessage($conversation, $userMessage)
    {
        if (!$conversation || !$conversation->isActive()) {
            throw new \Exception('Conversation not found or inactive');
        }

        // Check conversation length limit
        if ($conversation->hasExceededMaxLength($this->settings->max_conversation_length)) {
            $conversation->end();
            throw new \Exception('Conversation has reached maximum length');
        }

        // Save user message
        $userMessageRecord = ChatMessage::createUserMessage(
            $conversation->id,
            $userMessage
        );

        $conversation->incrementMessageCount();

        // Prepare AI context - let AI handle product recommendations
        $aiContext = $this->prepareAIContext($conversation, $userMessage);

        try {
            // Get AI response
            $aiResponse = $this->getAIResponse($aiContext);
            
            // Parse AI response for metadata (buttons, products, etc.)
            $parsedResponse = $this->parseAIResponse($aiResponse);

            // Save assistant message
            $assistantMessage = ChatMessage::createAssistantMessage(
                $conversation->id,
                $parsedResponse['content'],
                $parsedResponse['metadata'],
                $parsedResponse['token_count'] ?? null
            );

            $conversation->incrementMessageCount();

            return [
                'message' => $assistantMessage->getFormattedForFrontend(),
                'conversation_status' => $conversation->status,
            ];

        } catch (\Exception $e) {
            Log::error('Chatbot AI Error: ' . $e->getMessage());
            
            // Save error message
            $errorMessage = ChatMessage::createAssistantMessage(
                $conversation->id,
                'عذراً، حدث خطأ في النظام. يرجى المحاولة مرة أخرى.',
                ['error' => true]
            );

            return [
                'message' => $errorMessage->getFormattedForFrontend(),
                'conversation_status' => $conversation->status,
            ];
        }
    }

    /**
     * End conversation
     */
    public function endConversation($sessionId)
    {
        $conversation = ChatConversation::where('session_id', $sessionId)->first();
        
        if ($conversation) {
            $conversation->end();
        }

        return ['status' => 'ended'];
    }

    /**
     * Get conversation history
     */
    public function getConversationHistory($conversation)
    {
        if (!$conversation) {
            throw new \Exception('Conversation not found');
        }

        return [
            'session_id' => $conversation->session_id,
            'status' => $conversation->status,
            'messages' => $conversation->orderedMessages->map(function ($message) {
                return $message->getFormattedForFrontend();
            }),
        ];
    }

    /**
     * Get products context for AI
     */
    protected function getProductsContext()
    {
        $products = $this->settings->getAllowedProducts();
        
        return $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->title,
                'slug' => $product->slug,
                'description' => $product->description,
                'price' => $product->price,
                'category' => $product->category->name ?? null,
                'url' => "https://soapy-bubbles.com/product/{$product->slug}",
            ];
        })->toArray();
    }

    /**
     * Prepare AI context for request
     */
    protected function prepareAIContext($conversation, $userMessage)
    {
        $messages = [];
        
        // Add system prompt with products context for every message
        $systemPrompt = $this->settings->system_prompt;
        
        // Add products context and recommendation instructions for every message
        $productsJson = json_encode($conversation->context_data['products'], JSON_UNESCAPED_UNICODE);
        $systemPrompt .= "\n\nمنتجات المتجر المتاحة:\n" . $productsJson;
        $systemPrompt .= "\n\nتعليمات ترشيح المنتجات:";
        $systemPrompt .= "\n- عندما يسأل المستخدم عن منتج معين أو يذكر احتياجاً، قم بترشيح المنتجات المناسبة من القائمة أعلاه";
        $systemPrompt .= "\n- اختر من 1-3 منتجات فقط الأكثر صلة بطلب المستخدم";
        $systemPrompt .= "\n- في نهاية ردك، أضف قسم 'المنتجات المرشحة:' واذكر أرقام المنتجات المرشحة فقط";
        $systemPrompt .= "\n- مثال: 'المنتجات المرشحة: 5, 12, 8'";
        $systemPrompt .= "\n- إذا لم تجد منتجات مناسبة، لا تذكر أي منتجات";
        $systemPrompt .= "\n-اهم واخطر التعليمات هو انه لا يجب ابدا ترشيح اي منتج غير مرفق لك في قائمه المنتجات";
        
        $messages[] = [
            'role' => 'system',
            'content' => $systemPrompt
        ];

        // Add conversation history (last 10 messages to save tokens)
        $recentMessages = $conversation->orderedMessages()
            ->latest('sent_at')
            ->limit(10)
            ->get()
            ->reverse();

        foreach ($recentMessages as $message) {
            $messages[] = $message->getFormattedForAI();
        }

        // Add current user message
        $messages[] = [
            'role' => 'user',
            'content' => $userMessage
        ];

        // تم إزالة إرسال المنتجات المقترحة من الكود، البوت سيقوم بالترشيح ذاتياً

        return $messages;
    }

    /**
     * Get AI response from Gemini
     */
    protected function getAIResponse($messages)
    {
        $prompt = $this->formatMessagesForGemini($messages);
        
        $model = $this->settings->ai_settings['model'] ?? 'gemini-2.0-flash-exp';
        
        $response = Gemini::generativeModel(model: $model)->generateContent($prompt);
        
        return $response->text();
    }

    /**
     * Format messages for Gemini API
     */
    protected function formatMessagesForGemini($messages)
    {
        $formattedPrompt = "";
        
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $formattedPrompt .= "النظام: " . $message['content'] . "\n\n";
            } elseif ($message['role'] === 'user') {
                $formattedPrompt .= "المستخدم: " . $message['content'] . "\n\n";
            } elseif ($message['role'] === 'assistant') {
                $formattedPrompt .= "المساعد: " . $message['content'] . "\n\n";
            }
        }
        
        $formattedPrompt .= "المساعد: ";
        
        return $formattedPrompt;
    }

    /**
     * Parse AI response for metadata
     */
    protected function parseAIResponse($response)
    {
        $metadata = [];
        $content = $response;
        
        // Extract product recommendations from AI response
        $recommendedProducts = $this->extractProductRecommendationsFromText($response);
        if (!empty($recommendedProducts)) {
            $metadata['recommended_products'] = $recommendedProducts;
        }

        // Look for action buttons
        $buttons = $this->extractActionButtons($response);
        if (!empty($buttons)) {
            $metadata['buttons'] = $buttons;
        }

        return [
            'content' => $content,
            'metadata' => $metadata,
            'token_count' => $this->estimateTokenCount($response),
        ];
    }

    /**
     * Extract product recommendations from AI response text
     */
    protected function extractProductRecommendationsFromText($response)
    {
        $products = [];
        
        // Look for the recommended products section
        if (preg_match('/المنتجات المرشحة:\s*([0-9,\s]+)/u', $response, $matches)) {
            $productIds = array_map('trim', explode(',', $matches[1]));
            $productIds = array_filter($productIds, 'is_numeric');
            
            // Get product details for each ID
            foreach ($productIds as $productId) {
                $product = $this->getProductDetails((int)$productId);
                if ($product) {
                    $products[] = [
                        'id' => $product->id,
                        'name' => $product->title,
                        'slug' => $product->slug ?? str_replace(' ', '-', strtolower($product->title)),
                        'price' => $product->price,
                        'currency' => $product->currency ?? 'ر.س',
                        'image' => $product->images[0] ?? null,
                        'url' => "https://soapy-bubbles.com/product/" . ($product->slug ?? str_replace(' ', '-', strtolower($product->title))),
                    ];
                }
            }
        }
        
        return array_slice($products, 0, 3); // Limit to 3 products
    }

    /**
     * Extract action buttons from AI response
     */
    protected function extractActionButtons($response)
    {
        $buttons = [];
        
        // Common action patterns
        if (stripos($response, 'تصفح المنتجات') !== false || 
            stripos($response, 'عرض المنتجات') !== false) {
            $buttons[] = [
                'text' => 'تصفح جميع المنتجات',
                'action' => 'browse_products',
                'url' => 'https://soapy-bubbles.com/products'
            ];
        }
        
        if (stripos($response, 'تواصل معنا') !== false || 
            stripos($response, 'اتصل بنا') !== false) {
            $buttons[] = [
                'text' => 'تواصل معنا',
                'action' => 'contact_us',
                'url' => 'https://soapy-bubbles.com/contact'
            ];
        }
        
        return $buttons;
    }

    /**
     * Estimate token count for response
     */
    protected function estimateTokenCount($text)
    {
        // Rough estimation: 1 token ≈ 4 characters for Arabic text
        return intval(strlen($text) / 4);
    }

    /**
     * Search products by query
     */
    private function searchProducts($query, $limit = 5)
    {
        return Product::where('is_available', true)
            ->where(function($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('short_description', 'like', "%{$query}%");
            })
            ->select('id', 'title', 'price', 'currency', 'short_description')
            ->limit($limit)
            ->get();
    }

    /**
     * Get product details by ID
     */
    private function getProductDetails($productId)
    {
        return Product::where('id', $productId)
            ->where('is_available', true)
            ->select('id', 'title', 'description', 'price', 'currency', 'images')
            ->first();
    }


}