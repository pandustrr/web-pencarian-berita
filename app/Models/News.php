<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'original_text',
        'translated_text',
        'processed_text',
        'category',
        'source'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getExcerptAttribute()
    {
        return \Illuminate\Support\Str::limit($this->original_text, 200);
    }
}
