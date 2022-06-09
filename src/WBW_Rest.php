<?php

namespace wbw;

class WBW_Rest
{

    public function __construct($stub = null)
    {
        // our rest namespace
        $this->wbw_rest_namespace = 'wbw/v1';
        $this->wbw_submissions_rest_base = 'submissions';
        $this->wbw_service_bodies_rest_base = 'servicebodies';
        $this->wbw_bmltserver_rest_base = 'bmltserver';
        $this->wbw_options_rest_base = 'options';
    }
}
