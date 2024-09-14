/* eslint-disable prefer-destructuring */
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

/* global wp, jQuery, google */
/* global bmltwf_clear_notices, bmltwf_notice_success, bmltwf_notice_error, bmltwf_fetchJsonp, bmltwf_is_v3_server */
/* global bmltwf_gmaps_key, bmltwf_auto_geocoding_enabled, bmltwf_optional_location_nation, bmltwf_optional_location_sub_province, bmltwf_optional_location_province */
/* global bmltwf_do_states_and_provinces, bmltwf_counties_and_sub_provinces, bmltwf_remove_virtual_meeting_details_on_venue_change, bmltwf_bmlt_server_address */
/* global bmltwf_default_closed_meetings, bmltwf_bmlt_formats, bmltwf_datatables_delete_enabled, bmltwf_admin_submissions_rest_url, bmltwf_admin_bmltwf_service_bodies */
/* global bmltwf_optional_location_province_displayname, bmltwf_optional_location_sub_province_displayname, bmltwf_optional_location_nation_displayname */
/* global bmltwf_bmltserver_geolocate_rest_url, bmltwf_optional_postcode, bmltwf_zip_auto_geocoding, bmltwf_county_auto_geocoding */

const { __ } = wp.i18n;

function initMap(origlat = null, origlng = null) {
  let lat;
  let lng;

  // Show datatable once maps is loaded
  jQuery('.dt-container').show();

  if (origlat && origlng) {
    if (typeof origlat === 'string') {
      lat = parseFloat(origlat);
    } else {
      lat = origlat;
    }

    if (typeof origlng === 'string') {
      lng = parseFloat(origlng);
    } else {
      lng = origlng;
    }

    const mapOptions = {
      zoom: 16,
      center: { lat, lng },
      mapId: 'DEMO_MAP_ID',
    };

    const map = new google.maps.Map(document.getElementById('bmltwf_quickedit_map'), mapOptions);

    const marker = new google.maps.marker.AdvancedMarkerElement({
      position: { lat, lng },
      map,
      gmpDraggable: true,
      title: 'Meeting Location',
    });

    const infowindow = new google.maps.InfoWindow({
      content: `Marker Location: ${marker.position.lat}, ${marker.position.lng}`,
    });

    google.maps.event.addListener(marker, 'click', () => {
      infowindow.open(map, marker);
    });

    marker.addListener('dragend', () => {
      jQuery('#quickedit_latitude').val(marker.position.lat);
      jQuery('#quickedit_longitude').val(marker.position.lng);
      infowindow.close();
      infowindow.setContent(`Marker Location: ${marker.position.lat}, ${marker.position.lng}`);
      infowindow.open(marker.map, marker);
    });
  }
}

function mysql2localdate(data) {
  const t = data.split(/[- :]/);
  const d = new Date(Date.UTC(t[0], t[1] - 1, t[2], t[3], t[4], t[5]));
  const ds = `${d.getFullYear()}-${`0${d.getMonth() + 1}`.slice(-2)}-${`0${d.getDate()}`.slice(-2)} ${`0${d.getHours()}`.slice(-2)}:${`0${d.getMinutes()}`.slice(-2)}`;
  return ds;
}

const venue_types = {
  1: __('Face to face', 'bmlt-workflow'),
  2: __('Virtual Meeting', 'bmlt-workflow'),
  3: __('Hybrid Meeting', 'bmlt-workflow'),
  4: __('Temporarily Virtual', 'bmlt-workflow'),
};

