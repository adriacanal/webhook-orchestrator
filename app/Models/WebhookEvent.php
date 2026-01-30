<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'provider',
        'provider_event_id',
        'type',
        'raw_payload',
        'normalized_payload',
        'status',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'normalized_payload' => 'array',
    ];
}
