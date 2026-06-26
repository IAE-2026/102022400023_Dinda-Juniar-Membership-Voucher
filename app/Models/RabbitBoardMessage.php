<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RabbitBoardMessage extends Model
{
    protected $table = 'rabbit_board_messages';

    protected $fillable = [
        'event',
        'routing_key',
        'payload',
        'received_at',
    ];

    protected $casts = [
        'payload'     => 'array',
        'received_at' => 'datetime',
    ];
}
