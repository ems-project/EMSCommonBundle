<?php

namespace EMS\CommonBundle\Helper;



class ArrayTool
{

    /**
     * Normalize an array in order to compute it's hash
     *
     * @param array $array
     * @param int $sort_flags
     * @return bool
     */
    public static function normalizeArray(array &$array, int $sort_flags = SORT_REGULAR)
    {
        if (!is_array($array)) {
            trigger_error("Incorrect parameters, arrays expected", E_USER_ERROR);
            return false;
        }

        $out = true;
        ksort($array, $sort_flags);

        foreach ($array as $index => &$arr) {
            if( is_array($arr) && ! ArrayTool::normalizeArray($arr, $sort_flags)){
                $out = false;
            }

            if(is_array($array[$index]) && empty($array[$index])) {
                unset($array[$index]);
            }
        }
        return $out;
    }

    /**
     * Normalize and json encode an array in order to compute it's hash
     * @param array $array
     * @param int $sort_flags
     * @param int $jsonEncodeOptions
     * @return false|string
     */
    public static function normalizeAndSerializeArray (array &$array, int $sort_flags = SORT_REGULAR, int $jsonEncodeOptions = 0)
    {
        ArrayTool::normalizeArray($array, $sort_flags);
        return json_encode($array, $jsonEncodeOptions);
    }


}