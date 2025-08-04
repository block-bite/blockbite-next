<?php

namespace Blockbite\Next;

class NextPreview
{
    public function __construct()
    {

        add_action('template_redirect', [$this, 'interceptPreview'], 0);
    }

    public function interceptPreview()
    {
        if (is_admin() || !is_singular()) {
            return; // Skip for admin or non-singular views
        }

        $is_preview = isset($_GET['preview']) && $_GET['preview'] === 'true';

        if ($is_preview) {
            global $post;

            $slug = $post->post_name ?? '';
            $type = $post->post_type ?? 'page';
            $frontend_url = defined('NEXT_FRONTEND_URL') ? NEXT_FRONTEND_URL : 'http://localhost:8080';


            $type = $post->post_type ?? 'page';
            $site_url = home_url();
            $permalink = get_permalink($post);
            $relative_path = str_replace($site_url, '', $permalink);

            $preview_url = $frontend_url . $relative_path;
            if ($is_preview) {
                $preview_url .= (str_contains($relative_path, '?') ? '&' : '?') . 'preview=true';
            }

            $api_url = home_url('/wp-json/blockbite/v1/next/item/' . $slug . '?type=' . $type . '&preview=true');

            // Output the custom HTML directly
            include plugin_dir_path(__DIR__) . 'templates/preview-template.php';

            exit; // Stop further template loading
        }
    }

    public function getPreviewIcon()
    {
        return '<svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path id="Vector" d="M10.0002 5H8.2002C7.08009 5 6.51962 5 6.0918 5.21799C5.71547 5.40973 5.40973 5.71547 5.21799 6.0918C5 6.51962 5 7.08009 5 8.2002V15.8002C5 16.9203 5 17.4801 5.21799 17.9079C5.40973 18.2842 5.71547 18.5905 6.0918 18.7822C6.5192 19 7.07899 19 8.19691 19H15.8031C16.921 19 17.48 19 17.9074 18.7822C18.2837 18.5905 18.5905 18.2839 18.7822 17.9076C19 17.4802 19 16.921 19 15.8031V14M20 9V4M20 4H15M20 4L13 11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>';
    }
}
