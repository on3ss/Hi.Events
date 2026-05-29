<?php

namespace HiEvents\Models;

class RazorpayWebhookEvent extends BaseModel
{
    protected $fillable = [
        'event_id',
        'event_type',
        'entity_id',
        'entity_type',
        'status',
        'retry_count',
        'payload',
        'headers',
        'signature',
        'processing_time_ms',
        'exception',
        'received_at',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}