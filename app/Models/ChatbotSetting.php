<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'system_prompt',
        'welcome_message',
        'is_active',
        'product_access_type',
        'allowed_product_ids',
        'ai_settings',
        'max_conversation_length',
        'token_limit_per_message',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allowed_product_ids' => 'array',
        'ai_settings' => 'array',
        'max_conversation_length' => 'integer',
        'token_limit_per_message' => 'integer',
    ];

    /**
     * Get the default chatbot settings
     */
    public static function getDefault()
    {
        return self::first() ?? self::create([
            'name' => 'AI Assistant',
            'system_prompt' => 'You are a helpful AI assistant for an e-commerce website. Help customers find products and answer their questions.',
            'welcome_message' => 'مرحباً! كيف يمكنني مساعدتك اليوم؟',
            'is_active' => true,
            'product_access_type' => 'all',
            'ai_settings' => [
                'model' => 'gemini-2.0-flash-exp',
                'temperature' => 0.7,
                'max_tokens' => 1000,
            ],
            'max_conversation_length' => 50,
            'token_limit_per_message' => 4000,
        ]);
    }

    /**
     * Check if chatbot has access to specific product
     */
    public function hasAccessToProduct($productId)
    {
        if ($this->product_access_type === 'all') {
            return true;
        }

        return in_array($productId, $this->allowed_product_ids ?? []);
    }

    /**
     * Get allowed products
     */
    public function getAllowedProducts()
    {
        if ($this->product_access_type === 'all') {
            return Product::all();
        }

        return Product::whereIn('id', $this->allowed_product_ids ?? [])->get();
    }
}