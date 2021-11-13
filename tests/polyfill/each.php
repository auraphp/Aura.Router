<?php

// Source: https://gist.github.com/k-gun/30dd2bf8b22329a2dbc11a045aed3859
if (! function_exists('each')) {
    function each(array &$array) {
        $value = current($array);
        $key = key($array);

        if (is_null($key)) {
            return false;
        }

        // Move pointer.
        next($array);

        return array(1 => $value, 'value' => $value, 0 => $key, 'key' => $key);
    }
}
