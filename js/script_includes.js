/* eslint-disable no-unused-vars */
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

/* global jQuery, __ */

function bmltwf_clearFunction(functionName) {
  // IE8 throws an exception when you try to delete a property on window
  // http://stackoverflow.com/a/1824228/751089
  try {
    delete window[functionName];
  } catch (e) {
    window[functionName] = undefined;
  }
}

function bmltwf_removeScript(scriptId) {
  const script = document.getElementById(scriptId);
  if (script) {
    document.getElementsByTagName('head')[0].removeChild(script);
  }
}

// eslint-disable-next-line no-unused-vars
function bmltwf_notice_success(response, notice_class) {
  let msg = '';
  if (response.message === '') { msg = `<div class="notice notice-success is-dismissible"><p><strong>${__('SUCCESS', 'bmlt-workflow')}: </strong><button type="button" class="notice-dismiss" onclick="javascript: return bmltwf_dismiss_notice(this);"></button></div>`; } else {
    msg = `<div class="notice notice-success is-dismissible"><p id="bmltwf_error_class_${notice_class}"><strong>${__('SUCCESS', 'bmlt-workflow')}: </strong>${
      response.message
    }.</p><button type="button" class="notice-dismiss" onclick="javascript: return bmltwf_dismiss_notice(this);"></button></div>`;
  }
  jQuery(`.${notice_class}`).after(msg);
}

function bmltwf_notice_error(xhr, notice_class) {
  if (typeof xhr === 'string') {
    jQuery(`.${notice_class}`).after(
      `<div class="notice notice-error is-dismissible"><p id="bmltwf_error_class_${notice_class}"><strong>${__('ERROR', 'bmlt-workflow')}: </strong>${
        xhr
      }.</p><button type="button" class="notice-dismiss" onclick="javascript: return bmltwf_dismiss_notice(this);"></button></div>`,
    );
  } else {
    jQuery(`.${notice_class}`).after(
      `<div class="notice notice-error is-dismissible"><p id="bmltwf_error_class_${notice_class}"><strong>${__('ERROR', 'bmlt-workflow')}: </strong>${
        xhr.responseJSON.message
      }.</p><button type="button" class="notice-dismiss" onclick="javascript: return bmltwf_dismiss_notice(this);"></button></div>`,
    );
  }
}

function bmltwf_dismiss_notice(element) {
  jQuery(element)
    .parent()
    .slideUp('normal', function () {
      jQuery(this).remove();
    });
  return false;
}

function bmltwf_clear_notices() {
  jQuery('.notice-dismiss').each(function (i, e) {
    bmltwf_dismiss_notice(e);
  });
}

function bmltwf_turn_off_spinner(element) {
  jQuery(element).removeClass('is-active');
}

function bmltwf_turn_on_spinner(element) {
  jQuery(element).addClass('is-active');
}
