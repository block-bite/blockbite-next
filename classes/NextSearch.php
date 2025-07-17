<?php

namespace Blockbite\Next;

class NextSearch
{
    public function format($posts)
    {
        return array_map(function($post) {
            return [
                'id'    => $post->ID,
                'slug'  => $post->post_name,
                'title' => get_the_title($post),
                'excerpt' => get_the_excerpt($post),
            ];
        }, $posts);
    }
}
