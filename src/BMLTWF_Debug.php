<?php
// Copyright (C) 2022 nigel.bmlt@gmail.com
// 
// This file is part of bmlt-workflow.
// 
// bmlt-workflow is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// 
// bmlt-workflow is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with bmlt-workflow.  If not, see <http://www.gnu.org/licenses/>.

namespace bmltwf;

trait BMLTWF_Debug 
{
    public function debug_log($message)
    {
        if (BMLTWF_DEBUG)
        {
            $out = print_r($message, true);
            error_log(debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] . ": " . $out);
        }
    }

    public function debug_bmlt_payload($url=null,$method=null,$body=null)
    {
        if (BMLTWF_DEBUG)
        {
            $out = print_r($url, true);
            error_log(debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] . ": BMLT Payload : URL - " . $out);
            $out = print_r($method?$method:'GET', true);
            error_log(debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] . ": BMLT Payload : Method - " . $out);
            if(!is_string($body))
            {
                $body = json_encode($body);
            }
            $out = print_r($body?$body:'(null)', true);
            error_log(debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] . ": BMLT Payload : Body - " . $out);
        }
    }

}
