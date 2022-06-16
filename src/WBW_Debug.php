<?php

namespace wbw;

trait WBW_Debug 
{
    public function debug_log($message)
    {
        if (WBW_DEBUG)
        {
            $out = print_r($message, true);
            error_log(debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] . ": " . $out);
        }
    }
    
}
