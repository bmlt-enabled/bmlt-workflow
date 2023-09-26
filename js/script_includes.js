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

const bmltwf_defaultOptions = {
  timeout: 300000,
  jsonpCallback: 'callback',
  jsonpCallbackFunction: null,
};

function bmltwf_generateCallbackFunction() {
  return `jsonp_${Date.now().toString()}_${Math.ceil(Math.random() * 100000).toString()}`;
}

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
function bmltwf_fetchJsonp(_url, options) {
  if (!options) {
    // eslint-disable-next-line no-param-reassign
    options = {};
  }
  // to avoid param reassign
  let url = _url;
  const timeout = options.timeout || bmltwf_defaultOptions.timeout;
  const jsonpCallback = options.jsonpCallback || bmltwf_defaultOptions.jsonpCallback;

  let timeoutId;

  return new Promise(function (resolve, reject) {
    const callbackFunction = options.jsonpCallbackFunction || bmltwf_generateCallbackFunction();
    const scriptId = `${jsonpCallback}_${callbackFunction}`;

    window[callbackFunction] = function (response) {
      resolve({
        ok: true,
        // keep consistent with fetch API
        json() {
          return Promise.resolve(response);
        },
      });

      if (timeoutId) clearTimeout(timeoutId);

      bmltwf_removeScript(scriptId);

      bmltwf_clearFunction(callbackFunction);
    };

    // Check if the user set their own params, and if not add a ? to start a list of params
    url += url.indexOf('?') === -1 ? '?' : '&';

    const jsonpScript = document.createElement('script');
    jsonpScript.setAttribute('src', `${url + jsonpCallback}=${callbackFunction}`);
    if (options.charset) {
      jsonpScript.setAttribute('charset', options.charset);
    }
    if (options.nonce) {
      jsonpScript.setAttribute('nonce', options.nonce);
    }
    if (options.referrerPolicy) {
      jsonpScript.setAttribute('referrerPolicy', options.referrerPolicy);
    }
    jsonpScript.id = scriptId;
    document.getElementsByTagName('head')[0].appendChild(jsonpScript);

    timeoutId = setTimeout(function () {
      reject(new Error(`JSONP request to ${_url} timed out`));

      bmltwf_clearFunction(callbackFunction);
      bmltwf_removeScript(scriptId);
      window[callbackFunction] = function () {
        bmltwf_clearFunction(callbackFunction);
      };
    }, timeout);

    // Caught if got 404/500
    jsonpScript.onerror = function () {
      reject(new Error(`JSONP request to ${_url} failed`));

      bmltwf_clearFunction(callbackFunction);
      bmltwf_removeScript(scriptId);
      if (timeoutId) clearTimeout(timeoutId);
    };
  });
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
