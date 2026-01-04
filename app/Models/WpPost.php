<?php

namespace App\Models;

use App\Models\Post;

class WpPost extends Post
{
    protected $connection = 'wordpress';
    protected $table = 'posts';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    protected $fillable = [
        'post_author', 'post_date', 'post_content', 'post_title', 'post_excerpt', 'post_status', 'post_name', 'post_type',
    ];
}
