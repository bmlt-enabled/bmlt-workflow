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

/* eslint no-undef: "error" */

/* global wp, jQuery, ClipboardJS */
/* global bmltwf_clear_notices, bmltwf_turn_on_spinner, bmltwf_turn_off_spinner, bmltwf_notice_success, bmltwf_notice_error */
/* global bmltwf_admin_restore_rest_url, bmltwf_admin_backup_rest_url, bmltwf_admin_bmltserver_rest_url, bmltwf_fso_feature */
/* global bmltwf_bmlt_server_address, bmltwf_google_maps_key_select, bmltwf_admin_bmltwf_service_bodies_rest_url */

const { __ } = wp.i18n;

jQuery(document).ready(function ($) {
  // clipboard
  // eslint-disable-next-line no-unused-vars
  const clip = new ClipboardJS('.clipboard-button');

  // form submit handler to massage form content before sending

  $('#bmltwf_options_form').submit(function () {
    $('input[type=hidden]').each(function (i, el) {
      if (el.name.startsWith('bmltwf_optional')) {
        const hidden = !$(`input[name="${el.name}_visible_checkbox"]`)[0].checked;
        const required = $(`input[name="${el.name}_required_checkbox"]`)[0].checked;
        if (hidden) {
          $(el).val('hidden');
        } else if (required) {
          $(el).val('displayrequired');
        } else {
          $(el).val('display');
        }
      } else if (el.name.startsWith('bmltwf_required')) {
        const required = $(`input[name="${el.name}_required_checkbox"]`)[0].checked;
        if (required) {
          $(el).val('true');
        } else {
          $(el).val('false');
        }
      }
    });
  });

  // click and display handler for fso options
  if (bmltwf_fso_feature === 'hidden') {
    $('#fso_options').hide();
  } else {
    $('#fso_options').show();
  }

  // hide handler for optional fields
  $('.bmltwf_optional_visible_checkbox').on('change', function () {
    const disableclass = `.${this.id.slice(0, -'_visible_checkbox'.length)}_disable`;
    if (this.checked) {
      $(disableclass).prop('disabled', false);
    } else {
      $(disableclass).prop('disabled', true);
    }
  });

  // show/hide our google maps key input box
  $('#bmltwf_google_maps_key_select').on('change', function () {
    if (this.value === 'bmlt_key') {
      $('#bmltwf_google_maps_key').hide();
      $('#bmltwf_google_maps_key').val('');
    } else {
      $('#bmltwf_google_maps_key').show();
    }
  });

  $('#bmltwf_google_maps_key_select').val(bmltwf_google_maps_key_select);
  $('#bmltwf_google_maps_key_select').trigger('change');

  $('#bmltwf_fso_feature').on('change', function () {
    if (this.value === 'hidden') {
      $('#fso_options').hide();
    } else {
      $('#fso_options').show();
    }
  });

  // click handler for hidden file browser button
  $('#bmltwf_restore').on('click', function () {
    $('#bmltwf_file_selector').trigger('click');
  });

  // perform a restore
  $('#bmltwf_file_selector').on('change', function () {
    bmltwf_clear_notices();
    $('#bmltwf_restore_warning_dialog').dialog('open');
  });

  // restore hook
  const restore_fr = new FileReader();
  restore_fr.onload = function (e) {
    $.ajax({
      url: bmltwf_admin_restore_rest_url,
      method: 'POST',
      contentType: 'application/json; charset=utf-8',
      dataType: 'json',
      data: e.target.result,
      processData: false,
      beforeSend(xhr) {
        bmltwf_turn_on_spinner('#bmltwf-backup-spinner');
        bmltwf_clear_notices();
        xhr.setRequestHeader('X-WP-Nonce', $('#_wprestnonce').val());
      },
    })
      .done(function (response) {
        bmltwf_turn_off_spinner('#bmltwf-backup-spinner');
        bmltwf_notice_success(response, 'bmltwf-error-message');
      })
      .fail(function (xhr) {
        bmltwf_notice_error(xhr, 'bmltwf-error-message');
        bmltwf_turn_off_spinner('#bmltwf-backup-spinner');
      });
  };

  $('#bmltwf_restore_warning_dialog').dialog({
    title: 'Clear plugin warning',
    autoOpen: false,
    draggable: false,
    width: 'auto',
    maxWidth: 'auto',
    modal: true,
    resizable: false,
    closeOnEscape: true,
    position: {
      my: 'center',
      at: 'center',
      of: window,
    },
    buttons: {
      Ok() {
        // trigger the restore
        restore_fr.readAsText($('#bmltwf_file_selector')[0].files[0]);
        $(this).dialog('close');
      },
      Cancel() {
        $(this).dialog('close');
      },
    },
    open() {
      const $this = $(this);
      // close dialog by clicking the overlay behind it
      $('.ui-widget-overlay').on('click', function () {
        $this.dialog('close');
      });
    },
    create() {
      $('.ui-dialog-titlebar-close').addClass('ui-button');
    },
  });

  function hide_bmlt_validation() {
    $('#bmltwf_bmlt_server_address_test_yes').hide();
    $('#bmltwf_bmlt_login_test_yes').hide();
    $('#bmltwf_bmlt_server_address_test_no').hide();
    $('#bmltwf_bmlt_login_test_no').hide();
  }

  // click handler for bmlt configuration popup
  $('#bmltwf_configure_bmlt_server').on('click', function () {
    bmltwf_clear_notices();

    hide_bmlt_validation();

    $('#bmltwf_bmlt_configuration_dialog').dialog('open');
  });

  function get_test_status() {
    return new Promise((resolve) => {
      $.ajax({
        url: bmltwf_admin_bmltserver_rest_url,
        type: 'GET',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend(xhr) {
          // bmltwf_clear_notices();
          xhr.setRequestHeader('X-WP-Nonce', $('#_wprestnonce').val());
        },
      })
        .done(function (response) {
          resolve(response);
        })
        .fail(function (xhr) {
          resolve(xhr);
        });
    });
  }

  function update_from_test_result(data) {
    if (data.bmltwf_bmlt_test_status === 'success') {
      $('#bmltwf_bmlt_test_yes').show();
      $('#bmltwf_bmlt_test_no').hide();
    } else {
      $('#bmltwf_bmlt_test_no').show();
      $('#bmltwf_bmlt_test_yes').hide();
    }
    if (data.bmltwf_bmlt_server_version) {
      $('#bmltwf_server_version_yes').show();
      $('#bmltwf_server_version_no').hide();
      $('#bmltwf_server_version_yes').html(`<span class="dashicons dashicons-yes-alt" style="color: cornflowerblue;"></span>${__('BMLT Root Server Version', 'bmlt-workflow')} ${data.bmltwf_bmlt_server_version}`);
    } else {
      $('#bmltwf_server_version_no').show();
      $('#bmltwf_server_version_yes').hide();
    }
  }

  // update the test status
  get_test_status().then((data) => {
    update_from_test_result(data);
  });

  // click handler for backup
  $('#bmltwf_backup').on('click', function () {
    $.ajax({
      url: bmltwf_admin_backup_rest_url,
      method: 'POST',
      contentType: 'application/json; charset=utf-8',
      dataType: 'json',
      processData: false,
      beforeSend(xhr) {
        bmltwf_turn_on_spinner('#bmltwf-backup-spinner');
        bmltwf_clear_notices();
        xhr.setRequestHeader('X-WP-Nonce', $('#_wprestnonce').val());
      },
    })
      .done(function (response) {
        bmltwf_turn_off_spinner('#bmltwf-backup-spinner');
        bmltwf_notice_success(response, 'bmltwf-error-message');
        const blob = new Blob([response.backup], { type: 'application/json' });
        const link = document.createElement('a');
        const b_elem = document.getElementById('bmltwf_backup_filename');
        if (b_elem != null) {
          b_elem.parentNode.removeChild(b_elem);
        }
        link.setAttribute('id', 'bmltwf_backup_filename');
        link.href = window.URL.createObjectURL(blob);
        const d = new Date();
        const datetime = d.getFullYear().toString()
          + (`0${(d.getMonth() + 1).toString()}`).slice(-2)
          + (`0${d.getDate().toString()}`).slice(-2)
          + (`0${d.getHours().toString()}`).slice(-2)
          + (`0${d.getMinutes().toString()}`).slice(-2);
        link.download = `backup-${datetime}.json`;
        // stick it in the dom so we can find it later
        document.getElementById('bmltwf_file_selector').appendChild(link);
        link.click();
      })
      .fail(function (xhr) {
        bmltwf_notice_error(xhr, 'bmltwf-error-message');
        bmltwf_turn_off_spinner('#bmltwf-backup-spinner');
      });
  });

  function wipe_service_bodies(parameters) {
    $.ajax({
      url: bmltwf_admin_bmltwf_service_bodies_rest_url,
      type: 'DELETE',
      dataType: 'json',
      data: JSON.stringify(parameters),
      contentType: 'application/json',
      beforeSend(xhr) {
        xhr.setRequestHeader('X-WP-Nonce', $('#_wprestnonce').val());
      },
    });
  }

  function save_results() {
    const parameters = {};
    parameters.bmltwf_bmlt_server_address = $('#bmltwf_bmlt_server_address').val();
    parameters.bmltwf_bmlt_username = $('#bmltwf_bmlt_username').val();
    parameters.bmltwf_bmlt_password = $('#bmltwf_bmlt_password').val();

    $.ajax({
      url: bmltwf_admin_bmltserver_rest_url,
      type: 'PATCH',
      dataType: 'json',
      contentType: 'application/json',
      data: JSON.stringify(parameters),
      beforeSend(xhr) {
        bmltwf_clear_notices();
        xhr.setRequestHeader('X-WP-Nonce', $('#_wprestnonce').val());
      },
    })
      .done(function (response) {
        bmltwf_notice_success(response, 'bmltwf-error-message');
        update_from_test_result(response);
      })
      .fail(function (xhr) {
        bmltwf_notice_error(xhr, 'bmltwf-error-message');
        update_from_test_result(xhr.responseJSON.data);
      });
  }

  function test_configuration() {
    const parameters = {};
    parameters.bmltwf_bmlt_server_address = $('#bmltwf_bmlt_server_address').val();
    parameters.bmltwf_bmlt_username = $('#bmltwf_bmlt_username').val();
    parameters.bmltwf_bmlt_password = $('#bmltwf_bmlt_password').val();

    return new Promise((resolve, reject) => {
      $.ajax({
        url: bmltwf_admin_bmltserver_rest_url,
        type: 'POST',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify(parameters),
        beforeSend(xhr) {
          bmltwf_clear_notices();
          xhr.setRequestHeader('X-WP-Nonce', $('#_wprestnonce').val());
        },
      })
        .done(function (response) {
          bmltwf_notice_success(response, 'options_dialog_bmltwf_error_message');
          resolve();
        })
        .fail(function (xhr) {
          bmltwf_notice_error(xhr, 'options_dialog_bmltwf_error_message');
          reject();
        });
    });
  }

  function enable_save_button(enable) {
    const dialogButtons = $('#bmltwf_bmlt_configuration_dialog').dialog('option', 'buttons');

    $.each(dialogButtons, function (buttonIndex, button) {
      if (button.id === 'bmltwf_bmlt_configuration_save') {
        // eslint-disable-next-line no-param-reassign
        button.disabled = !enable;
      }
    });
    $('#bmltwf_bmlt_configuration_dialog').dialog('option', 'buttons', dialogButtons);
  }

  function test_server_configuration() {
    const parameters = {};
    parameters.bmltwf_bmlt_server_address = $('#bmltwf_bmlt_server_address').val();
    parameters.bmltwf_bmlt_username = $('#bmltwf_bmlt_username').val();
    parameters.bmltwf_bmlt_password = $('#bmltwf_bmlt_password').val();

    $.ajax({
      url: bmltwf_admin_bmltserver_rest_url,
      type: 'POST',
      dataType: 'json',
      contentType: 'application/json',
      data: JSON.stringify(parameters),
      beforeSend(xhr) {
        bmltwf_clear_notices();
        xhr.setRequestHeader('X-WP-Nonce', $('#_wprestnonce').val());
      },
    })

      .done(function (response) {
        bmltwf_notice_success(response, 'options_dialog_bmltwf_error_message');
        $('#bmltwf_bmlt_server_address_test_yes').show();
        $('#bmltwf_bmlt_login_test_yes').show();
        $('#bmltwf_bmlt_server_address_test_no').hide();
        $('#bmltwf_bmlt_login_test_no').hide();
        enable_save_button(true);
      })

      .fail(function (xhr) {
        if (xhr.responseJSON.data.bmltwf_bmlt_server_status === 'true') {
          $('#bmltwf_bmlt_server_address_test_yes').show();
          $('#bmltwf_bmlt_server_address_test_no').hide();
        } else {
          $('#bmltwf_bmlt_server_address_test_yes').hide();
          $('#bmltwf_bmlt_server_address_test_no').show();
        }
        if (xhr.responseJSON.data.bmltwf_bmlt_login_status !== 'unknown') {
          if (xhr.responseJSON.data.bmltwf_bmlt_login_status === 'true') {
            $('#bmltwf_bmlt_login_test_yes').show();
            $('#bmltwf_bmlt_login_test_no').hide();
          } else {
            $('#bmltwf_bmlt_login_test_yes').hide();
            $('#bmltwf_bmlt_login_test_no').show();
          }
        } else {
          $('#bmltwf_bmlt_login_test_yes').hide();
          $('#bmltwf_bmlt_login_test_no').hide();
        }
        enable_save_button(false);

        bmltwf_notice_error(xhr, 'options_dialog_bmltwf_error_message');
      });
  }

  $('#bmltwf_bmlt_change_server_warning_dialog').dialog({
    title: 'BMLT Root Server Configuration Change Warning',
    autoOpen: false,
    draggable: false,
    width: 'auto',
    maxWidth: 'auto',
    zindex: 1001,
    modal: true,
    resizable: false,
    closeOnEscape: true,
    position: {
      my: 'center',
      at: 'center',
      of: window,
    },
    buttons: {
      Ok() {
        if ($('#yesimsure').prop('checked') === true) {
          wipe_service_bodies({ checked: 'true' });
          save_results(this);
        }
        // trigger an update on the main page
        test_configuration(true);
        $(this).dialog('close');
        $('#bmltwf_bmlt_change_server_warning_dialog').data('parent').dialog('close');
      },
      Cancel() {
        $(this).dialog('close');
      },
    },
    open() {
      const $this = $(this);
      // close dialog by clicking the overlay behind it
      $('.ui-widget-overlay').on('click', function () {
        $this.dialog('close');
      });
    },
    create() {
      $('.ui-dialog-titlebar-close').addClass('ui-button');
    },
  });

  $('#bmltwf_bmlt_configuration_dialog').dialog({
    title: 'BMLT Root Server Configuration',
    autoOpen: false,
    draggable: false,
    width: 'auto',
    maxWidth: 'auto',
    modal: true,
    resizable: false,
    closeOnEscape: true,
    position: {
      my: 'center',
      at: 'center',
      of: window,
    },
    buttons:
      [
        {
          id: 'bmltwf_bmlt_configuration_test',
          text: 'Test Configuration',
          click() {
            test_server_configuration();
          },
        },
        {
          id: 'bmltwf_bmlt_configuration_save',
          text: 'Save Configuration',
          disabled: true,
          click() {
            // check if server address changed
            if (bmltwf_bmlt_server_address !== '' && bmltwf_bmlt_server_address !== $('#bmltwf_bmlt_server_address').val()) {
              $('#bmltwf_bmlt_change_server_warning_dialog').data('parent', $(this)).dialog('open');
            } else {
              save_results();
              // trigger an update on the main page
              test_configuration(true);
              $(this).dialog('close');
            }
          },
        },
        {
          id: 'bmltwf_bmlt_configuration_cancel',
          text: 'Cancel',
          click() {
            $(this).dialog('close');
          },
        },
      ],

    open() {
      const $this = $(this);
      // close dialog by clicking the overlay behind it
      $('.ui-widget-overlay').on('click', function () {
        $this.dialog('close');
      });
    },
    create() {
      $('.ui-dialog-titlebar-close').addClass('ui-button');
    },
  });

  $('#bmltwf_bmlt_server_address').on('change', function () {
    enable_save_button(false);
    hide_bmlt_validation();
  });

  $('#bmltwf_bmlt_username').on('change', function () {
    enable_save_button(false);
    hide_bmlt_validation();
  });

  $('#bmltwf_bmlt_password').on('change', function () {
    enable_save_button(false);
    hide_bmlt_validation();
  });
});
