<?php

namespace Blockbite\Next;

class NextAcf
{
    private $postId;

    public function __construct() {}

    public function getAcfBlockFields($block)
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

        return $this->processAcfBlockFields($acfFields);
    }



    public function getAcfPageFields($pageId)
    {
        if (!function_exists('get_fields')) {
            return [];
        }

        $this->postId = $pageId;

        $acfFields = get_fields($this->postId);

        if (empty($acfFields)) {
            return [];
        }

        return $this->processAcfPageFields($acfFields);
    }


    private function processAcfBlockFields($acfFields)
    {
        $fields = $this->getBlockFields($acfFields);
        $fields = $this->cleanUpSubFields($fields);

        return $fields;
    }


    public function processAcfPageFields(array $acfFields)
    {
        $processedFields = [];

        foreach ($acfFields as $key => $value) {
            $field = get_field_object($key, $this->postId);

            if (!$field || !isset($field['type'])) {
                continue;
            }

            $type = $field['type'];
            $subfields = $field['sub_fields'] ?? [];

            if ($type === 'repeater') {
                $processedFields[$key] = [
                    'name' => $key,
                    'type' => $type,
                    'data' => $this->processRepeaterPageField($value, $subfields),
                ];
            } else {
                $processedFields[$key] = [
                    'name' => $key,
                    'type' => $type,
                    'data' => $this->processField($type, $value),
                ];
            }
        }

        return $processedFields;
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
                    $subFieldData = $this->processRepeaterBlockField($acfFields, $name, $subfields, $data);
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


    private function processRepeaterPageField(array $rows, array $subfields)
    {
        $processed = [];

        foreach ($rows as $row) {
            $processedRow = [];

            foreach ($subfields as $subField) {
                $name = $subField['name'];
                $type = $subField['type'];
                $value = $row[$name] ?? null;

                if ($type === 'repeater' && is_array($value)) {
                    $processedRow[$name] = $this->processRepeaterPageField($value, $subField['sub_fields'] ?? []);
                } else {
                    $processedRow[$name] = $this->processField($type, $value);
                }
            }

            $processed[] = $processedRow;
        }

        return $processed;
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


    private function processRepeaterBlockField($acfFields, $fieldName, $subfields, $rowCount)
    {
        $repeaterData = [];

        for ($i = 0; $i < $rowCount; $i++) {
            $rowData = [];

            foreach ($subfields as $subFieldKey => $subField) {
                $subFieldName = $subField['name'];
                $subFieldDataKey = "{$fieldName}_{$i}_{$subFieldName}";
                $subFieldData = $acfFields[$subFieldDataKey] ?? null;

                if ($subField['type'] === 'repeater') {
                    $subFieldData = $this->processRepeaterBlockField(
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
