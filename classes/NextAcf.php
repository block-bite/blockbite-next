<?php

namespace Blockbite\Next;

class NextAcf
{
    private $postId;

    public function __construct() {}

    public function getAcfFields($block)
    {
        if (!function_exists('get_fields')) {
            return [];
        }

        if (empty($block['attrs']['data']) || !is_array($block['attrs']['data'])) {
            return [];
        }

        $acfFields = $block['attrs']['data'];

        if (empty($acfFields)) {
            return [];
        }

        return $this->processAcfFields($acfFields);
    }

    private function processAcfFields($acfFields)
    {
        $fields = $this->getBlockFields($acfFields);
        $fields = $this->cleanUpSubFields($fields);

        return $fields;
    }

    private function getBlockFields($acfFields)
    {
        $fields = [];

        foreach ($acfFields as $key => $value) {
            if (!is_array($value) && str_contains($value, 'field_')) {
                $field = get_field_object($value);
                $name = $field['name'] ?? null;
                $type = $field['type'] ?? null;
                $data = $acfFields[$name] ?? null;
                $subfields = [];

                if (isset($field['sub_fields']) && !empty($field['sub_fields'])) {
                    foreach ($field['sub_fields'] as $subField) {
                        $subfields[$subField['key']] = $subField;
                    }
                }

                $subFieldData = [];
                if ($type === 'repeater') {
                    $subFieldData = $this->processRepeaterField($acfFields, $name, $subfields, $data);
                }

                $fields[$name] = [
                    'field' => $value,
                    'name' => $name,
                    'type' => $type,
                    'data' => empty($subFieldData) ? $this->processField($type, $data) : $subFieldData,
                    'sub_fields' => $subfields,
                ];
            }
        }

        return $fields;
    }



    /*
        * Process repeater fields recursively
        *
        * @param array $acfFields
        * @param string $fieldName
        * @param array $subfields
        * @param int $rowCount
        * @return array
        */


    private function processRepeaterField($acfFields, $fieldName, $subfields, $rowCount)
    {
        $repeaterData = [];

        for ($i = 0; $i < $rowCount; $i++) {
            $rowData = [];

            foreach ($subfields as $subFieldKey => $subField) {
                $subFieldName = $subField['name'];
                $subFieldDataKey = "{$fieldName}_{$i}_{$subFieldName}";
                $subFieldData = $acfFields[$subFieldDataKey] ?? null;

                if ($subField['type'] === 'repeater') {
                    $subFieldData = $this->processRepeaterField(
                        $acfFields,
                        $subFieldDataKey,
                        $subField['sub_fields'],
                        $subFieldData
                    );
                } else {
                    $subFieldData = $this->processField($subField['type'], $subFieldData);
                }
                // Object format so you can use eg row.title directly
                $rowData[$subFieldName] = $subFieldData;
            }

            $repeaterData[] = $rowData;
        }

        return $repeaterData;
    }


    public function processField($type, $value)
    {
        if ($type) {
            switch ($type) {
                case 'image':
                    return $this->processImageField($value);
                case 'gallery':
                    return $this->processGalleryField($value);
                case 'file':
                    return $this->processFileField($value);
                default:
                    return $value;
            }
        }
    }


    /*
        * Process image fields
        *
        * @param int|string $imageId
        * @return array|string
        */
    public static function processImageField($imageId)
    {
        if (is_numeric($imageId)) {
            $imageUrl = wp_get_attachment_image_url($imageId, 'full');
            $metadata = wp_get_attachment_metadata($imageId);
            $uploadDir = wp_upload_dir();
            $baseUrl = $uploadDir['baseurl'];

            $sizes = [];
            if (!empty($metadata['sizes'])) {
                foreach ($metadata['sizes'] as $size => $info) {
                    $sizes[$size] = [
                        'url' => $baseUrl . '/' . dirname($metadata['file']) . '/' . $info['file'],
                        'width' => $info['width'],
                        'height' => $info['height'],
                        'mime-type' => $info['mime-type'] ?? null,
                    ];
                }
            }
            return [
                'id' => $imageId,
                'url' => $imageUrl,
                'alt' => get_post_meta($imageId, '_wp_attachment_image_alt', true),
                'description' => wp_get_attachment_metadata($imageId)['image_meta']['caption'] ?? '',
                'caption' => wp_get_attachment_caption($imageId),
                'sizes' => $sizes,
            ];
        }
        return $imageId;
    }


    private function processGalleryField($galleryIds)
    {
        $gallery = [];
        if (!is_array($galleryIds) || empty($galleryIds)) {
            return $gallery;
        }
        foreach ($galleryIds as $imageId) {
            $imageUrl = wp_get_attachment_image_url($imageId, 'full');
            $gallery[] = [
                'id' => $imageId,
                'url' => $imageUrl,
            ];
        }
        return $gallery;
    }

    private function processFileField($fileId)
    {
        if (is_numeric($fileId)) {
            $fileUrl = wp_get_attachment_url($fileId);
            return [
                'id' => $fileId,
                'url' => $fileUrl,
            ];
        }
        return $fileId;
    }


    private function cleanUpSubFields($fields)
    {
        foreach ($fields as $key => $field) {
            unset($fields[$key]['sub_fields']);
        }
        return $fields;
    }
}
