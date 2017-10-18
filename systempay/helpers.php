<?php

/**
 * Filter the array using the given Closure.
 *
 * @param         $array
 * @param Closure $callback
 *
 * @return array
 */
if ( ! function_exists('array_where')) {
    function array_where($array, $callback)
    {
        $filtered = [];
        foreach ($array as $key => $value) {
            if (call_user_func($callback, $key, $value)) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }
}