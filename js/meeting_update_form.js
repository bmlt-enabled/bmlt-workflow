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

/* global wp, jQuery */
/* global bmltwf_clear_notices, bmltwf_turn_on_spinner, bmltwf_turn_off_spinner, bmltwf_notice_error */
/* global bmltwf_fso_feature, bmltwf_counties_and_sub_provinces, bmltwf_do_states_and_provinces, bmltwf_bmlt_formats, bmltwf_service_bodies */
/* global bmltwf_optional_location_nation, bmltwf_optional_postcode,, bmltwf_optional_location_sub_province, bmltwf_optional_location_province */
/* global bmltwf_form_submit_url, bmltwf_bmltserver_meetings_rest_url */

const { __ } = wp.i18n;

const weekdays = [__('Sunday', 'bmlt-workflow'), __('Monday', 'bmlt-workflow'), __('Tuesday', 'bmlt-workflow'),
  __('Wednesday', 'bmlt-workflow'), __('Thursday', 'bmlt-workflow'), __('Friday', 'bmlt-workflow'), __('Saturday', 'bmlt-workflow')];

jQuery(document).ready(function ($) {
  function get_query_string_parameter(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
  }

  function enable_highlighting() {
    // add highlighting trigger for general input fields
    $('.meeting-input').on('input.bmltwf-highlight', function () {
      $(this).addClass('bmltwf-changed');
    });
    // add highlighting trigger for select2
    $('#display_formatIds').on('change.bmltwf-highlight', function () {
      $('.select2-selection--multiple').addClass('bmltwf-changed');
    });
  }

  function put_field(fieldname, value) {
    const field = `#${fieldname}`;
    $(field).val(value);
    $(field).trigger('change');
  }

  function clear_field(fieldname) {
    const field = `#${fieldname}`;
    $(field).val('');
    $(field).trigger('change');
  }

  function enable_field(fieldname) {
    const field = `#${fieldname}`;
    $(field).prop('disabled', false);
    $(field).trigger('change');
  }

  function disable_field(fieldname) {
    const field = `#${fieldname}`;
    $(field).prop('disabled', true);
    $(field).trigger('change');
  }

  function enable_edits() {
    enable_field('name');
    enable_field('startTime');
    enable_field('duration_minutes');
    enable_field('duration_hours');
    enable_field('location_street');
    enable_field('location_text');
    enable_field('location_info');
    enable_field('location_municipality');
    enable_field('location_province');
    enable_field('location_postal_code_1');
    enable_field('location_sub_province');
    enable_field('location_nation');
    enable_field('display_formatIds');
    enable_field('day');
    enable_field('serviceBodyId');
    enable_field('virtual_meeting_additional_info');
    enable_field('phone_meeting_number');
    enable_field('virtual_meeting_link');
    enable_field('');
  }

  function disable_edits() {
    disable_field('name');
    disable_field('startTime');
    disable_field('duration_minutes');
    disable_field('duration_hours');
    disable_field('location_street');
    disable_field('location_text');
    disable_field('location_info');
    disable_field('location_municipality');
    disable_field('location_province');
    disable_field('location_postal_code_1');
    disable_field('location_sub_province');
    disable_field('location_nation');
    disable_field('display_formatIds');
    disable_field('day');
    disable_field('serviceBodyId');
    disable_field('virtual_meeting_additional_info');
    disable_field('phone_meeting_number');
    disable_field('virtual_meeting_link');
    disable_field('venueType');
  }

  function clear_form() {
    clear_field('name');
    clear_field('startTime');
    clear_field('duration_minutes');
    clear_field('duration_hours');
    clear_field('location_street');
    clear_field('location_text');
    clear_field('location_info');
    clear_field('location_municipality');
    clear_field('location_province');
    clear_field('location_postal_code_1');
    clear_field('location_sub_province');
    clear_field('location_nation');
    clear_field('first_name');
    clear_field('last_name');
    clear_field('contact_number');
    clear_field('email_address');
    clear_field('display_formatIds');
    clear_field('id');
    clear_field('additional_info');
    clear_field('meeting_searcher');
    clear_field('starter_kit_postal_address');
    clear_field('virtual_meeting_additional_info');
    clear_field('phone_meeting_number');
    clear_field('virtual_meeting_link');
    // placeholder for these select elements
    $('#group_relationship').val('');
    $('#venueType').val('');
    $('#serviceBodyId').val('');
    // reset select2
    $('#display_formatIds').val(null).trigger('change');
    $('#meeting-searcher').val(null).trigger('change');
    // set email selector to no
    $('#add-email').val('no');
  }

  function disable_and_clear_highlighting() {
    // disable the highlighting triggers
    $('.meeting-input').off('input.bmltwf-highlight');
    $('#display_formatIds').off('change.bmltwf-highlight');
    // remove the highlighting css
    $('.meeting-input').removeClass('bmltwf-changed');
    $('.select2-selection--multiple').removeClass('bmltwf-changed');
  }

  $('#meeting-searcher').on('select2:open', function () {
    // Use bmltwf_translations object if available, otherwise fallback to hardcoded string
    const placeholderText = (typeof bmltwf_translations !== 'undefined' && 
                           bmltwf_translations.begin_typing_meeting_name) ? 
                           bmltwf_translations.begin_typing_meeting_name : 
                           'Begin typing your meeting name';
    $('input.select2-search__field').prop('placeholder', placeholderText);
  });

  function real_submit_handler() {
    bmltwf_clear_notices();
    // in case we disabled this we want to send it now
    enable_field('serviceBodyId');

    // prevent displayable list from being submitted
    $('#display_formatIds').attr('disabled', 'disabled');
    // turn the format list into a single string and move it into the submitted formatIds
    // $('#formatIds').val($('#display_formatIds').val().join(','));
    // $('#formatIds').val($('#display_formatIds').val());

    // time control by default doesn't add extra seconds, so add them to be compaitble with BMLT
    if ($('#startTime').val().length === 5) {
      $('#startTime').val(`${$('#startTime').val()}:00`);
    }

    // construct our duration
    const str = `${$('#duration_hours').val()}:${$('#duration_minutes').val()}:00`;
    put_field('duration', str);

    if ($('#venueType') === 4) {
      put_field('venueType', 1);
      put_field('temporarilyVirtual', 'true');
    }

    const form_submit = $('#meeting_update_form').serializeObject();
    form_submit.formatIds = $('#display_formatIds').val();

    $.ajax({
      url: bmltwf_form_submit_url,
      method: 'POST',
      data: JSON.stringify(form_submit),
      contentType: 'application/json; charset=utf-8',
      dataType: 'json',
      processData: false,
      beforeSend() {
        bmltwf_turn_on_spinner('#bmltwf-submit-spinner');
        $('#submit').prop('disabled', true);
      },
    })
      .done(function (response) {
        bmltwf_turn_off_spinner('#bmltwf-submit-spinner');
        $('#submit').prop('disabled', false);
        $('#form_replace').replaceWith(response.form_html);
      })
      .fail(function (xhr) {
        bmltwf_turn_off_spinner('#bmltwf-submit-spinner');
        $('#submit').prop('disabled', false);
        bmltwf_notice_error(xhr, 'bmltwf-error-message');
      });
  }

  // set up our format selector
  const formatdata = [];

  // display handler for fso options
  if (bmltwf_fso_feature === 'hidden') {
    $('#starter_pack').hide();
  } else {
    $('#starter_pack').show();
  }

  // fill in counties and sub provinces
  if (bmltwf_counties_and_sub_provinces === false) {
    $('#optional_location_sub_province').append('<input class="meeting-input" type="text" name="location_sub_province" size="50" id="location_sub_province">');
  } else {
    let appendstr = '<select class="meeting-input" id="location_sub_province" name="location_sub_province">';
    bmltwf_counties_and_sub_provinces.forEach(function (item) {
      appendstr += `<option value="${item}">${item}</option>`;
    });
    appendstr += '</select>';
    $('#optional_location_sub_province').append(appendstr);
  }

  if (bmltwf_do_states_and_provinces === false) {
    $('#optional_location_province').append('<input class="meeting-input" type="text" name="location_province" size="50" id="location_province">');
  } else {
    let appendstr = '<select class="meeting-input" id="location_province" name="location_province">';
    bmltwf_do_states_and_provinces.forEach(function (item) {
      appendstr += `<option value="${item}">${item}</option>`;
    });
    appendstr += '</select>';
    $('#optional_location_province').append(appendstr);
  }

  Object.keys(bmltwf_bmlt_formats).forEach((key) => {
    const { key_string } = bmltwf_bmlt_formats[key];
    if (!((key_string === 'HY') || (key_string === 'VM') || (key_string === 'TC'))) {
      formatdata.push({ text: `(${bmltwf_bmlt_formats[key].key_string})-${bmltwf_bmlt_formats[key].name_string}`, id: key });
    }
  });

  $('#display_formatIds').select2({
    placeholder: (typeof bmltwf_translations !== 'undefined' && 
                bmltwf_translations.select_from_available_formats) ? 
                bmltwf_translations.select_from_available_formats : 
                'Select from available formats',
    multiple: true,
    data: formatdata,
    width: '100%',
    theme: 'bmltwf_select2_theme',
  });

  // hide / show / required our optional fields
  switch (bmltwf_optional_location_nation) {
    case 'display':
      $('#optional_location_nation').show();
      $('#location_nation').attr('required', false);
      break;
    case 'displayrequired':
      $('#optional_location_nation').show();
      $('#location_nation').attr('required', true);
      $('#location_nation_label').append('<span class="bmltwf-required-field"> *</span>');
      break;
    case 'hidden':
    case '':
    default:
      $('#optional_location_nation').hide();
      break;
  }

  switch (bmltwf_optional_location_sub_province) {
    case 'display':
      $('#optional_location_sub_province').show();
      $('#location_sub_province').attr('required', false);
      break;
    case 'displayrequired':
      $('#optional_location_sub_province').show();
      $('#location_sub_province').attr('required', true);
      $('#location_sub_province_label').append('<span class="bmltwf-required-field"> *</span>');
      break;
    case 'hidden':
    case '':
    default:
      $('#optional_location_sub_province').hide();
      break;
  }

  switch (bmltwf_optional_location_province) {
    case 'display':
      $('#optional_location_province').show();
      $('#location_province').attr('required', false);
      break;
    case 'displayrequired':
      $('#optional_location_province').show();
      $('#location_province').attr('required', true);
      $('#location_province_label').append('<span class="bmltwf-required-field"> *</span>');
      break;
    case 'hidden':
    case '':
    default:
      $('#optional_location_province').hide();
      break;
  }

  switch (bmltwf_optional_postcode) {
    case 'display':
      $('#optional_postcode').show();
      $('#location_postal_code_1').attr('required', false);
      break;
    case 'displayrequired':
      $('#optional_postcode').show();
      $('#location_postal_code_1').attr('required', true);
      $('#location_postal_code_1_label').append('<span class="bmltwf-required-field"> *</span>');
      break;
    case 'hidden':
    case '':
    default:
      $('#optional_postcode').hide();
      break;
  }
  function create_meeting_searcher(mdata) {
    const mtext = [];

    // create friendly meeting details for meeting searcher
    for (let i = 0, { length } = mdata; i < length; i += 1) {
      let str = `${mdata[i].name} [ ${weekdays[mdata[i].day]}, ${mdata[i].startTime} ]`;
      let city = '';
      if (mdata[i].location_municipality !== '') {
        city = `${mdata[i].location_municipality}, `;
      }
      if (mdata[i].location_province !== '') {
        city += mdata[i].location_province;
      }
      if (city !== '') {
        city = `[ ${city} ]`;
      }
      str += city;
      mtext[i] = { text: str, id: i };
    }

    function matchCustom(params, data) {
      // If there are no search terms, return all of the data
      if (typeof params.term === 'undefined' || params.term.trim() === '') {
        return data;
      }

      // Do not display the item if there is no 'text' property
      if (typeof data.text === 'undefined') {
        return null;
      }

      // `params.term` should be the term that is used for searching
      // `data.text` is the text that is displayed for the data object

      // split the term on spaces and search them all as independent terms
      const allterms = params.term.split(/\s/).filter(function (x) {
        return x;
      });
      const ltext = data.text.toLowerCase();
      for (let i = 0; i < allterms.length; i += 1) {
        if (ltext.indexOf(allterms[i].toLowerCase()) > -1) {
          return data;
        }
      }

      // Return `null` if the term should not be displayed
      return null;
    }

    $('#meeting-searcher').select2({
      data: mtext,
      placeholder: __('Click to select', 'bmlt-workflow'),
      allowClear: true,
      dropdownAutoWidth: true,
      matcher: matchCustom,
      theme: 'bmltwf_select2_theme',
      width: '100%',
    });

    $('#meeting-searcher').on('select2:select', function (e) {
      disable_and_clear_highlighting();
      const { data } = e.params;
      const { id } = data;
      // set the weekday format
      $('#day').val(mdata[id].day);

      const fields = [
        'name',
        'startTime',
        'published',
        'virtualna_published',
        'location_street',
        'location_text',
        'location_info',
        'location_municipality',
        'location_province',
        'location_sub_province',
        'location_nation',
        'location_postal_code_1',
        'virtual_meeting_additional_info',
        'phone_meeting_number',
        'virtual_meeting_link',
        'venueType',
      ];

      // populate form fields from bmlt if they exist
      fields.forEach(function (item) {
        // Skip virtualna_published for non-virtual meetings
        if (item === 'virtualna_published' && mdata[id].venueType === 1) {
          return;
        }
        if (item in mdata[id]) {
          if (mdata[id][item] === true) {
            put_field(item, '1');
          } else if (mdata[id][item] === false) {
            put_field(item, '0');
          } else {
            put_field(item, mdata[id][item]);
          }
        }
      });

      // seperate handler for formats
      if ('formatIds' in mdata[id]) {
        const meeting_formats = mdata[id].formatIds;
        put_field('display_formatIds', meeting_formats);
      }

      // handle duration in the select dropdowns
      const durationarr = mdata[id].duration.split(':');
      // hoping we got both hours, minutes and seconds here
      if (durationarr.length === 2) {
        $('#duration_hours').val(durationarr[0]);
        $('#duration_minutes').val(durationarr[1]);
      }
      // handle service body in the select dropdown
      $('#serviceBodyId').val(mdata[id].serviceBodyId);

      const { venueType } = mdata[id];
      // doesn't handle if they have both selected in BMLT
      // virtual_meeting_options
      $('#venueType').val(venueType);
      if (venueType === 1) {
        $('#virtual_meeting_options').hide();
      } else {
        $('#virtual_meeting_options').show();
      }

      // allow meeting unpublish from virtual.na.org
      if ('worldid_mixed' in mdata[id] && mdata[id].worldid_mixed !== '') {
        if (mdata[id].worldid_mixed.charAt(0) === 'G') {
          $('#virtualna_published').val(1);
        } else {
          $('#virtualna_published').val(0);
        }
        $('#virtualna_publish_div').show();
      } else {
        $('#virtualna_publish_div').hide();
      }

      // store the selected meeting ID away
      put_field('id', mdata[id].id);

      // different form behaviours after meeting selection depending on the change type
      const reason = $('#update_reason').val();
      switch (reason) {
        case 'reason_change':
          // display form instructions
          $('#instructions').html(
            __("We've retrieved the details below from our system. Please make any changes and then submit your update. <br>Any changes you make to the content are highlighted and will be submitted for approval.", 'bmlt-workflow'),
          );
          $('#meeting_content').show();
          $('#publish_div').show();
          disable_field('serviceBodyId');
          enable_highlighting();
          break;
        case 'reason_close':
          // display form instructions
          $('#instructions').html(`${__("Verify you have selected the correct meeting, then add details to support the meeting close request in the Additional Information box.<br><br><b>Note: If you are submitting a temporary meeting closure, please instead use 'Change existing meeting' and use the 'temporarily closed in person meeting' dropdown menu.", 'bmlt-workflow')}</b>`);
          $('#meeting_content').show();
          disable_edits();
          break;
        default:
          break;
      }
    });
  }

  function update_meeting_list(id = null) {
    $.ajax({
      url: bmltwf_bmltserver_meetings_rest_url,
      type: 'GET',
      dataType: 'json',
      contentType: 'application/json',
      beforeSend(xhr) {
        // bmltwf_clear_notices();
        xhr.setRequestHeader('X-WP-Nonce', $('#_wprestnonce').val());
      },
    })
      .done(function (response) {
        const mdata = JSON.parse(response.message);
        create_meeting_searcher(mdata);
        if (id) {
          const jump_to = mdata.findIndex((el) => el.id === parseInt(id, 10));
          $('#update_reason').val('reason_change').trigger('change');
          $('#meeting-searcher').val(jump_to).trigger('change').trigger({
            type: 'select2:select',
            params: {
              data: {
                id: jump_to,
              },
            },
          });
        }
      })
      .fail(function () {
        $('#instructions').html('<div class="bmltwf-error">Failed to load meeting data. Please try again later.</div>');
      });
  }

  // Sort service bodies alphabetically by name
  const sortedServiceBodies = Object.keys(bmltwf_service_bodies).sort((a, b) => {
    const nameA = bmltwf_service_bodies[a].name.toLowerCase();
    const nameB = bmltwf_service_bodies[b].name.toLowerCase();
    return nameA.localeCompare(nameB);
  });

  sortedServiceBodies.forEach((item) => {
    const serviceBodyId = item;
    const service_body_name = bmltwf_service_bodies[item].name;
    const opt = new Option(service_body_name, serviceBodyId, false, false);
    $('#serviceBodyId').append(opt);
  });

  const id = get_query_string_parameter('meeting_id');

  update_meeting_list(id);

  function is_virtual_meeting_additional_info_empty() {
    return ($('#virtual_meeting_additional_info').val().length === 0);
  }

  function is_virtual_meeting_link_empty() {
    return ($('#virtual_meeting_link').val().length === 0);
  }

  function is_phone_meeting_number_empty() {
    return ($('#phone_meeting_number').val().length === 0);
  }

  $('#meeting_update_form').validate({
    groups: {
      groupName: 'virtual_meeting_link virtual_meeting_additional_info phone_meeting_number',
    },
    errorPlacement(error, element) {
      if (element.attr('id') === 'virtual_meeting_link' || element.attr('id') === 'virtual_meeting_additional_info' || element.attr('id') === 'phone_meeting_number') {
        error.insertAfter('#phone_meeting_number');
      }
    },
    rules: {
      virtual_meeting_link: {
        required: (is_virtual_meeting_additional_info_empty || is_virtual_meeting_link_empty) && is_phone_meeting_number_empty,
      },
      virtual_meeting_additional_info: {
        required: (is_virtual_meeting_link_empty || is_virtual_meeting_additional_info_empty) && is_phone_meeting_number_empty,
      },
      phone_meeting_number: {
        required: is_virtual_meeting_additional_info_empty && is_virtual_meeting_link_empty,
      },
    },
    messages: {
      virtual_meeting_link: __('You must provide at least a phone number for a Virtual Meeting, or fill in both the Virtual Meeting link and Virtual Meeting additional information', 'bmlt-workflow'),
      virtual_meeting_additional_info: __('You must provide at least a phone number for a Virtual Meeting, or fill in both the Virtual Meeting link and Virtual Meeting additional information', 'bmlt-workflow'),
      phone_meeting_number: __('You must provide at least a phone number for a Virtual Meeting, or fill in both the Virtual Meeting link and Virtual Meeting additional information', 'bmlt-workflow'),
    },
    submitHandler() {
      real_submit_handler();
    },
  });

  $('#starter_kit_required').on('change', function () {
    if (this.value === 'yes') {
      $('#starter_kit_postal_address_div').show();
      $('#starter_kit_postal_address').prop('required', true);
    } else {
      $('#starter_kit_postal_address_div').hide();
      $('#starter_kit_postal_address').prop('required', false);
    }
  });

  // if (id && jump_to) {
  //   $('#update_reason').val('reason_change').trigger('change');
  //   $('#meeting-searcher').val(jump_to);
  // } else {
  // meeting logic before selection is made
  $('#meeting_selector').hide();
  $('#meeting_content').hide();
  $('#other_reason').prop('required', false);
  // }

  $('#venueType').on('change', function () {
    // show and hide the virtual meeting settings

    if (this.value === '1') {
      $('#virtual_meeting_options').hide();
      $('#location_fields').show();
      // Disable virtualna_published field for in-person meetings
      $('#virtualna_published').hide();
    } else {
      $('#virtual_meeting_options').show();
      // Enable virtualna_published field for virtual meetings
      $('#virtualna_published').show();
      switch (this.value) {
        case '2':
          $('#location_fields').hide();
          break;
        case '3':
          $('#location_fields').show();
          break;
        case '4':
          $('#location_fields').show();
          break;
        default:
          break;
      }
    }
  });

  $('#update_reason').on('change', function () {
    // hide all the optional items
    $('#reason_new_text').hide();
    $('#reason_change_text').hide();
    $('#reason_close_text').hide();
    $('#starter_pack').hide();
    $('#meeting_selector').hide();
    // enable the meeting form
    $('#meeting_content').hide();
    $('#other_reason').prop('required', false);
    $('#additional_info').prop('required', false);
    disable_and_clear_highlighting();
    enable_edits();
    // enable items as required
    const reason = $(this).val();

    clear_form();
    switch (reason) {
      case 'reason_new':
        $('#meeting_content').show();
        $('#publish_div').hide();
        $('#personal_details').show();
        $('#meeting_details').show();
        $('#additional_info_div').show();
        $('#virtual_meeting_options').hide();
        // display form instructions
        $('#instructions').html(
          __('Please fill in the details of your new meeting, and then submit your update. <br><b>Note:</b> If your meeting convenes multiple times a week, please submit additional new meeting requests for each day you meet.', 'bmlt-workflow'),
        );
        // new meeting has a starter pack
        if (bmltwf_fso_feature === 'display') {
          $('#starter_pack').show();
        }
        break;
      case 'reason_change':
        // hide this until they've selected a meeting
        $('#meeting_content').hide();
        $('#personal_details').show();
        $('#meeting_details').show();
        $('#additional_info_div').show();

        // change meeting has a search bar
        $('#meeting_selector').show();

        break;
      case 'reason_close':
        // hide this until they've selected a meeting
        $('#meeting_content').hide();
        $('#personal_details').show();
        $('#meeting_details').show();
        $('#additional_info_div').show();

        // close meeting has a search bar
        $('#meeting_selector').show();
        $('#additional_info').prop('required', true);
        break;
      default:
        break;
    }
  });

  // eslint-disable-next-line no-param-reassign
  $.fn.serializeObject = function () {
    const o = {};
    const a = this.serializeArray();
    $.each(a, function () {
      if (o[this.name]) {
        if (!o[this.name].push) {
          o[this.name] = [o[this.name]];
        }
        o[this.name].push(this.value || '');
      } else {
        o[this.name] = this.value || '';
      }
    });
    return o;
  };
});
