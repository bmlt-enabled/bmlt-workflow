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
