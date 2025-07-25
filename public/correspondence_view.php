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

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use bmltwf\BMLTWF_Debug;

/**
 * Shortcode handler for correspondence view
 */
function bmltwf_correspondence_view_shortcode($atts)
{
    // Get thread ID from URL parameter
    $thread_id = isset($_GET['thread']) ? sanitize_text_field($_GET['thread']) : '';
    
    if (empty($thread_id)) {
        return '<div class="bmltwf-error">' . __('No correspondence thread specified.', 'bmlt-workflow') . '</div>';
    }
    
    // Output container for correspondence
    $output = '<div id="bmltwf-correspondence-container">';
    $output .= '<div id="bmltwf-correspondence-loading">' . __('Loading correspondence...', 'bmlt-workflow') . '</div>';
    $output .= '<div id="bmltwf-correspondence-error" style="display:none;"></div>';
    $output .= '<div id="bmltwf-correspondence-header" style="display:none;"></div>';
    $output .= '<div id="bmltwf-correspondence-messages" style="display:none;"></div>';
    $output .= '<div id="bmltwf-correspondence-reply" style="display:none;">';
    $output .= '<button id="bmltwf-reply-button" class="button">' . __('Reply', 'bmlt-workflow') . '</button>';
    $output .= '<div id="bmltwf-reply-form" style="display:none;">';
    $output .= '<textarea id="bmltwf-reply-text" placeholder="' . __('Your reply...', 'bmlt-workflow') . '"></textarea>';
    $output .= '<div class="bmltwf-reply-buttons">';
    $output .= '<button id="bmltwf-send-reply" class="button button-primary">' . __('Send', 'bmlt-workflow') . '</button>';
    $output .= '<button id="bmltwf-cancel-reply" class="button">' . __('Cancel', 'bmlt-workflow') . '</button>';
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';
    
    return $output;
}
add_shortcode('bmltwf-correspondence-view', 'bmltwf_correspondence_view_shortcode');