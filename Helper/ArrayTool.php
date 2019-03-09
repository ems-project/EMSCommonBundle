<?php
/**
 * Created by PhpStorm.
 * User: theus
 * Date: 09/03/2019
 * Time: 11:34
 */

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


}