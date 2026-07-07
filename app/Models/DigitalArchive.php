<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DigitalArchive extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'table_name',
        'original_id',
        'payload',
        'reason'
    ];

    protected $casts = [
        'payload' => 'array',
        'archived_at' => 'datetime'
    ];
}