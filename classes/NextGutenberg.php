<?php

namespace Blockbite\Next;

use Blockbite\Next\NextAcf;

class NextGutenberg{


    private $acf;

    public function __construct()
    {
        // Constructor logic here
        $this->acf = new NextAcf();
    }

    public function getBlocks($content)
    {
        $parsed_blocks = parse_blocks($content);

   
        return $this->parseBlocksRecursive($parsed_blocks);
    }

    private function parseBlocksRecursive($blocks, $recursive = false)
    {
        $result = [];
        $unique_id = 0;

        foreach ($blocks as $key => $block) {
            $block_name = $block['blockName'] ?? null;
          
            $parsed_block = [
                'id'            => uniqid('block_' .$key),
                'blockName'     => $block_name,
                'innerHTML'     => $block['innerHTML'],
                'attrs'         => $block['attrs'] ?? [],
                'acf' =>        $this->acf->getAcfPageFields($block),
            ];

            if (!empty($block['innerBlocks'])) {
                $parsed_block['innerBlocks'] = $this->parseBlocksRecursive($block['innerBlocks'], true);
            }

            $result[] = $parsed_block;
        }

        return $result;
    }


    private function getBlockAttributesWithDefaults($block_name, $runtime_attrs)
    {
        if (!$block_name) return [];

        $registry = \WP_Block_Type_Registry::get_instance();
        $block_type = $registry->get_registered($block_name);

        if (!$block_type) return [];

        $defined_attrs = $block_type->get_attributes();
        $merged_attrs = [];

        foreach ($defined_attrs as $key => $schema) {
            if (isset($runtime_attrs[$key])) {
                $merged_attrs[$key] = $runtime_attrs[$key];
            } elseif (array_key_exists('default', $schema)) {
                $merged_attrs[$key] = $schema['default'];
            } else {
                $merged_attrs[$key] = null;
            }
        }

        return $merged_attrs;
    }



    private function renderBlock($block)
    {
        return render_block($block);
    }
}
