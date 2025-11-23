<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'processed_content',
        'category',
        'source',
        'similarity_score'
    ];

    protected $casts = [
        'similarity_score' => 'decimal:4'
    ];

    public function getExcerptAttribute()
    {
        return substr($this->content, 0, 200) . (strlen($this->content) > 200 ? '...' : '');
    }

    public function getSimilarityPercentAttribute()
    {
        return $this->similarity_score ? round($this->similarity_score * 100, 2) : 0;
    }
}
