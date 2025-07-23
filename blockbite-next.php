<?php
/*
Plugin Name: Blockbite Next Plugin
Description: A custom WordPress plugin with Composer autoloading.
Version: 1.0.1
Author: Your Name
*/

// Require Composer autoload file
require_once __DIR__ . '/vendor/autoload.php';

use Blockbite\Next\NextGutenberg;
use Blockbite\Next\NextArchive;
use Blockbite\Next\NextSearch;
use Blockbite\Blockbite\Frontend as BlockbiteFrontend;

// Register single endpoint for any CPT and slug
add_action('rest_api_init', function () {
    register_rest_route('blockbite/v1/next', '/item(?:/(?P<slug>[a-zA-Z0-9-_\/]+))?', [
        'methods' => 'GET',
        'callback' => function ($data) {

            $is_preview = isset($_GET['preview']) && $_GET['preview'] === 'true';
            $type = $_GET['type'] ?? 'page'; 
            $slug = $data['slug'] ?? null;

            if ($slug === null) {
                // If no slug is provided, fetch the homepage
                $post_id = get_option('page_on_front');
                if (!$post_id) {
                    return new \WP_Error('no_front_page', 'No front page set', ['status' => 404]);
                }
                $post = get_post($post_id);
            } else {
                // Otherwise, fetch the post by slug and type
                $post = get_page_by_path($slug, OBJECT, $type);
            }

            if (!$post) {
                return new \WP_Error('not_found', 'Item not found', ['status' => 404]);
            }

            $gutenberg = new NextGutenberg();
            return [
                'id' => $post->ID,
                'slug' => $slug,
                'type' => $type,
                'post_status'    => $is_preview ? ['publish', 'draft', 'private', 'future'] : 'publish',
                'title' => get_the_title($post),
                'blocks' => $gutenberg->getBlocks($post->post_content),
            ];
        },
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('blockbite/v1/next', '/items(?:/(?P<type>[a-zA-Z0-9-_]+))?', [
        'methods' => 'GET',
        'callback' => function ($data) {
            $type = $data['type'] ?? 'page'; // Default to 'page' if type is not provided
            $args = [
                'post_type' => $type,
                'post_status' => 'publish',
                'numberposts' => -1,
            ];

            $posts = get_posts($args);
            $archive = new NextArchive();
            return $archive->format($posts);
        },
        'permission_callback' => '__return_true',
    ]);


    register_rest_route('blockbite/v1/next', '/search(?:/(?P<type>[a-zA-Z0-9-_]+))?', [
        'methods' => 'GET',
        'callback' => function () 
        {


            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : -1;
           
            $args = [
                
                'post_type' => ['post', 'page'],
                'post_status' => 'publish',
                'numberposts' => $limit,
            ];

            // optional type parameter
            $query = sanitize_text_field($_GET['query'] ?? '');
            if (!empty($query)) {
                $args['s'] = $query; 
            }


            $posts = get_posts($args);
            $search = new NextSearch();
            return $search->format($posts);
        },
        'permission_callback' => '__return_true',
    ]);


    register_rest_route('blockbite/v1/next', '/config', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => function () {
            // check if the BlockbiteFrontend class exists
            if (!class_exists('Blockbite\Blockbite\Frontend')) {
                return new \WP_Error('class_not_found', 'BlockbiteFrontend class not found', ['status' => 500]);
            }

            $css = BlockbiteFrontend::getFrontendCss();

            return [
                'css' => $css,
            ];
        },
    ]);
});

