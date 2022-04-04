<?php

namespace wbw;

class Debug 
{
    function debug_log($message)
    {
        if (WBW_DEBUG)
        {
            error_log($message);
        }
    }
    
    function vdump($object)
    {
        ob_start();
        var_dump($object);
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }    
}
