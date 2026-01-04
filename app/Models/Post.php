<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class Post extends Model
{
    protected $casts = [
        'post_date' => 'datetime',
    ];

    public function getExcerptAttribute()
    {
        if (! empty($this->post_excerpt)) {
            return $this->post_excerpt;
        }

        $text = strip_tags($this->post_content);
        $text = html_entity_decode($text);
        $text = preg_replace('/\s+/', ' ', $text);

        return strlen($text) > 200 ? substr($text, 0, 197).'...' : $text;
    }

    public function scopePublished($query, $type = 'post')
    {
        return $query->where('post_status', 'publish')
            ->where('post_type', $type);
    }
}
