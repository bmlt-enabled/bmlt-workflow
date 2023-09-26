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

/* global wp, jQuery */
/* global bmltwf_clear_notices, bmltwf_turn_on_spinner, bmltwf_turn_off_spinner, bmltwf_notice_success, bmltwf_notice_error */
/* global bmltwf_admin_bmltwf_service_bodies_rest_url */
/* global wp_users_url */

const { __ } = wp.i18n;

jQuery(document).ready(function ($) {
  function attach_select_options_for_sbid(sblist, userlist, sbid, selectid) {
    Object.keys(userlist).forEach((item) => {
      const wp_uid = userlist[item].id;
      const username = userlist[item].slug;
      const { membership } = sblist[sbid];
      let selected = false;
      if (membership.includes(wp_uid)) {
        selected = true;
      }
      const opt = new Option(username, wp_uid, false, selected);
      $(selectid).append(opt);
      // console.log(opt);
    });
    $(selectid).trigger('change');
  }

  function create_service_area_permission_post() {
    const ret = {};
    $('.bmltwf-userlist').each(function () {
      // console.log("got real id " + $(this).data("id"));
      const id = $(this).data('id');
      // console.log("got name " + $(this).data("name"));
      const sbname = $(this).data('name');
      // console.log("select vals = "+ $(this).val());
      const membership = $(this).val();
      // console.log("got show_on_form = "+ $(this).data("show_on_form"));
      const show_on_form = $(this).data('show_on_form');

      ret[id] = { name: sbname, show_on_form, membership };
    });
    return ret;
  }

  $('#bmltwf_submit').on('click', function () {
    $('#bmltwf-userlist-table tbody tr').each(function () {
      const tr = $(this);
      const checked = $(tr).find('input:checkbox').prop('checked');
      const select = $(tr).find('select');
      select.data('show_on_form', checked);
    });
    const post = create_service_area_permission_post();

    bmltwf_clear_notices();

    $.ajax({
      url: bmltwf_admin_bmltwf_service_bodies_rest_url,
      method: 'POST',
      data: JSON.stringify(post),
      contentType: 'application/json; charset=utf-8',
      dataType: 'json',
      processData: false,
      beforeSend(xhr) {
        bmltwf_turn_on_spinner('#bmltwf-submit-spinner');

        xhr.setRequestHeader('X-WP-Nonce', $('#_wprestnonce').val());
      },
    })
      .done(function (response) {
        bmltwf_turn_off_spinner('#bmltwf-submit-spinner');
        bmltwf_notice_success(response, 'bmltwf-error-message');
      })
      .fail(function (xhr) {
        bmltwf_turn_off_spinner('#bmltwf-submit-spinner');
        bmltwf_notice_error(xhr, 'bmltwf-error-message');
      });
  });

  // get the permissions, and the userlist from wordpress, and create our select lists
  const parameters = { detail: 'true' };

  const respsblist = $.ajax({
    url: bmltwf_admin_bmltwf_service_bodies_rest_url,
    dataType: 'json',
    data: parameters,
    beforeSend(xhr) {
      bmltwf_turn_on_spinner('#bmltwf-form-spinner');
      xhr.setRequestHeader('X-WP-Nonce', $('#_wprestnonce').val());
    },
  })
    .done(function (response) {
      // paginate wordpress user response
      const pagesize = 10;
      const page = 1;
      let firstsep = '?';
      if (wp_users_url.includes('?')) {
        firstsep = '&';
      }

      const thisresp = $.ajax({
        url: `${wp_users_url + firstsep}per_page=${pagesize}&page=${page}`,
        dataType: 'json',
        sblist: response,
        beforeSend(xhr) {
          xhr.setRequestHeader('X-WP-Nonce', $('#_wprestnonce').val());
        },
      }).done(function () {
        const pg = thisresp.getResponseHeader('x-wp-totalpages');

        if (pg != null) {
          const totalpages = parseInt(pg, 10);
          const range = [...Array(totalpages).keys()].map((x) => x + 1);

          const users = {};
          const allAJAX = range.map((thispage) => $.ajax({
            url: `${wp_users_url + firstsep}per_page=${pagesize}&page=${thispage}`,
            dataType: 'json',
            beforeSend(xhr) {
              xhr.setRequestHeader('X-WP-Nonce', $('#_wprestnonce').val());
            },
          }).done(function (data) {
            Object.keys(data).forEach((item) => {
              users[data[item].id] = data[item];
            });
          }));

          Promise.all(allAJAX).then(function () {
            const sblist = respsblist.responseJSON;
            const userlist = users;
            Object.keys(sblist).forEach((item) => {
              const id = `bmltwf_userlist_id_${item}`;
              const cbid = `bmltwf_userlist_checkbox_id_${item}`;
              const checked = sblist[item].show_on_form ? 'checked' : '';
              let appendstr = '<tr>';

              appendstr += `<td>${sblist[item].name}</td>`;
              appendstr += `<td><div class="grow-wrap"><textarea onInput="this.parentNode.dataset.replicatedValue = this.value" disabled>${sblist[item].description}</textarea></div></td>`;
              appendstr += `<td><select class="bmltwf-userlist" id="${id}" style="width: auto"></select></td>`;
              appendstr += `<td class="bmltwf-center-checkbox"><input type="checkbox" id="${cbid}" ${checked}></td>`;
              appendstr += '</tr>';
              $('#bmltwf-userlist-table tbody').append(appendstr);
              // store metadata away for later
              $(`#${id}`).data('id', item);
              $(`#${id}`).data('name', sblist[item].name);

              $('.bmltwf-userlist').select2({
                multiple: true,
                width: '100%',
              });

              attach_select_options_for_sbid(sblist, userlist, item, `#${id}`);
            });

            // update the auto size boxes
            $('.grow-wrap textarea').trigger('input');

            // turn off spinner
            bmltwf_turn_off_spinner('#bmltwf-form-spinner');
            // turn on table
            $('#bmltwf-userlist-table').show();
            $('#bmltwf_submit').show();
          });
        } else {
          bmltwf_turn_off_spinner('#bmltwf-form-spinner');
          bmltwf_notice_error(__('Error retrieving wordpress users', 'bmlt-workflow'), 'bmltwf-error-message');
        }
      });
    })
    .fail(function (xhr) {
      bmltwf_turn_off_spinner('#bmltwf-form-spinner');
      bmltwf_notice_error(xhr, 'bmltwf-error-message');
    });
});
