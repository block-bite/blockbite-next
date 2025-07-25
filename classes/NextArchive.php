<?php

namespace Blockbite\Next;

use Blockbite\Next\NextAcf;

class NextArchive
{

    public function format($posts)
    {
        // Load ACF processor if available
        $acfProcessor = null;
        if (class_exists('Blockbite\\Next\\NextAcf')) {
            $acfProcessor = new \Blockbite\Next\NextAcf();
        }

        $formatted = array_map(function($post) use ($acfProcessor) {
            // Get thumbnail as image array (not ID)
            $image = null;
            if (function_exists('get_post_thumbnail_id')) {
               $thumbnail_id = get_post_thumbnail_id($post->ID);
               $image = NextAcf::processImageField($thumbnail_id);
 
            }

            // Get tags
            $tags = function_exists('get_the_tags') ? get_the_tags($post->ID) : [];
            $tagsArr = [];
            if ($tags && is_array($tags)) {
                foreach ($tags as $tag) {
                    $tagsArr[] = [
                        'id' => $tag->term_id,
                        'name' => $tag->name,
                        'slug' => $tag->slug
                    ];
                }
            }

            // Get categories
            $categories = function_exists('get_the_category') ? get_the_category($post->ID) : [];
            $catsArr = [];
            if ($categories && is_array($categories)) {
                foreach ($categories as $cat) {
                    $catsArr[] = [
                        'id' => $cat->term_id,
                        'name' => $cat->name,
                        'slug' => $cat->slug
                    ];
                }
            }

            // Get taxonomies
            $taxonomies = function_exists('get_object_taxonomies') ? get_object_taxonomies($post->post_type, 'objects') : [];
            $inTaxonomies = [];
            if ($taxonomies && is_array($taxonomies)) {
                foreach ($taxonomies as $taxonomy) {
                    $terms = function_exists('get_the_terms') ? get_the_terms($post->ID, $taxonomy->name) : [];
                    if ($terms && !is_wp_error($terms)) {
                        $inTaxonomies[$taxonomy->name] = [];
                        foreach ($terms as $term) {
                            $inTaxonomies[$taxonomy->name][] = [        
                                'id' => $term->term_id,
                                'name' => $term->name,
                                'slug' => $term->slug
                            ];
                        }
                    }
                }
            }

            // Get ACF fields if available
           $acf = (new NextAcf())->getAcfPageFields($post->ID);
           

            return [
                'id'    => $post->ID,
                'slug'  => $post->post_name,
                'title' => get_the_title($post),
                'excerpt' => get_the_excerpt($post),
                'image' => $image,
                'tags' => $tagsArr,
                'categories' => $catsArr,
                'date' => $post->post_date,
                'taxonomies' => $taxonomies, 
                'inTaxonomies' => $inTaxonomies,
                'acf' => $acf,
                // Do NOT include post_content
            ];
        }, $posts);

        return $formatted;
    }
}
