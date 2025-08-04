<?php

namespace Blockbite\Next;

class NextCors
{
    public function __construct()
    {
        add_action('init', [$this, 'add_cors_headers']);
    }

    public function add_cors_headers()
    {
        if (
            defined('BLOCKBITE_NEXT_FRONTEND') &&
            isset($_SERVER['HTTP_ORIGIN']) &&
            $_SERVER['HTTP_ORIGIN'] === BLOCKBITE_NEXT_FRONTEND
        ) {
            header('Access-Control-Allow-Origin: ' . BLOCKBITE_NEXT_FRONTEND);
            header('Access-Control-Allow-Methods: GET, OPTIONS');
            header('Access-Control-Allow-Headers: Origin, Content-Type, Accept');
        }

        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            status_header(200);
            exit;
        }
    }
}
