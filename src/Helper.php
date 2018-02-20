<?php

function isAssocArray($arr)
{
    if (array() === $arr || !is_array($arr)) 
        return false;
        
    return array_keys($arr) !== range(0, count($arr) - 1);
}