jQuery(document).ready(function ($) {
  let bmltwf_changedata = {};

  const weekdays = [
    __('Error', 'bmlt-workflow'),
    __('Sunday', 'bmlt-workflow'),
    __('Monday', 'bmlt-workflow'),
    __('Tuesday', 'bmlt-workflow'),
    __('Wednesday', 'bmlt-workflow'),
    __('Thursday', 'bmlt-workflow'),
    __('Friday', 'bmlt-workflow'),
    __('Saturday', 'bmlt-workflow'),
  ];

  $.getScript(`https://maps.googleapis.com/maps/api/js?key=${bmltwf_gmaps_key}&loading=async&libraries=marker&callback=initMap&v=weekly&async=2`);

  if (!bmltwf_is_v3_server) {
    bmltwf_notice_error(__('Support for BMLT Server version 2.x will end soon. Please upgrade your BMLT Server version to 3.x or above.'), 'bmltwf-error-message');
  }

  if (!bmltwf_auto_geocoding_enabled) {
    $('#optional_auto_geocode_enabled').hide();
  } else {
    $('#optional_auto_geocode_enabled').show();
  }

  // hide / show / required our optional fields
  switch (bmltwf_optional_location_nation) {
    case 'hidden':
    case '':
      $('#optional_location_nation').hide();
      break;
    case 'display':
      $('#optional_location_nation').show();
      break;
    case 'displayrequired':
      $('#optional_location_nation').show();
      $('#location_nation_label').append('<span class="bmltwf-required-field"> *</span>');
      break;
    default:
      break;
  }

  switch (bmltwf_optional_location_sub_province) {
    case 'hidden':
    case '':
      $('#optional_location_sub_province').hide();
      break;
    case 'display':
      $('#optional_location_sub_province').show();
      break;
    case 'displayrequired':
      $('#optional_location_sub_province').show();
      $('#location_sub_province_label').append('<span class="bmltwf-required-field"> *</span>');
      break;
    default:
      break;
  }

  switch (bmltwf_optional_location_province) {
    case 'hidden':
    case '':
      $('#optional_location_province').hide();
      break;
    case 'display':
      $('#optional_location_province').show();
      break;
    case 'displayrequired':
      $('#optional_location_province').show();
      $('#location_province_label').append('<span class="bmltwf-required-field"> *</span>');
      break;
    default:
      break;
  }

  switch (bmltwf_optional_postcode) {
    case 'hidden':
    case '':
      $('#optional_postcode').hide();
      break;
    case 'display':
      $('#optional_postcode').show();
      break;
    case 'displayrequired':
      $('#optional_postcode').show();
      $('#location_province_label').append('<span class="bmltwf-required-field"> *</span>');
      break;
    default:
      break;
  }

  // fill in counties and sub provinces
  if (bmltwf_counties_and_sub_provinces === false) {
    $('#optional_location_sub_province').append('<input class="quickedit-input" type="text" name="quickedit_location_sub_province" size="50" id="quickedit_location_sub_province">');
  } else {
    let appendstr = '<select class="quickedit-input" id="quickedit_location_sub_province" name="quickedit_location_sub_province">';
    bmltwf_counties_and_sub_provinces.forEach(function (item) {
      appendstr += `<option value="${item}">${item}</option>`;
    });
    appendstr += '</select>';
    $('#optional_location_sub_province').append(appendstr);
  }

  if (bmltwf_do_states_and_provinces === false) {
    $('#optional_location_province').append('<input class="quickedit-input" type="text" name="quickedit_location_province" size="50" id="quickedit_location_province">');
  } else {
    let appendstr = '<select class="quickedit-input" id="quickedit_location_province" name="quickedit_location_province">';
    bmltwf_do_states_and_provinces.forEach(function (item) {
      appendstr += `<option value="${item}">${item}</option>`;
    });
    appendstr += '</select>';
    $('#optional_location_province').append(appendstr);
  }

  function add_highlighted_changes_to_quickedit(bmltwf_requested) {
    // fill in and highlight the changes - use extend to clone
    const changes_requested = $.extend(true, {}, bmltwf_requested);

    if ('format_shared_id_list' in changes_requested) {
      changes_requested.format_shared_id_list = changes_requested.format_shared_id_list.split(',');
    }

    if ('duration_time' in changes_requested) {
      const durationarr = changes_requested.duration_time.split(':');
      // hoping we got hours, minutes and seconds here
      if (durationarr.length === 3) {
        changes_requested.duration_hours = durationarr[0];
        changes_requested.duration_minutes = durationarr[1];
        delete changes_requested.duration_time;
      }
    }

    // some special handling for deletion of fields
    // for (const key in changes_requested)
    Object.keys(changes_requested).forEach((key) => {
      switch (key) {
        case 'original_virtual_meeting_additional_info':
          if (
            (!('virtual_meeting_additional_info' in changes_requested) || changes_requested.virtual_meeting_additional_info === '')
            && bmltwf_remove_virtual_meeting_details_on_venue_change === 'true'
          ) {
            changes_requested.virtual_meeting_additional_info = __('(deleted)', 'bmlt-workflow');
          }
          break;
        case 'original_phone_meeting_number':
          if ((!('phone_meeting_number' in changes_requested) || changes_requested.phone_meeting_number === '') && bmltwf_remove_virtual_meeting_details_on_venue_change === 'true') {
            changes_requested.phone_meeting_number = __('(deleted)', 'bmlt-workflow');
          }
          break;
        case 'original_virtual_meeting_link':
          if ((!('virtual_meeting_link' in changes_requested) || changes_requested.virtual_meeting_link === '') && bmltwf_remove_virtual_meeting_details_on_venue_change === 'true') {
            changes_requested.virtual_meeting_link = __('(deleted)', 'bmlt-workflow');
          }
          break;
        default:
          break;
      }
    });

    Object.keys(changes_requested).forEach((element) => {
      if ($(`#quickedit_${element}`).length) {
        if (element === 'format_shared_id_list') {
          $('.quickedit_format_shared_id_list-select2').addClass('bmltwf-changed');
        } else {
          $(`#quickedit_${element}`).addClass('bmltwf-changed');
        }
        $(`#quickedit_${element}`).val(changes_requested[element]);
        $(`#quickedit_${element}`).trigger('change');
      }
    });
    // trigger adding of highlights when input changes
    $('.quickedit-input').on('input.bmltwf-highlight', function (event, arg) {
      if (arg !== 'growwrap') {
        $(this).addClass('bmltwf-changed');
      }
    });
    $('#quickedit_format_shared_id_list').on('change.bmltwf-highlight', function () {
      $('.quickedit_format_shared_id_list-select2').addClass('bmltwf-changed');
    });
    // stretch our grow wrap boxes
    $('.grow-wrap textarea').trigger('input', 'growwrap');
  }

  function update_gmaps(lat, long) {
    initMap(lat, long);
    $('#bmltwf_quickedit_map').show();
  }

  function populate_and_open_quickedit(id) {
    // clear quickedit

    // remove our change handler
    $('.quickedit-input').off('input.bmltwf-highlight');
    $('#quickedit_format_shared_id_list').off('change.bmltwf-highlight');
    // remove the highlighting
    $('.quickedit-input').removeClass('bmltwf-changed');
    $('.quickedit_format_shared_id_list-select2').removeClass('bmltwf-changed');

    // remove any content from the input fields
    $('.quickedit-input').val('');

    // zip and county are disabled if the option is set

    const autocompleted = ` (${__('autocompleted')})`;
    if (bmltwf_zip_auto_geocoding) {
      $('#quickedit_location_postal_code_1').prop('disabled', true);
      $('#quickedit_location_postal_code_1_label').append(autocompleted);
    }

    if (bmltwf_county_auto_geocoding) {
      $('#optional_location_sub_province').show();
      $('#quickedit_location_sub_province').prop('disabled', true);
      $('#quickedit_location_sub_province_label').append(autocompleted);
    }

    // hide map and let it be shown later if required
    $('#bmltwf_quickedit_map').hide();

    bmltwf_clear_notices();
    // fill quickedit

    // if it's a meeting change, fill from bmlt first
    if (bmltwf_changedata[id].submission_type === 'reason_change') {
      const { meeting_id } = bmltwf_changedata[id];
      const search_results_address = `${bmltwf_bmlt_server_address}client_interface/jsonp/?switcher=GetSearchResults&advanced_published=0&meeting_key=id_bigint&meeting_key_value=${meeting_id}&lang_enum=en&&recursive=1&sort_keys=start_time`;

      bmltwf_fetchJsonp(search_results_address)
        .then((response) => response.json())
        .then((data) => {
          // fill in all the bmlt stuff
          const item = data[0];
          if (!Object.keys(data).length) {
            const a = {};
            a.responseJSON = {};
            a.responseJSON.message = __('Error retrieving BMLT data - meeting possibly removed', 'bmlt-workflow');
            bmltwf_notice_error(a, 'bmltwf-error-message');
          } else {
            // split up the duration so we can use it in the select
            if ('duration_time' in item) {
              const durationarr = item.duration_time.split(':');
              // hoping we got hours, minutes and seconds here
              if (durationarr.length === 3) {
                $('#quickedit_duration_hours').val(durationarr[0]);
                $('#quickedit_duration_minutes').val(durationarr[1]);
              }
            }

            // split up the format list so we can use it in the select
            if ('format_shared_id_list' in item) {
              item.format_shared_id_list = item.format_shared_id_list.split(',');
            }

            Object.keys(item).forEach((element) => {
              if ($(`#quickedit_${element}`).length) {
                $(`#quickedit_${element}`).val(item[element]);
                $(`#quickedit_${element}`).trigger('change');
              }
            });
            add_highlighted_changes_to_quickedit(bmltwf_changedata[id].changes_requested);

            if (item.longitude && item.latitude) {
              const lat = item.latitude;
              const long = item.longitude;
              update_gmaps(lat, long);
            } else {
              $('#quickedit_gmaps').hide();
            }
          }
        });
    } else if (bmltwf_changedata[id].submission_type === 'reason_new') {
      // won't have a geolocation for a new meeting
      $('#quickedit_gmaps').hide();
      add_highlighted_changes_to_quickedit(bmltwf_changedata[id].changes_requested);
    }
    // Hide the publish to virtual na option if this isn't a virtual meeting
    if ($('#quickedit_venue_type').val() !== '2') {
      $('#optional_virtualna_published').hide();
    } else {
      $('#optional_virtualna_published').show();
    }

    $('#bmltwf_submission_quickedit_dialog').data('id', id).dialog('open');
  }

  // default close meeting radio button
  if (bmltwf_default_closed_meetings === 'delete') {
    $('#close_delete').prop('checked', true);
  } else {
    $('#close_unpublish').prop('checked', true);
  }

  const formatdata = [];

  // delete hybrid/TC etc
  Object.keys(bmltwf_bmlt_formats).forEach((key) => {
    const { key_string } = bmltwf_bmlt_formats[key];
    if (!(key_string === 'HY' || key_string === 'VM' || key_string === 'TC')) {
      formatdata.push({ text: `(${bmltwf_bmlt_formats[key].key_string})-${bmltwf_bmlt_formats[key].name_string}`, id: key });
    }
  });

  $('#quickedit_format_shared_id_list').select2({
    placeholder: 'Select from available formats',
    multiple: true,
    width: '100%',
    data: formatdata,
    selectionCssClass: ':all:',
    dropdownParent: $('#bmltwf_submission_quickedit_dialog'),
  });
  $('#quickedit_format_shared_id_list').trigger('change');

  const datatable = $('#dt-submission').DataTable({
    dom: 'Bfrtip',
    select: true,
    searching: true,
    order: [[5, 'desc']],
    buttons: [
      {
        name: 'approve',
        text: __('Approve', 'bmlt-workflow'),
        enabled: false,
        action(e, dt) {
          const { id } = dt.row('.selected').data();
          const reason = dt.row('.selected').data().submission_type;
          if (reason === 'reason_close') {
            // clear text area from before
            $('#bmltwf_submission_approve_close_dialog_textarea').val('');
            $('#bmltwf_submission_approve_close_dialog').data('id', id).dialog('open');
          } else {
            // clear text area from before
            $('#bmltwf_submission_approve_dialog_textarea').val('');
            $('#bmltwf_submission_approve_dialog').data('id', id).dialog('open');
          }
        },
      },
      {
        name: 'reject',
        text: __('Reject', 'bmlt-workflow'),
        enabled: false,
        action(e, dt) {
          const { id } = dt.row('.selected').data();
          // clear text area from before
          $('#bmltwf_submission_reject_dialog_textarea').val('');
          $('#bmltwf_submission_reject_dialog').data('id', id).dialog('open');
        },
      },
      {
        name: 'quickedit',
        text: __('QuickEdit', 'bmlt-workflow'),
        extend: 'selected',
        action(e, dt) {
          const { id } = dt.row('.selected').data();
          populate_and_open_quickedit(id);
        },
      },
      {
        name: 'delete',
        text: __('Delete', 'bmlt-workflow'),
        enabled: bmltwf_datatables_delete_enabled,
        extend: 'selected',
        action(e, dt) {
          const { id } = dt.row('.selected').data();
          $('#bmltwf_submission_delete_dialog').data('id', id).dialog('open');
        },
      },
    ],
    ajax: {
      url: bmltwf_admin_submissions_rest_url,
      beforeSend(xhr) {
        xhr.setRequestHeader('X-WP-Nonce', $('#_wprestnonce').val());
      },
      dataSrc(json) {
        bmltwf_changedata = {};
        // eslint-disable-next-line prefer-const
        let newjson = JSON.parse(JSON.stringify(json));
        for (let i = 0, ien = newjson.length; i < ien; i += 1) {
          newjson[i].changes_requested.submission_type = newjson[i].submission_type;
          // store the json for us to use in quick editor
          bmltwf_changedata[newjson[i].id] = newjson[i];
        }
        return newjson;
      },
    },
    columns: [
      {
        name: 'id',
        data: 'id',
      },
      {
        name: 'submitter_name',
        data: 'submitter_name',
      },
      {
        name: 'submitter_email',
        data: 'submitter_email',
      },
      {
        name: 'service_body_bigint',
        data: 'service_body_bigint',
        render(data) {
          if (data in bmltwf_admin_bmltwf_service_bodies) {
            return bmltwf_admin_bmltwf_service_bodies[data].name;
          }
          return `<b>${__('Service body ID', 'bmlt-workflow')} ${data} ${__('no longer shown to users', 'bmlt-workflow')}</b>`;
        },
      },
      {
        name: 'changes_requested',
        data: 'changes_requested',
        render(data) {
          let summary = '';
          let submission_type = '';
          let namestr = '';
          let original = '';
          let meeting_day = '';
          let meeting_time = '';
          switch (data.submission_type) {
            case 'reason_new':
              submission_type = __('New Meeting', 'bmlt-workflow');
              namestr = data.meeting_name;
              meeting_day = weekdays[data.weekday_tinyint];
              meeting_time = data.start_time;
              break;
            case 'reason_close':
              submission_type = __('Close Meeting', 'bmlt-workflow');
              // console.log(data);
              namestr = data.meeting_name;
              meeting_day = weekdays[data.weekday_tinyint];
              meeting_time = data.start_time;
              break;
            case 'reason_change':
              submission_type = __('Modify Meeting', 'bmlt-workflow');
              namestr = data.original_meeting_name;
              meeting_day = weekdays[data.original_weekday_tinyint];
              meeting_time = data.original_start_time;
              original = `${__('Original', 'bmlt-workflow')} `;
              break;
            default:
              submission_type = data.submission_type;
          }
          summary = `${__('Submission Type', 'bmlt-workflow')}: ${submission_type}<br>`;
          if (namestr !== '') {
            summary += `${__('Meeting Name', 'bmlt-workflow')}: ${namestr}<br>`;
          }
          if (meeting_day !== '' && meeting_time !== '') {
            summary += `${original + __('Time', 'bmlt-workflow')}: ${meeting_day} ${meeting_time}`;
          }
          return summary;
        },
      },
      {
        name: 'submission_time',
        data: 'submission_time',
        render(data) {
          return mysql2localdate(data);
        },
      },
      {
        name: 'change_time',
        data: 'change_time',
        render(data) {
          if (data === '0000-00-00 00:00:00') {
            return '(no change made)';
          }
          return mysql2localdate(data);
        },
      },
      {
        name: 'changed_by',
        data: 'changed_by',
      },
      {
        name: 'change_made',
        data: 'change_made',
        defaultContent: '',
        render(data) {
          if (data === null) {
            return __('None - Pending', 'bmlt-workflow');
          }
          switch (data) {
            case 'approved':
              return __('Approved', 'bmlt-workflow');
            case 'rejected':
              return __('Rejected', 'bmlt-workflow');
            case 'updated':
              return __('Updated', 'bmlt-workflow');
            default:
              return data;
          }
        },
      },
      {
        className: 'dt-control',
        orderable: false,
        data: null,
        defaultContent: '',
      },
    ],
  });

  $('#dt-submission_wrapper .dt-buttons').append(
    `${__('Filter', 'bmlt-workflow')}: <select id='dt-submission-filters'><option value='all'>${__('All', 'bmlt-workflow')}</option><option value='pending'>${__(
      'Pending',
      'bmlt-workflow',
    )}</option><option value='approved'>${__('Approved', 'bmlt-workflow')}</option><option value='rejected'>${__('Rejected', 'bmlt-workflow')}</option></select>`,
  );
  $('#dt-submission-filters').change(function () {
    $('#dt-submission').DataTable().draw();
  });

  // filter on column 8
  $.fn.dataTable.ext.search.push(function (settings, data) {
    const selectedItem = $('#dt-submission-filters').val();
    const category = data[8];
    if (selectedItem === 'all') {
      return true;
    }
    if (selectedItem === 'pending' && category.includes(__('Pending', 'bmlt-workflow'))) {
      return true;
    }
    if (selectedItem === 'approved' && category.includes(__('Approved', 'bmlt-workflow'))) {
      return true;
    }
    if (selectedItem === 'rejected' && category.includes(__('Rejected', 'bmlt-workflow'))) {
      return true;
    }
    return false;
  });

  $('#dt-submission')
    .DataTable()
    .on('select deselect', function () {
      let actioned = true;
      // handle optional delete
      $('#dt-submission').DataTable().button('delete:name').enable(bmltwf_datatables_delete_enabled);

      if ($('#dt-submission').DataTable().row({ selected: true }).count()) {
        const { change_made } = $('#dt-submission').DataTable().row({ selected: true }).data();
        const { submission_type } = $('#dt-submission').DataTable().row({ selected: true }).data();
        actioned = change_made === 'approved' || change_made === 'rejected';
        const cantquickedit = change_made === 'approved' || change_made === 'rejected' || submission_type === 'reason_close';
        $('#dt-submission').DataTable().button('approve:name').enable(!actioned);
        $('#dt-submission').DataTable().button('reject:name').enable(!actioned);
        $('#dt-submission').DataTable().button('quickedit:name').enable(!cantquickedit);
      } else {
        $('#dt-submission').DataTable().button('approve:name').enable(false);
        $('#dt-submission').DataTable().button('reject:name').enable(false);
        $('#dt-submission').DataTable().button('quickedit:name').enable(false);
      }
    });

  function column(col, key, value) {
    let output = `<div class="c${col}k">`;
    output += key;
    output += ':</div>';
    output += `<div class="c${col}v">`;
    output += value;
    output += '</div>';
    return output;
  }

  // child rows
  function format(rows) {
    // clone the requested info
    const d = $.extend(true, {}, rows);

    const col_meeting_details = 1;
    const col_personal_details = 2;
    const col_virtual_meeting_details = 3;
    const col_fso_other = 4;

    let table = '<div class="header">';
    table += `<div class="cell-hdr h${col_personal_details}">${__('Personal Details', 'bmlt-workflow')}</div>`;
    table += `<div class="cell-hdr h${col_meeting_details}">${__('Updated Meeting Details', 'bmlt-workflow')}</div>`;
    table += `<div class="cell-hdr h${col_virtual_meeting_details}">${__('Updated Virtual Meeting Details', 'bmlt-workflow')}</div>`;
    table += `<div class="cell-hdr h${col_fso_other}">${__('FSO Request and Other Info', 'bmlt-workflow')}</div>`;
    table += '</div><div class="gridbody">';

    Object.keys(d).forEach((key) => {
      switch (key) {
        case 'action_message':
          if (d.action_message !== '' && d.action_message != null) {
            table += column(col_fso_other, __('Message to submitter', 'bmlt-workflow'), d[key]);
          }
          break;
        case 'submitter_email':
          table += column(col_personal_details, __('Submitter Email', 'bmlt-workflow'), d[key]);
          break;
        case 'submitter_name':
          table += column(col_personal_details, __('Submitter Name', 'bmlt-workflow'), d[key]);
          break;
        default:
          break;
      }
    });

    const c = d.changes_requested;

    // some special handling for deletion of fields
    Object.keys(c).forEach((key) => {
      switch (key) {
        case 'original_virtual_meeting_additional_info':
          if ((!('virtual_meeting_additional_info' in c) || c.virtual_meeting_additional_info === '') && bmltwf_remove_virtual_meeting_details_on_venue_change === 'true') {
            d.changes_requested.virtual_meeting_additional_info = __('(deleted)', 'bmlt-workflow');
          }
          break;
        case 'original_phone_meeting_number':
          if ((!('phone_meeting_number' in c) || c.phone_meeting_number === '') && bmltwf_remove_virtual_meeting_details_on_venue_change === 'true') {
            d.changes_requested.phone_meeting_number = __('(deleted)', 'bmlt-workflow');
          }
          break;
        case 'original_virtual_meeting_link':
          if ((!('virtual_meeting_link' in c) || c.virtual_meeting_link === '') && bmltwf_remove_virtual_meeting_details_on_venue_change === 'true') {
            d.changes_requested.virtual_meeting_link = __('(deleted)', 'bmlt-workflow');
          }
          break;
        default:
          break;
      }
    });

    // fill in the sub menu

    Object.keys(c).forEach((key) => {
      switch (key) {
        case 'meeting_name': {
          let mname = __('Meeting Name (new)', 'bmlt-workflow');
          if (d.submission_type === 'reason_close') {
            mname = __('Meeting Name', 'bmlt-workflow');
          }
          table += column(col_meeting_details, mname, c[key]);
          break;
        }
        case 'venue_type': {
          const vtype = venue_types[c[key]];
          if ('original_venue_type' in c) {
            const ovtype = venue_types[c.original_venue_type];
            table += column(col_meeting_details, __('Venue Type', 'bmlt-workflow'), `${ovtype} → ${vtype}`);
          } else {
            table += column(col_meeting_details, __('Venue Type', 'bmlt-workflow'), vtype);
          }
          break;
        }
        case 'published': {
          const published = c[key] === 1 ? 'Yes' : 'No';
          if ('original_published' in c) {
            const opublished = c.original_published === 1 || c.original_published === true ? 'Yes' : 'No';
            table += column(col_meeting_details, __('Published', 'bmlt-workflow'), `${opublished} → ${published}`);
          } else {
            table += column(col_meeting_details, __('Published', 'bmlt-workflow'), `${published}`);
          }
          break;
        }
        case 'virtualna_published': {
          const published = c[key] === '1' ? 'Yes' : 'No';
          if ('original_virtualna_published' in c) {
            const opublished = c.original_published === 1 || c.original_published === true ? 'Yes' : 'No';
            table += column(col_meeting_details, __('Virtual.na.org Published', 'bmlt-workflow'), `${opublished} → ${published}`);
          } else {
            table += column(col_meeting_details, __('Virtual.na.org Published', 'bmlt-workflow'), `${published}`);
          }
          break;
        }
        case 'start_time':
          table += column(col_meeting_details, __('Start Time', 'bmlt-workflow'), c[key]);
          break;
        case 'duration_time': {
          const durationarr = d.changes_requested.duration_time.split(':');
          table += column(col_meeting_details, __('Duration', 'bmlt-workflow'), `${durationarr[0]}h${durationarr[1]}m`);
          break;
        }
        case 'location_text':
          table += column(col_meeting_details, __('Location', 'bmlt-workflow'), c[key]);
          break;
        case 'location_street':
          table += column(col_meeting_details, __('Street', 'bmlt-workflow'), c[key]);
          break;
        case 'location_info':
          table += column(col_meeting_details, __('Location Info', 'bmlt-workflow'), c[key]);
          break;
        case 'location_municipality':
          table += column(col_meeting_details, __('Municipality', 'bmlt-workflow'), c[key]);
          break;
        case 'location_province':
          table += column(col_meeting_details, bmltwf_optional_location_province_displayname, c[key]);
          break;
        case 'location_sub_province':
          table += column(col_meeting_details, bmltwf_optional_location_sub_province_displayname, c[key]);
          break;
        case 'location_nation':
          table += column(col_meeting_details, bmltwf_optional_location_nation_displayname, c[key]);
          break;
        case 'location_postal_code_1':
          table += column(col_meeting_details, bmltwf_optional_postcode, c[key]);
          break;
        case 'group_relationship':
          table += column(col_personal_details, __('Relationship to Group', 'bmlt-workflow'), c[key]);
          break;
        case 'weekday_tinyint':
          table += column(col_meeting_details, __('Meeting Day', 'bmlt-workflow'), weekdays[c[key]]);
          break;
        case 'starter_kit_postal_address':
          if (c.starter_kit_required === 'yes') {
            table += column(col_fso_other, __('Starter Kit Postal Address', 'bmlt-workflow'), c[key]);
          }
          break;
        case 'additional_info':
          table += column(col_fso_other, __('Additional Info', 'bmlt-workflow'), c[key]);
          break;
        case 'other_reason':
          table += column(col_fso_other, __('Other Reason', 'bmlt-workflow'), c[key]);
          break;
        case 'latitude':
          table += column(col_fso_other, __('Latitude (calculated)', 'bmlt-workflow'), c[key]);
          break;
        case 'longitude':
          table += column(col_fso_other, __('Longitude (calculated)', 'bmlt-workflow'), c[key]);
          break;
        case 'contact_number':
          table += column(col_personal_details, __('Contact number (confidential)', 'bmlt-workflow'), c[key]);
          break;
        case 'add_contact':
          table += column(col_personal_details, __('Add contact details to meeting', 'bmlt-workflow'), d.changes_requested.add_contact === 'yes' ? 'Yes' : 'No');
          break;
        case 'virtual_meeting_additional_info':
          table += column(col_virtual_meeting_details, __('Virtual Meeting Additional Info', 'bmlt-workflow'), c[key]);
          break;
        case 'phone_meeting_number':
          table += column(col_virtual_meeting_details, __('Virtual Meeting Phone Details', 'bmlt-workflow'), c[key]);
          break;
        case 'virtual_meeting_link':
          table += column(col_virtual_meeting_details, __('Virtual Meeting Link', 'bmlt-workflow'), c[key]);
          break;

        case 'format_shared_id_list': {
          const friendlyname = __('Meeting Formats', 'bmlt-workflow');
          // convert the meeting formats to human readable
          let friendlydata = '';
          const strarr = d.changes_requested.format_shared_id_list.split(',');
          strarr.forEach((element) => {
            friendlydata += `(${bmltwf_bmlt_formats[element].key_string})-${bmltwf_bmlt_formats[element].name_string} `;
          });
          table += column(col_meeting_details, friendlyname, friendlydata);

          break;
        }
        default:
          break;
      }
    });

    table += '</div>';
    return table;
  }

  $('#dt-submission tbody').on('click', 'td.dt-control', function (event) {
    const tr = $(this).closest('tr');
    const row = datatable.row(tr);

    event.stopPropagation();

    if (row.child.isShown()) {
      // This row is already open - close it
      row.child.hide();
      tr.removeClass('shown');
    } else {
      // Open this row
      row.child(format(row.data())).show();
      tr.addClass('shown');
    }
  });

  function bmltwf_create_generic_modal(dialogid, title, width, maxwidth) {
    $(`#${dialogid}`).dialog({
      title,
      autoOpen: false,
      draggable: false,
      width,
      maxWidth: maxwidth,
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
          const fn = window[`${this.id}_ok`];
          if (typeof fn === 'function') fn($(this).data('id'));
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
  }

  function geolocate_handler() {
    const locfields = ['location_street', 'location_municipality', 'location_province', 'location_nation'];

    if (!bmltwf_zip_auto_geocoding) {
      locfields.push('location_postal_code_1');
    }

    if (!bmltwf_county_auto_geocoding) {
      locfields.push('location_sub_province');
    }

    const locdata = [];

    locfields.forEach((item) => {
      const el = `#quickedit_${item}`;
      const val = $(el).val();
      if (val !== '') {
        locdata.push(val);
      }
    });

    const address = `address=${locdata.join(',')}`;

    $.ajax({
      url: bmltwf_bmltserver_geolocate_rest_url,
      type: 'GET',
      dataType: 'json',
      contentType: 'application/json',
      data: encodeURI(address),
      beforeSend(xhr) {
        bmltwf_clear_notices();
        xhr.setRequestHeader('X-WP-Nonce', $('#_wprestnonce').val());
      },
    })
      .done(function (response) {
        // const lat = response.latitude;
        // const long = response.longitude;
        const lat = response.results[0].geometry.location.lat;
        const long = response.results[0].geometry.location.lng;

        if (bmltwf_zip_auto_geocoding) {
          // eslint-disable-next-line consistent-return
          $.each(response.results[0].address_components, function (i, v) {
            if (v.types.includes('postal_code')) {
              $('#quickedit_location_postal_code_1').val(v.short_name);
              return false;
            }
          });
        }
        if (bmltwf_county_auto_geocoding) {
          // eslint-disable-next-line consistent-return
          $.each(response.results[0].address_components, function (i, v) {
            if (v.types.includes('administrative_area_level_2')) {
              $('#quickedit_location_sub_province').val(v.short_name);
              return false;
            }
          });
        }

        $('#quickedit_latitude').val(lat);
        $('#quickedit_longitude').val(long);
        update_gmaps(lat, long);
        bmltwf_notice_success(response, 'bmltwf-quickedit-error-message');
      })
      .fail(function (xhr) {
        bmltwf_notice_error(xhr, 'bmltwf-quickedit-error-message');
      });
  }

  function save_handler(id) {
    const parameters = {};
    const quickedit_changes_requested = {};

    bmltwf_clear_notices();

    // pull out all the changed elements
    $('.bmltwf-changed').each(function () {
      if ($(this).is('textarea,select,input')) {
        const short_id = $(this).attr('id').replace('quickedit_', '');
        // turn the format list into a comma seperated array
        if (short_id === 'format_shared_id_list') {
          quickedit_changes_requested[short_id] = $(this).val().join(',');
        } else if (short_id === 'duration_hours' || short_id === 'duration_minutes') {
          // reconstruct our duration from the select list
          // add duration entirely if either minutes or hours have changed
          quickedit_changes_requested.duration_time = `${$('#quickedit_duration_hours').val()}:${$('#quickedit_duration_minutes').val()}:00`;
        } else if ((short_id === 'virtual_meeting_additional_info' || short_id === 'phone_meeting_number' || short_id === 'virtual_meeting_link') && $(this).val() === '(deleted)') {
          delete quickedit_changes_requested[short_id];
        } else {
          quickedit_changes_requested[short_id] = $(this).val();
        }
      }
    });

    if ($('#quickedit_latitude').val()) {
      quickedit_changes_requested.latitude = $('#quickedit_latitude').val();
      quickedit_changes_requested.longitude = $('#quickedit_longitude').val();
    }

    parameters.changes_requested = quickedit_changes_requested;

    $.ajax({
      url: bmltwf_admin_submissions_rest_url + id,
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

        // reload the table to pick up any changes
        $('#dt-submission').DataTable().ajax.reload();
        // reset the buttons correctly
        $('#dt-submission').DataTable().rows().deselect();
      })
      .fail(function (xhr) {
        bmltwf_notice_error(xhr, 'bmltwf-error-message');
      });
    $('#bmltwf_submission_quickedit_dialog').dialog('close');
  }

  function bmltwf_create_quickedit_modal(dialogid, title, width, maxwidth) {
    $(`#${dialogid}`).dialog({
      title,
      classes: { 'ui-dialog-content': 'quickedit' },
      autoOpen: false,
      draggable: false,
      width,
      maxWidth: maxwidth,
      modal: true,
      resizable: false,
      closeOnEscape: true,
      position: {
        my: 'center',
        at: 'center',
        of: window,
      },
      buttons: [
        {
          text: __('Check Geolocate', 'bmlt-workflow'),
          click() {
            geolocate_handler($(this).data('id'));
          },
          disabled: !bmltwf_auto_geocoding_enabled,
        },
        {
          text: __('Save', 'bmlt-workflow'),
          click() {
            save_handler($(this).data('id'));
          },
        },
        {
          text: __('Cancel', 'bmlt-workflow'),
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
  }

  function generic_approve_handler(id, action, url, slug) {
    const parameters = {};
    if ($(`#${slug}_dialog_textarea`).length) {
      const action_message = $(`#${slug}_dialog_textarea`).val().trim();
      if (action_message !== '') {
        parameters.action_message = action_message;
      }
    }

    // delete/unpublish handling on the approve+close dialog
    if (slug === 'bmltwf_submission_approve_close') {
      const option = $(`#${slug}_dialog input[name="close_action"]:checked`).attr('id');
      if (option === 'close_delete') {
        parameters.delete = true;
      } else {
        parameters.delete = false;
      }
    }

    $.ajax({
      url: bmltwf_admin_submissions_rest_url + id + url,
      type: action,
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
        // reload the table to pick up any changes
        $('#dt-submission').DataTable().ajax.reload();
        // reset the buttons correctly
        $('#dt-submission').DataTable().rows().deselect();
      })
      .fail(function (xhr) {
        bmltwf_notice_error(xhr, 'bmltwf-error-message');
      });
    $(`#${slug}_dialog`).dialog('close');
  }

  bmltwf_create_generic_modal('bmltwf_submission_delete_dialog', __('Delete Submission', 'bmlt-workflow'), 'auto', 'auto');
  bmltwf_create_generic_modal('bmltwf_submission_approve_dialog', __('Approve Submission', 'bmlt-workflow'), 'auto', 'auto');
  bmltwf_create_generic_modal('bmltwf_submission_approve_close_dialog', __('Approve Submission', 'bmlt-workflow'), 'auto', 'auto');
  bmltwf_create_generic_modal('bmltwf_submission_reject_dialog', __('Reject Submission', 'bmlt-workflow'), 'auto', 'auto');
  bmltwf_create_quickedit_modal('bmltwf_submission_quickedit_dialog', __('Submission QuickEdit', 'bmlt-workflow'), 'auto', 'auto');

  // eslint-disable-next-line no-undef
  bmltwf_submission_approve_dialog_ok = function (id) {
    bmltwf_clear_notices();
    generic_approve_handler(id, 'POST', '/approve', 'bmltwf_submission_approve');
  };

  // eslint-disable-next-line no-undef
  bmltwf_submission_approve_close_dialog_ok = function (id) {
    bmltwf_clear_notices();
    generic_approve_handler(id, 'POST', '/approve', 'bmltwf_submission_approve_close');
  };

  // eslint-disable-next-line no-undef
  bmltwf_submission_reject_dialog_ok = function (id) {
    bmltwf_clear_notices();
    generic_approve_handler(id, 'POST', '/reject', 'bmltwf_submission_reject');
  };
  // eslint-disable-next-line no-undef
  bmltwf_submission_delete_dialog_ok = function (id) {
    bmltwf_clear_notices();
    generic_approve_handler(id, 'DELETE', '', 'bmltwf_submission_delete');
  };
});
