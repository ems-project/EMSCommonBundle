<?php

namespace EMS\CommonBundle\Helper;

class ArrayTool
{
    /**
     * Normalize and json encode an array in order to compute it's hash.
     *
     * @return false|string
     */
    public static function normalizeAndSerializeArray(array &$array, int $sort_flags = SORT_REGULAR, int $jsonEncodeOptions = 0)
    {
        ArrayTool::normalizeArray($array, $sort_flags);

        return json_encode($array, $jsonEncodeOptions);
    }

    /**
     * Normalize an array in order to compute it's hash.
     */
    public static function normalizeArray(array &$array, int $sort_flags = SORT_REGULAR)
    {
        ksort($array, $sort_flags);

        foreach ($array as $index => &$arr) {
            if (is_array($arr)) {
                ArrayTool::normalizeArray($arr, $sort_flags);
            }

            if (is_array($array[$index]) && empty($array[$index])) {
                unset($array[$index]);
            }
        }
    }
}
