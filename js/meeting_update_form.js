"use strict";

var mdata = [];
var mtext = [];
var weekdays = ["none", "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

jQuery(document).ready(function ($) {
  var formatdata = [];
  Object.keys(wbw_bmlt_formats).forEach((key) => {
    formatdata.push({ text: "(" + wbw_bmlt_formats[key]["key_string"] + ")-" + wbw_bmlt_formats[key]["name_string"], id: key });
  });

  $("#display_format_shared_id_list").select2({
    placeholder: "Select from available formats",
    multiple: true,
    data: formatdata,
    width: "100%",
  });

  function update_meeting_list(wbw_service_bodies) {
    var search_results_address =
      wbw_bmlt_server_address +
      "client_interface/jsonp/?switcher=GetSearchResults&lang_enum=en&data_field_key=location_postal_code_1,duration_time," +
      "start_time,time_zone,weekday_tinyint,service_body_bigint,longitude,latitude,location_province,location_municipality," +
      "location_street,location_info,location_neighborhood,formats,format_shared_id_list,comments,location_sub_province,worldid_mixed," +
      "root_server_uri,id_bigint,venue_type,meeting_name,location_text,virtual_meeting_additional_info,contact_name_1,contact_phone_1," +
      "contact_email_1,contact_name_2,contact_phone_2,contact_email_2&" +
      wbw_service_bodies +
      "recursive=1&sort_keys=meeting_name";

    fetchJsonp(search_results_address)
      .then((response) => response.json())
      .then((data) => {
        mdata = data;

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

        function matchCustom(params, data) {
          // If there are no search terms, return all of the data
          if (String.prototype.trim(params.term) === "") {
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

        $("#meeting-searcher").select2({
          data: mtext,
          placeholder: "Select a meeting",
          allowClear: true,
          dropdownAutoWidth: true,
          matcher: matchCustom,
        });

        $("#meeting-searcher").on("select2:open", function (e) {
          $("input.select2-search__field").prop("placeholder", "Begin typing your meeting name");
        });

        $("#meeting-searcher").on("select2:select", function (e) {
          var data = e.params.data;
          var id = data.id;
          // set the weekday format
          $("#weekday_tinyint").val(mdata[id].weekday_tinyint);

          // fill in the other fields from bmlt
          put_field("meeting_name", mdata[id].meeting_name);
          put_field("start_time", mdata[id].start_time);
          put_field("location_street", mdata[id].location_street);
          put_field("location_text", mdata[id].location_text);
          put_field("location_info", mdata[id].location_info);
          put_field("location_municipality", mdata[id].location_municipality);
          put_field("location_province", mdata[id].location_province);
          put_field("location_postal_code_1", mdata[id].location_postal_code_1);
          put_field("display_format_shared_id_list", mdata[id].format_shared_id_list.split(","));

          // handle duration in the select dropdowns
          var durationarr = mdata[id].duration_time.split(":");
          // hoping we got both hours, minutes and seconds here
          if (durationarr.length == 3) {
            $("#duration_hours").val(durationarr[0]);
            $("#duration_minutes").val(durationarr[1]);
          }
          // handle service body in the select dropdown
          $("#service_body_bigint").val(mdata[id].service_body_bigint);

          // store the selected meeting ID away
          put_field("meeting_id", mdata[id].id_bigint);

          // tweak form instructions
          var reason = $("#update_reason").val();
          switch (reason) {
            case "reason_change":
              // display form instructions
              $("#instructions").text("We've retrieved the details below from our system. Please make any changes and then submit your update.");
              $("#meeting_content").show();
              disable_field("service_body_bigint");
              break;
            case "reason_close":
              // display form instructions
              $("#instructions").text("Verify you have selected the correct meeting, then add details to support the meeting close request in the Additional Information box");
              $("#meeting_content").show();
              disable_edits();
              break;
          }
        });
      });
  }

  $.ajax({
    url: wp_rest_base + wbw_admin_wbw_service_bodies_rest_route,
    dataType: "json",
    beforeSend: function (xhr) {
      xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
    },
  }).done(function (response) {
    var wbw_service_bodies = "";
    Object.keys(response).forEach((item) => {
      // console.log(response);
      var service_body_bigint = item;
      var service_area_name = response[item]["name"];
      var opt = new Option(service_area_name, service_body_bigint, false, false);
      $("#service_body_bigint").append(opt);
      wbw_service_bodies += "services[]=" + service_body_bigint + "&";
      update_meeting_list(wbw_service_bodies);
    });
  });

  $("#meeting_update_form").validate({
    submitHandler: function () {
      real_submit_handler();
    },
  });

  $("#starter_kit_required").on("change", function () {
    if (this.value == "yes") {
      $("#starter_kit_postal_address").show();
      $("#starter_kit_postal_address").prop("required", true);
    } else {
      $("#starter_kit_postal_address").hide();
      $("#starter_kit_postal_address").prop("required", false);
    }
  });

  function put_field(fieldname, value) {
    var field = "#" + fieldname;
    $(field).val(value);
    $(field).change();
  }

  function clear_field(fieldname, value) {
    var field = "#" + fieldname;
    $(field).val("");
    $(field).change();
  }

  function enable_field(fieldname) {
    var field = "#" + fieldname;
    $(field).prop("disabled", false);
    $(field).change();
  }

  function disable_field(fieldname) {
    var field = "#" + fieldname;
    $(field).prop("disabled", true);
    $(field).change();
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
    enable_field("display_format_shared_id_list");
    enable_field("weekday_tinyint");
    enable_field("service_body_bigint");
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
    disable_field("display_format_shared_id_list");
    disable_field("weekday_tinyint");
    disable_field("service_body_bigint");
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
    clear_field("first_name");
    clear_field("last_name");
    clear_field("contact_number_confidential");
    clear_field("email_address");
    clear_field("display_format_shared_id_list");
    clear_field("meeting_id");
    clear_field("meeting_searcher");
    // set email selector to no
    $("#add-email").val('no');

  }

  // meeting logic before selection is made
  $("#meeting_selector").hide();
  $("#meeting_content").hide();
  $("#other_reason_div").hide();
  $("#other_reason").prop("required", false);

  $("#update_reason").change(function () {
    // hide all the optional items
    $("#reason_new_text").hide();
    $("#reason_change_text").hide();
    $("#reason_close_text").hide();
    $("#reason_other_text").hide();
    $("#starter_pack").hide();
    $("#meeting_selector").hide();
    // enable the meeting form
    $("#meeting_content").hide();
    $("#other_reason_div").hide();
    $("#other_reason").prop("required", false);
    $("#additional_info").prop("required", false);

    enable_edits();
    // enable items as required
    var reason = $(this).val();
    // <p id="reason_close_text" style="display: none;">We've retrieved the details below from our system. Please add any other information and your contact details and then submit your update.

    switch (reason) {
      case "reason_new":
        clear_form();
        $("#meeting_content").show();
        $("#personal_details").show();
        $("#meeting_details").show();
        // display form instructions
        $("#instructions").text(
          "Please fill in the details of your new meeting, and whether your new meeting needs a starter kit provided, and then submit your update. Note: If your meeting meets multiple times a week, please submit additional new meeting requests for each day you meet."
        );
        // new meeting has a starter pack
        $("#starter_pack").show();
        break;
      case "reason_change":
        clear_form();
        // hide this until they've selected a meeting
        $("#meeting_content").hide();
        $("#personal_details").show();
        $("#meeting_details").show();
        // change meeting has a search bar
        $("#meeting_selector").show();

        break;
      case "reason_close":
        clear_form();
        // hide this until they've selected a meeting
        $("#meeting_content").hide();
        $("#personal_details").show();
        $("#meeting_details").show();

        // close meeting has a search bar
        $("#meeting_selector").show();
        $("#additional_info").prop("required", true);

        break;
      case "reason_other":
        clear_form();
        // display form instructions
        $("#instructions").text("Please let us know the details about your meeting change.");
        // other reason has a textarea
        $("#other_reason_div").show();
        $("#meeting_content").show();
        $("#personal_details").show();
        $("#meeting_details").hide();
        $("#other_reason").prop("required", true);
        break;
    }
  });

  // $("#meeting_update_form").submit(function (event) {

  // form submit handler
  function real_submit_handler() {
    // in case we disabled this we want to send it now
    enable_field("service_body_bigint");

    // prevent displayable list from being submitted
    $("#display_format_shared_id_list").attr('disabled','disabled')
    // turn the format list into a single string and move it into the submitted format_shared_id_list
    $("#format_shared_id_list").val($("#display_format_shared_id_list").val().join(","));
    
    // construct our duration
    var str = $("#duration_hours").val() + ":" + $("#duration_minutes").val() + ":00";
    put_field("duration_time", str);

    var url = wp_rest_base + wbw_form_submit;
    $.post(url, $("#meeting_update_form").serialize(), function (response) {
      // console.log("submitted");
      $("#form_replace").replaceWith(response.form_html);
    });
  }
});
