"use strict";

var weekdays = ["none", "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

jQuery(document).ready(function ($) {
  // set up our format selector
  var formatdata = [];
  var hybrid_formatid = "";
  var virtual_formatid = "";

  Object.keys(wbw_bmlt_formats).forEach((key) => {
    formatdata.push({ text: "(" + wbw_bmlt_formats[key]["key_string"] + ")-" + wbw_bmlt_formats[key]["name_string"], id: key });
    if (wbw_bmlt_formats[key]["key_string"] === "HY") {
      hybrid_formatid = key;
    } else if (wbw_bmlt_formats[key]["key_string"] === "VM") {
      virtual_formatid = key;
    }
  });

  $("#display_format_shared_id_list").select2({
    placeholder: "Select from available formats",
    multiple: true,
    data: formatdata,
    selectionCssClass: ":all:",
    width: "100%",
  });

  // hide / show / required our optional fields
  switch (wbw_optional_location_nation) {
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
      $("#location_nation_label").append('<span class="wbw-required-field"> *</span>');
      break;
  }

  switch (wbw_optional_location_sub_province) {
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
      $("#location_sub_province_label").append('<span class="wbw-required-field"> *</span>');
      break;
  }

  function update_meeting_list(wbw_service_bodies) {
    var search_results_address =
      wbw_bmlt_server_address +
      "client_interface/jsonp/?switcher=GetSearchResults&lang_enum=en&data_field_key=location_postal_code_1,duration_time," +
      "start_time,time_zone,weekday_tinyint,service_body_bigint,longitude,latitude,location_province,location_municipality," +
      "location_street,location_info,location_neighborhood,formats,format_shared_id_list,comments,location_sub_province,worldid_mixed," +
      "root_server_uri,id_bigint,venue_type,meeting_name,location_text,virtual_meeting_additional_info,virtual_meeting_link,phone_meeting_number,contact_name_1,contact_phone_1," +
      "contact_email_1,contact_name_2,contact_phone_2,contact_email_2&" +
      wbw_service_bodies +
      "recursive=1&sort_keys=meeting_name";

    // // https://na.org.au/main_server/client_interface/jsonp/?switcher=GetSearchResults&get_used_formats
    // &lang_enum=en&data_field_key=location_postal_code_1,duration_time,start_time,time_zone,weekday_tinyint,service_body_bigint,
    // location_province,location_municipality,location_street,location_info,location_neighborhood,formats,format_shared_id_list,comments,
    // location_sub_province,worldid_mixed,root_server_uri,id_bigint,venue_type,meeting_name,location_text,virtual_meeting_additional_info,virtual_meeting_link,phone_meeting_number,
    // latitude,longitude,contact_name_1,contact_phone_1,contact_email_1,contact_name_2,contact_phone_2,contact_email_2&services[]=1&recursive=1&sort_keys=start_time

    fetchJsonp(search_results_address)
      .then((response) => response.json())
      .then((mdata) => create_meeting_searcher(mdata));
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
    });
    update_meeting_list(wbw_service_bodies);
  });

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
      placeholder: "Select a meeting",
      allowClear: true,
      dropdownAutoWidth: true,
      matcher: matchCustom,
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

      var meeting_formats = mdata[id].format_shared_id_list.split(",");
      // fill in the other fields from bmlt
      put_field("meeting_name", mdata[id].meeting_name);
      put_field("start_time", mdata[id].start_time);
      put_field("location_street", mdata[id].location_street);
      put_field("location_text", mdata[id].location_text);
      put_field("location_info", mdata[id].location_info);
      put_field("location_municipality", mdata[id].location_municipality);
      put_field("location_province", mdata[id].location_province);
      put_field("location_sub_province", mdata[id].location_sub_province);
      put_field("location_nation", mdata[id].location_nation);
      put_field("location_postal_code_1", mdata[id].location_postal_code_1);
      put_field("display_format_shared_id_list", meeting_formats);
      put_field("virtual_meeting_additional_info", mdata[id].virtual_meeting_additional_info);
      put_field("phone_meeting_number", mdata[id].phone_meeting_number);
      put_field("virtual_meeting_link", mdata[id].virtual_meeting_link);

      // handle duration in the select dropdowns
      var durationarr = mdata[id].duration_time.split(":");
      // hoping we got both hours, minutes and seconds here
      if (durationarr.length == 3) {
        $("#duration_hours").val(durationarr[0]);
        $("#duration_minutes").val(durationarr[1]);
      }
      // handle service body in the select dropdown
      $("#service_body_bigint").val(mdata[id].service_body_bigint);

      // handle virtual meeting type in the virtual meeting dropdown
      var virtual_format = "none";
      if (virtual_formatid in meeting_formats) {
        virtual_format = "hybrid";
      } else if (hybrid_formatid in meeting_formats) {
        virtual_format = "virtual";
      }
      // meeting_formats.forEach((item, index) => {
      //   if(wbw_bmlt_formats[item]['key_string'] === 'HY')
      //   {
      //     virtual_format='hybrid';
      //   }
      //   else if(wbw_bmlt_formats[item]['key_string'] === 'VM')
      //   {
      //     virtual_format='virtual';
      //   }
      // });
      // doesn't handle if they have both selected in BMLT
      $("#virtual_hybrid_select").val(virtual_format);
      if (virtual_format === "none") {
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
    $(".meeting-input").off("input.wbw-highlight");
    $("#display_format_shared_id_list").off("change.wbw-highlight");
    // remove the highlighting css
    $(".meeting-input").removeClass("wbw-changed");
    $(".display_format_shared_id_list-select2").removeClass("wbw-changed");
  }

  function enable_highlighting() {
    // add highlighting trigger for general input fields
    $(".meeting-input").on("input.wbw-highlight", function () {
      $(this).addClass("wbw-changed");
    });
    // add highlighting trigger for select2
    $("#display_format_shared_id_list").on("change.wbw-highlight", function () {
      $(".display_format_shared_id_list-select2").addClass("wbw-changed");
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
    disable_field("format_shared_id_list");
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
    clear_field("location_sub_province");
    clear_field("location_nation");
    clear_field("first_name");
    clear_field("last_name");
    clear_field("contact_number_confidential");
    clear_field("email_address");
    clear_field("display_format_shared_id_list");
    clear_field("meeting_id");
    clear_field("meeting_searcher");
    // set email selector to no
    $("#add-email").val("no");
  }

  // meeting logic before selection is made
  $("#meeting_selector").hide();
  $("#meeting_content").hide();
  $("#other_reason_div").hide();
  $("#other_reason").prop("required", false);
  $("#personal_details").attr("class", "form-grid-col2-1");

  $("#virtual_hybrid_select").on("change", function () {
    // show and hide the virtual meeting settings, and adjust formats as required
    var oldarr = $("#display_format_shared_id_list").val();
    // strip out all the virtual/hybrids first
    var arr = oldarr.filter(function (value, index, a) {
      return value != virtual_formatid && value != hybrid_formatid;
    });

    if (this.value == "none") {
      $("#virtual_meeting_settings").hide();
    } else {
      $("#virtual_meeting_settings").show();
      if (this.value === "virtual") {
        arr.push(virtual_formatid);
      } else if (this.value === "hybrid") {
        arr.push(hybrid_formatid);
      }
      $("#display_format_shared_id_list").val(arr).trigger("change");
    }
  });

  $("#update_reason").on("change", function () {
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
    $("#personal_details").attr("class", "form-grid-col2-1");
    disable_and_clear_highlighting();
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
        $("#additional_info_div").show();
        $("#virtual_meeting_settings").hide();
        // display form instructions
        $("#instructions").html(
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
        $("#additional_info_div").show();

        // change meeting has a search bar
        $("#meeting_selector").show();

        break;
      case "reason_close":
        clear_form();
        // hide this until they've selected a meeting
        $("#meeting_content").hide();
        $("#personal_details").show();
        $("#meeting_details").show();
        $("#additional_info_div").show();

        // close meeting has a search bar
        $("#meeting_selector").show();
        $("#additional_info").prop("required", true);

        break;
      case "reason_other":
        clear_form();
        // display form instructions
        $("#instructions").html("");
        // other reason has a textarea
        $("#other_reason_div").show();
        $("#meeting_content").show();
        $("#personal_details").attr("class", "form-grid-col1");
        $("#personal_details").show();
        $("#meeting_details").hide();
        $("#other_reason").prop("required", true);
        $("#additional_info_div").hide();
        break;
    }
  });

  // form submit handler
  function real_submit_handler() {
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

    var url = wp_rest_base + wbw_form_submit;
    $.post(url, $("#meeting_update_form").serialize(), function (response) {
      // console.log("submitted");
      $("#form_replace").replaceWith(response.form_html);
    });
  }
});
