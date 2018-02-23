<?php

if (!function_exists('isAssocArray'))
{
    function isAssocArray($arr)
    {
        if (array() === $arr || !is_array($arr)) 
            return false;
            
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}

if (!function_exists('action_exists'))
{
    function action_exists($action) 
    {
        try {
            action($action);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}