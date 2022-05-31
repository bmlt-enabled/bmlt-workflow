<?php 
namespace wbw\REST\Handlers;

use wbw\REST\HandlerCore;

class OptionsHandler
{

    public function __construct()
    {
        $this->handlerCore = new HandlerCore;
    }

    public function post_wbw_backup_handler($request)
    {
        global $wbw_dbg;

        $wbw_dbg->debug_log("backup handler called");
        return $this->handlerCore->wbw_rest_success('Backup completed.');

    }
}