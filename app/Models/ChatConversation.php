<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ChatConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'user_ip',
        'user_agent',
        'context_data',
        'status',
        'last_activity_at',
        'message_count',
    ];

    protected $casts = [
        'context_data' => 'array',
        'last_activity_at' => 'datetime',
        'message_count' => 'integer',
    ];

    /**
     * Relationship with chat messages
     */
    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    /**
     * Get messages ordered by sent_at
     */
    public function orderedMessages()
    {
        return $this->messages()->orderBy('sent_at');
    }

    /**
     * Generate unique session ID
     */
    public static function generateSessionId()
    {
        do {
            $sessionId = Str::random(32);
        } while (self::where('session_id', $sessionId)->exists());

        return $sessionId;
    }

    /**
     * Create new conversation
     */
    public static function createNew($userIp = null, $userAgent = null, $contextData = null)
    {
        return self::create([
            'session_id' => self::generateSessionId(),
            'user_ip' => $userIp,
            'user_agent' => $userAgent,
            'context_data' => $contextData,
            'status' => 'active',
            'last_activity_at' => now(),
            'message_count' => 0,
        ]);
    }

    /**
     * Update last activity
     */
    public function updateActivity()
    {
        $this->update([
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Increment message count
     */
    public function incrementMessageCount()
    {
        $this->increment('message_count');
        $this->updateActivity();
    }

    /**
     * End conversation
     */
    public function end()
    {
        $this->update([
            'status' => 'ended',
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Check if conversation is active
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Check if conversation has exceeded max length
     */
    public function hasExceededMaxLength($maxLength = 50)
    {
        return $this->message_count >= $maxLength;
    }

    /**
     * Get conversation context for AI
     */
    public function getContextForAI()
    {
        return [
            'session_id' => $this->session_id,
            'message_count' => $this->message_count,
            'context_data' => $this->context_data,
            'conversation_history' => $this->orderedMessages()
                ->select(['role', 'content', 'sent_at'])
                ->get()
                ->toArray(),
        ];
    }
}