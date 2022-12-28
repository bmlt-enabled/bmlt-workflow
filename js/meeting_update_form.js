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

"use strict";

var weekdays = ["none", "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

jQuery(document).ready(function ($) {
  // set up our format selector
  var formatdata = [];

  // display handler for fso options
  if(bmltwf_fso_feature == 'hidden')
  {
    $("#starter_pack").hide();
  } else {
    $("#starter_pack").show();
  }
  
  // fill in counties and sub provinces
  if(bmltwf_counties_and_sub_provinces === false)
  {
    $("#optional_location_sub_province").append('<input class="meeting-input" type="text" name="location_sub_province" size="50" id="location_sub_province">');
  }
  else
  {
    var appendstr = '<select class="meeting-input" id="location_sub_province" name="location_sub_province">';
    bmltwf_counties_and_sub_provinces.forEach(function (item, index) {
      appendstr += '<option value="' + item + '">' + item + '</option>';
        });
    appendstr += '</select>';
    $("#optional_location_sub_province").append(appendstr);

  }

  if(bmltwf_do_states_and_provinces === false)
  {
    $("#optional_location_province").append('<input class="meeting-input" type="text" name="location_province" size="50" id="location_province">');
  }
  else
  {
    var appendstr = '<select class="meeting-input" id="location_province" name="location_province">';
    bmltwf_do_states_and_provinces.forEach(function (item, index) {
      appendstr += '<option value="' + item + '">' + item + '</option>';
    });
    appendstr += '</select>';
    $("#optional_location_province").append(appendstr);
  }

  Object.keys(bmltwf_bmlt_formats).forEach((key) => {
    var key_string = bmltwf_bmlt_formats[key]["key_string"];
    if (!((key_string === "HY")||(key_string === "VM")||(key_string === "TC")))
    {
      formatdata.push({ text: "(" + bmltwf_bmlt_formats[key]["key_string"] + ")-" + bmltwf_bmlt_formats[key]["name_string"], id: key });
    }
  });

  $("#display_format_shared_id_list").select2({
    placeholder: "Select from available formats",
    multiple: true,
    data: formatdata,
    width: "100%",
    theme: 'bmltwf_select2_theme',
  });

  // hide / show / required our optional fields
  switch (bmltwf_optional_location_nation) {
    case "hidden":
    case "":
      $("#optional_location_nation").hide();
      break;
    case "display":
      $("#optional_location_nation").show();
      $("#location_nation").attr("required", false);
      break;
    case "displayrequired":
      $("#optional_location_nation").show();
      $("#location_nation").attr("required", true);
      $("#location_nation_label").append('<span class="bmltwf-required-field"> *</span>');
      break;
  }

  switch (bmltwf_optional_location_sub_province) {
    case "hidden":
    case "":
      $("#optional_location_sub_province").hide();
      break;
    case "display":
      $("#optional_location_sub_province").show();
      $("#location_sub_province").attr("required", false);
      break;
    case "displayrequired":
      $("#optional_location_sub_province").show();
      $("#location_sub_province").attr("required", true);
      $("#location_sub_province_label").append('<span class="bmltwf-required-field"> *</span>');
      break;
  }

  switch (bmltwf_optional_location_province) {
    case "hidden":
    case "":
      $("#optional_location_province").hide();
      break;
    case "display":
      $("#optional_location_province").show();
      $("#location_province").attr("required", false);
      break;
    case "displayrequired":
      $("#optional_location_province").show();
      $("#location_province").attr("required", true);
      $("#location_province_label").append('<span class="bmltwf-required-field"> *</span>');
      break;
  }

  switch (bmltwf_optional_postcode) {
    case "hidden":
    case "":
      $("#optional_postcode").hide();
      break;
    case "display":
      $("#optional_postcode").show();
      $("#location_postal_code_1").attr("required", false);
      break;
    case "displayrequired":
      $("#optional_postcode").show();
      $("#location_postal_code_1").attr("required", true);
      $("#location_postal_code_1_label").append('<span class="bmltwf-required-field"> *</span>');
      break;
  }

  function update_meeting_list(bmltwf_service_bodies) {
    var search_results_address = bmltwf_bmlt_server_address + "client_interface/jsonp/?switcher=GetSearchResults&lang_enum=en&" + bmltwf_service_bodies + "recursive=1&sort_keys=meeting_name";

    fetchJsonp(search_results_address)
      .then((response) => response.json())
      .then((mdata) => create_meeting_searcher(mdata));
  }

  var bmltwf_service_bodies_querystr = "";

  Object.keys(bmltwf_service_bodies).forEach((item) => {
    // console.log(response);
    var service_body_bigint = item;
    var service_body_name = bmltwf_service_bodies[item]["name"];
    var opt = new Option(service_body_name, service_body_bigint, false, false);
    $("#service_body_bigint").append(opt);
    bmltwf_service_bodies_querystr += "services[]=" + service_body_bigint + "&";
  });

  update_meeting_list(bmltwf_service_bodies_querystr);

  $("#meeting_update_form").validate({
    submitHandler: function () {
      real_submit_handler();
    },
  });

  $("#starter_kit_required").on("change", function () {
    if (this.value == "yes") {
      $("#starter_kit_postal_address_div").show();
      $("#starter_kit_postal_address").prop("required", true);
    } else {
      $("#starter_kit_postal_address_div").hide();
      $("#starter_kit_postal_address").prop("required", false);
    }
  });

  function matchCustom(params, data) {
    // If there are no search terms, return all of the data
    if (typeof params.term === "undefined" || params.term.trim() === "") {
      return data;
    }

    // Do not display the item if there is no 'text' property
    if (typeof data.text === "undefined") {
      return null;
    }

    // `params.term` should be the term that is used for searching
    // `data.text` is the text that is displayed for the data object

    // split the term on spaces and search them all as independent terms
    var allterms = params.term.split(/\s/).filter(function (x) {
      return x;
    });
    var ltext = data.text.toLowerCase();
    for (var i = 0; i < allterms.length; ++i) {
      if (ltext.indexOf(allterms[i].toLowerCase()) > -1) {
        return data;
      }
    }

    // Return `null` if the term should not be displayed
    return null;
  }

  function create_meeting_searcher(mdata) {
    var mtext = [];

    // create friendly meeting details for meeting searcher
    for (let i = 0, length = mdata.length; i < length; i++) {
      let str = mdata[i].meeting_name + " [ " + weekdays[mdata[i].weekday_tinyint] + ", " + mdata[i].start_time + " ]";
      var city = "";
      if (mdata[i].location_municipality != "") {
        city = mdata[i].location_municipality + ", ";
      }
      if (mdata[i].location_province != "") {
        city += mdata[i].location_province;
      }
      if (city != "") {
        city = "[ " + city + " ]";
      }
      str = str + city;
      mtext[i] = { text: str, id: i };
    }

    $("#meeting-searcher").select2({
      data: mtext,
      placeholder: "Click to select",
      allowClear: true,
      dropdownAutoWidth: true,
      matcher: matchCustom,
      theme: 'bmltwf_select2_theme',
      width: '100%',
    });

    $("#meeting-searcher").on("select2:open", function (e) {
      $("input.select2-search__field").prop("placeholder", "Begin typing your meeting name");
    });

    $("#meeting-searcher").on("select2:select", function (e) {
      disable_and_clear_highlighting();
      var data = e.params.data;
      var id = data.id;
      // set the weekday format
      $("#weekday_tinyint").val(mdata[id].weekday_tinyint);

      var fields = [
        "meeting_name",
        "start_time",
        "location_street",
        "location_text",
        "location_info",
        "location_municipality",
        "location_province",
        "location_sub_province",
        "location_nation",
        "location_postal_code_1",
        "virtual_meeting_additional_info",
        "phone_meeting_number",
        "virtual_meeting_link",
        "venue_type"
      ];

      // populate form fields from bmlt if they exist
      fields.forEach(function (item, i) {
        if (item in mdata[id]) {
          put_field(item, mdata[id][item]);
        }
      });

      // seperate handler for formats
      if ("format_shared_id_list" in mdata[id]) {
        var meeting_formats = mdata[id].format_shared_id_list.split(",");
        put_field("display_format_shared_id_list", meeting_formats);
      }

      // handle duration in the select dropdowns
      var durationarr = mdata[id].duration_time.split(":");
      // hoping we got both hours, minutes and seconds here
      if (durationarr.length == 3) {
        $("#duration_hours").val(durationarr[0]);
        $("#duration_minutes").val(durationarr[1]);
      }
      // handle service body in the select dropdown
      $("#service_body_bigint").val(mdata[id].service_body_bigint);

      var venue_type = mdata[id].venue_type;
      // doesn't handle if they have both selected in BMLT
      $("#venue_type").val(venue_type);
      if (venue_type === "1") {
        $("#virtual_meeting_settings").hide();
      } else {
        $("#virtual_meeting_settings").show();
      }

      // store the selected meeting ID away
      put_field("meeting_id", mdata[id].id_bigint);

      // different form behaviours after meeting selection depending on the change type
      var reason = $("#update_reason").val();
      switch (reason) {
        case "reason_change":
          // display form instructions
          $("#instructions").html(
            "We've retrieved the details below from our system. Please make any changes and then submit your update. <br>Any changes you make to the content are highlighted and will be submitted for approval."
          );
          $("#meeting_content").show();
          disable_field("service_body_bigint");
          enable_highlighting();

          break;
        case "reason_close":
          // display form instructions
          $("#instructions").html("Verify you have selected the correct meeting, then add details to support the meeting close request in the Additional Information box");
          $("#meeting_content").show();
          disable_edits();
          break;
      }
    });
  }

  function disable_and_clear_highlighting() {
    // disable the highlighting triggers
    $(".meeting-input").off("input.bmltwf-highlight");
    $("#display_format_shared_id_list").off("change.bmltwf-highlight");
    // remove the highlighting css
    $(".meeting-input").removeClass("bmltwf-changed");
    $(".select2-selection--multiple").removeClass("bmltwf-changed");
  }

  function enable_highlighting() {
    // add highlighting trigger for general input fields
    $(".meeting-input").on("input.bmltwf-highlight", function () {
      $(this).addClass("bmltwf-changed");
    });
    // add highlighting trigger for select2
    $("#display_format_shared_id_list").on("change.bmltwf-highlight", function () {
      $(".select2-selection--multiple").addClass("bmltwf-changed");
    });
  }

  function put_field(fieldname, value) {
    var field = "#" + fieldname;
    $(field).val(value);
    $(field).trigger("change");
  }

  function clear_field(fieldname, value) {
    var field = "#" + fieldname;
    $(field).val("");
    $(field).trigger("change");
  }

  function enable_field(fieldname) {
    var field = "#" + fieldname;
    $(field).prop("disabled", false);
    $(field).trigger("change");
  }

  function disable_field(fieldname) {
    var field = "#" + fieldname;
    $(field).prop("disabled", true);
    $(field).trigger("change");
  }

  function enable_edits() {
    enable_field("meeting_name");
    enable_field("start_time");
    enable_field("duration_minutes");
    enable_field("duration_hours");
    enable_field("location_street");
    enable_field("location_text");
    enable_field("location_info");
    enable_field("location_municipality");
    enable_field("location_province");
    enable_field("location_postal_code_1");
    enable_field("location_sub_province");
    enable_field("location_nation");
    enable_field("display_format_shared_id_list");
    enable_field("weekday_tinyint");
    enable_field("service_body_bigint");
    enable_field("virtual_meeting_additional_info");
    enable_field("phone_meeting_number");
    enable_field("virtual_meeting_link");
    enable_field("");
  }

  function disable_edits() {
    disable_field("meeting_name");
    disable_field("start_time");
    disable_field("duration_minutes");
    disable_field("duration_hours");
    disable_field("location_street");
    disable_field("location_text");
    disable_field("location_info");
    disable_field("location_municipality");
    disable_field("location_province");
    disable_field("location_postal_code_1");
    disable_field("location_sub_province");
    disable_field("location_nation");
    disable_field("display_format_shared_id_list");
    disable_field("weekday_tinyint");
    disable_field("service_body_bigint");
    disable_field("virtual_meeting_additional_info");
    disable_field("phone_meeting_number");
    disable_field("virtual_meeting_link");
    disable_field("venue_type");
  }

  function clear_form() {
    clear_field("meeting_name");
    clear_field("start_time");
    clear_field("duration_time");
    clear_field("location_street");
    clear_field("location_text");
    clear_field("location_info");
    clear_field("location_municipality");
    clear_field("location_province");
    clear_field("location_postal_code_1");
    clear_field("location_sub_province");
    clear_field("location_nation");
    clear_field("first_name");
    clear_field("last_name");
    clear_field("contact_number_confidential");
    clear_field("email_address");
    clear_field("display_format_shared_id_list");
    clear_field("meeting_id");
    clear_field("additional_info");
    clear_field("meeting_searcher");
    clear_field("starter_kit_postal_address");
    clear_field("virtual_meeting_additional_info");
    clear_field("phone_meeting_number");
    clear_field("virtual_meeting_link");
    // placeholder for these select elements
    $("#group_relationship").val('');
    $("#venue_type").val('');
    $("#service_body_bigint").val('');
    // reset select2
    $('#display_format_shared_id_list').val(null).trigger('change');
    $('#meeting-searcher').val(null).trigger('change');
    // set email selector to no
    $("#add-email").val("no");
  }

  // meeting logic before selection is made
  $("#meeting_selector").hide();
  $("#meeting_content").hide();
  $("#other_reason").prop("required", false);

  $("#venue_type").on("change", function () {
    // show and hide the virtual meeting settings

    if (this.value == "1") {
      $("#virtual_meeting_settings").hide();
      $("#location_fields").show();
    } else {
      
      $("#virtual_meeting_settings").show();
      switch (this.value) {
        case "2":
          $("#location_fields").hide();
          break;
        case "3":
          $("#location_fields").show();
          break;
        case "4":
          $("#location_fields").show();
          break;
      }
    }

  });

  $("#update_reason").on("change", function () {
    // hide all the optional items
    $("#reason_new_text").hide();
    $("#reason_change_text").hide();
    $("#reason_close_text").hide();
    $("#starter_pack").hide();
    $("#meeting_selector").hide();
    // enable the meeting form
    $("#meeting_content").hide();
    $("#other_reason").prop("required", false);
    $("#additional_info").prop("required", false);
    disable_and_clear_highlighting();
    enable_edits();
    // enable items as required
    var reason = $(this).val();

    clear_form();
    switch (reason) {
      case "reason_new":
        $("#meeting_content").show();
        $("#personal_details").show();
        $("#meeting_details").show();
        $("#additional_info_div").show();
        $("#virtual_meeting_settings").hide();
        // display form instructions
        $("#instructions").html(
          "Please fill in the details of your new meeting, and then submit your update. <br><b>Note:</b> If your meeting convenes multiple times a week, please submit additional new meeting requests for each day you meet."
        );
        // new meeting has a starter pack
        if(bmltwf_fso_feature == 'display')
        {
          $("#starter_pack").show();
        }
        break;
      case "reason_change":
        // hide this until they've selected a meeting
        $("#meeting_content").hide();
        $("#personal_details").show();
        $("#meeting_details").show();
        $("#additional_info_div").show();

        // change meeting has a search bar
        $("#meeting_selector").show();

        break;
      case "reason_close":
        // hide this until they've selected a meeting
        $("#meeting_content").hide();
        $("#personal_details").show();
        $("#meeting_details").show();
        $("#additional_info_div").show();

        // close meeting has a search bar
        $("#meeting_selector").show();
        $("#additional_info").prop("required", true);

        break;
    }
  });

  $.fn.serializeObject = function () {
    var o = {};
    var a = this.serializeArray();
    $.each(a, function () {
      if (o[this.name]) {
        if (!o[this.name].push) {
          o[this.name] = [o[this.name]];
        }
        o[this.name].push(this.value || "");
      } else {
        o[this.name] = this.value || "";
      }
    });
    return o;
  };

  // form submit handler
  function real_submit_handler() {
    clear_notices();
    // in case we disabled this we want to send it now
    enable_field("service_body_bigint");

    // prevent displayable list from being submitted
    $("#display_format_shared_id_list").attr("disabled", "disabled");
    // turn the format list into a single string and move it into the submitted format_shared_id_list
    $("#format_shared_id_list").val($("#display_format_shared_id_list").val().join(","));
    // $("#format_shared_id_list").val($("#display_format_shared_id_list").val());

    // time control by default doesn't add extra seconds, so add them to be compaitble with BMLT
    if ($("#start_time").val().length === 5) {
      $("#start_time").val($("#start_time").val() + ":00");
    }

    // construct our duration
    var str = $("#duration_hours").val() + ":" + $("#duration_minutes").val() + ":00";
    put_field("duration_time", str);

    if($("#venue_type") == 4)
    {
      put_field("venue_type",1);
      put_field("temporarilyVirtual","true");
    }

    $.ajax({
      url: bmltwf_form_submit_url,
      method: "POST",
      data: JSON.stringify($("#meeting_update_form").serializeObject()),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      processData: false,
      beforeSend: function (xhr) {
        turn_on_spinner("#bmltwf-submit-spinner");
      },
    })
      .done(function (response) {
        turn_off_spinner("#bmltwf-submit-spinner");
        // notice_success(response,"bmltwf-error-message");
        $("#form_replace").replaceWith(response.form_html);
      })
      .fail(function (xhr) {
        turn_off_spinner("#bmltwf-submit-spinner");
        notice_error(xhr, "bmltwf-error-message");
      });
  }
});
