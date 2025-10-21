<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'metadata',
        'token_count',
        'sent_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'token_count' => 'integer',
        'sent_at' => 'datetime',
    ];

    /**
     * Relationship with conversation
     */
    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    /**
     * Create user message
     */
    public static function createUserMessage($conversationId, $content, $metadata = null)
    {
        return self::create([
            'conversation_id' => $conversationId,
            'role' => 'user',
            'content' => $content,
            'metadata' => $metadata,
            'sent_at' => now(),
        ]);
    }

    /**
     * Create assistant message
     */
    public static function createAssistantMessage($conversationId, $content, $metadata = null, $tokenCount = null)
    {
        return self::create([
            'conversation_id' => $conversationId,
            'role' => 'assistant',
            'content' => $content,
            'metadata' => $metadata,
            'token_count' => $tokenCount,
            'sent_at' => now(),
        ]);
    }

    /**
     * Check if message is from user
     */
    public function isUserMessage()
    {
        return $this->role === 'user';
    }

    /**
     * Check if message is from assistant
     */
    public function isAssistantMessage()
    {
        return $this->role === 'assistant';
    }

    /**
     * Get formatted message for AI context
     */
    public function getFormattedForAI()
    {
        return [
            'role' => $this->role,
            'content' => $this->content,
        ];
    }

    /**
     * Get message with metadata for frontend
     */
    public function getFormattedForFrontend()
    {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'content' => $this->content,
            'metadata' => $this->metadata,
            'sent_at' => $this->sent_at->toISOString(),
        ];
    }
}