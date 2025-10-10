<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'provider',
        'payload',
        'received_at',
        'processed',
        'processing_notes',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed' => 'boolean',
        'received_at' => 'datetime',
    ];
}
