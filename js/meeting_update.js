"use strict";

var mdata = [];
var mtext = [];

jQuery(document).ready(function ($) {
  $("#meeting_update_form").submit(function () {
    $.post("/flop/wp-json/bmaw-submission/v1/submissions", $.param($(this).serializeArray()), function (response) {
      alert(response);
    });
  });

  $("#meeting_update_form").validate();

  $("#starter_kit_required").on("change", function () {
    if (this.value == "yes") {
      $("#starter_kit_postal_address").show();
    } else {
      $("#starter_kit_postal_address").hide();
    }
  });

  var weekdays = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

  function get_field_checked_index(fieldname, index) {
    var field = "#" + fieldname + "-" + index;
    return $(field).prop('checked');
  }

  function put_field(fieldname, value) {
    var field = "#" + fieldname;
    $(field).val(value);
  }

  function clear_field(fieldname, value) {
    var field = "#" + fieldname;
    $(field).val('');
  }

  function enable_field(fieldname) {
    var field = "#" + fieldname;
    $(field).prop("disabled", false);
  }

  function enable_field_index(fieldname, index) {
    var field = "#" + fieldname + "-" + index;
    $(field).prop("disabled", false);
  }

  function disable_field(fieldname) {
    var field = "#" + fieldname;
    $(field).prop("disabled", true);
  }

  function disable_field_index(fieldname, index) {
    var field = "#" + fieldname + "-" + index;
    $(field).prop("disabled", true);
  }

  function put_field_checked_index(fieldname, index, value) {
    var field = "#" + fieldname + "-" + index;
    $(field).prop('checked', value);
  }

  function add_checkbox_row_to_table(formatcode, id, name, description, container_id) {
    let container = $("#" + container_id);
    let row = "<tr><td>";
    $("#" + container_id + " > tbody:last-child").append(
      "<tr>" +
        '<td><input type="checkbox" id="' + container_id + '-' + id +
        '" value="' +
        formatcode +
        '"></input></td>' +
        "<td>(" +
        formatcode +
        ")</td>" +
        "<td>" +
        name +
        "</td>" +
        "<td>" +
        description +
        "</td>" +
        "</tr>"
    );
  }

  function enable_edits() {
    enable_field("meeting_name");
    enable_field("start_time");
    enable_field("duration_time");
    enable_field("location_street");
    enable_field("location_text");
    enable_field("location_info");
    enable_field("location_municipality");
    enable_field("location_province");
    enable_field("location_postal_code_1");
    for (var i = 0; i < $("#format-table tr").length; i++) {
      enable_field_index("format-table", i);
    }
    for (var i = 0; i < 7; i++) {
      enable_field_index("weekday", i);
    }
  }

  function disable_edits() {
    disable_field("meeting_name");
    disable_field("start_time");
    disable_field("duration_time");
    disable_field("location_street");
    disable_field("location_text");
    disable_field("location_info");
    disable_field("location_municipality");
    disable_field("location_province");
    disable_field("location_postal_code_1");
    for (var i = 0; i < $("#format-table tr").length; i++) {
      disable_field_index("format-table", i);
    }
    for (var i = 0; i < 7; i++) {
      disable_field_index("weekday", i);
    }
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

    // clear_field("comments", mdata[id].comments);
    // clear_field("time_zone", mdata[id].time_zone);

    clear_field("id_bigint");
    // reset email checkbox
    put_field_checked_index("add_email", 0, true);

    // clear all the formats
    $("#format-table tr").each(function(){
      let inpid = $(this).find("td input").attr('id').replace('format-table-','');
      put_field_checked_index("format-table", inpid, false);
    });

    // clear weekdays
    for (var i = 0; i < 7; i++) {
      put_field_checked_index("weekday", i, false);
    }

    // reset selector
    $("#meeting-searcher").val("").trigger("change");
  }

  // meeting logic before selection is made
  $("#meeting_selector").hide();
  $("#meeting_content").hide();
  $("#other_reason").hide();

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
    $("#other_reason").hide();

    enable_edits();
    // enable items as required
    var reason = $(this).val();
    switch (reason) {
      case "reason_new":
        clear_form();
        $("#meeting_content").show();
        // display form instructions
        $("#reason_new_text").show();
        // new meeting has a starter pack
        $("#starter_pack").show();
        break;
      case "reason_change":
        clear_form();
        // change meeting has a search bar
        $("#meeting_selector").show();

        break;
      case "reason_close":
        clear_form();
        // close meeting has a search bar
        $("#meeting_selector").show();
        break;
      case "reason_other":
        clear_form();
        // display form instructions
        $("#reason_other_text").show();
        // other reason has a textarea
        $("#other_reason").show();
        break;
    }
  });

  // $bmlt_address = get_option('bmaw_bmlt_server_address');
  // var format_results_address = bmaw_bmlt_server_address + "/client_interface/jsonp/?switcher=GetFormats";

  // fetchJsonp(format_results_address)
  //   .then((response) => response.json())
  //   .then((data) => {
  //     let fdata = data;
  //     for (let i = 0, length = fdata.length; i < length; i++) {
  //       add_checkbox_row_to_table(fdata[i].key_string, fdata[i].name_string, fdata[i].description_string, "format-table");
  //     }
  //   });

  // FIX - hardwired

  var g_format_object_array = [
    { id: 40, key: "AB", name: "Ask-It-Basket", description: "A topic is chosen from suggestions placed into a basket.", worldid_mixed: "QA" },
    { id: 38, key: "Ag", name: "Agnostic", description: "Intended for people with varying degrees of Faith.", worldid_mixed: "" },
    { id: 1, key: "B", name: "Beginners", description: "This meeting is focused on the needs of new members of NA.", worldid_mixed: "BEG" },
    { id: 36, key: "BK", name: "Book Study", description: "Approved N.A. Books", worldid_mixed: "LIT" },
    { id: 2, key: "BL", name: "Bi-Lingual", description: "This Meeting can be attended by speakers of English and another language.", worldid_mixed: "LANG" },
    { id: 3, key: "BT", name: "Basic Text", description: "This meeting is focused on discussion of the Basic Text of Narcotics Anonymous.", worldid_mixed: "BT" },
    {
      id: 4,
      key: "C",
      name: "Closed",
      description: "This meeting is closed to non-addicts. You should attend only if you believe that you may have a problem with substance abuse.",
      worldid_mixed: "CLOSED",
    },
    { id: 5, key: "CH", name: "Closed Holidays", description: "This meeting gathers in a facility that is usually closed on holidays.", worldid_mixed: "CH" },
    { id: 6, key: "CL", name: "Candlelight", description: "This meeting is held by candlelight.", worldid_mixed: "CAN" },
    { id: 45, key: "CP", name: "Concepts", description: "This meeting is focused on discussion of the twelve concepts of NA.", worldid_mixed: "CPT" },
    { id: 7, key: "CS", name: "Children under Supervision", description: "Well-behaved, supervised children are welcome.", worldid_mixed: "" },
    { id: 44, key: "CW", name: "Children Welcome", description: "Children are welcome at this meeting.", worldid_mixed: "CW" },
    { id: 8, key: "D", name: "Discussion", description: "This meeting invites participation by all attendees.", worldid_mixed: "DISC" },
    { id: 47, key: "ENG", name: "English speaking", description: "This Meeting can be attended by speakers of English.", worldid_mixed: "LANG" },
    { id: 9, key: "ES", name: "Español", description: "This meeting is conducted in Spanish.", worldid_mixed: "LANG" },
    { id: 39, key: "FD", name: "Five and Dime", description: "Discussion of the Fifth Step and the Tenth Step", worldid_mixed: "" },
    { id: 46, key: "FIN", name: "Finnish", description: "Finnish speaking meeting", worldid_mixed: "LANG" },
    { id: 10, key: "GL", name: "Gay/Lesbian/Transgender", description: "This meeting is focused on the needs of gay, lesbian and transgender members of NA.", worldid_mixed: "GL" },
    { id: 52, key: "GP", name: "Guiding Principles", description: "This is a discussion of the NA book Guiding Principles - The Spirit of Our Traditions.", worldid_mixed: "GP" },
    { id: 56, key: "HY", name: "Hybrid Meeting", description: "Meets Virtually and In-person", worldid_mixed: "HYBR" },
    { id: 11, key: "IL", name: "Illness", description: "This meeting is focused on the needs of NA members with chronic illness.", worldid_mixed: "" },
    { id: 12, key: "IP", name: "Informational Pamphlet", description: "This meeting is focused on discussion of one or more Informational Pamphlets.", worldid_mixed: "IP" },
    { id: 13, key: "IW", name: "It Works -How and Why", description: "This meeting is focused on discussion of the It Works -How and Why text.", worldid_mixed: "IW" },
    { id: 14, key: "JT", name: "Just for Today", description: "This meeting is focused on discussion of the Just For Today text.", worldid_mixed: "JFT" },
    { id: 49, key: "L/R", name: "Lithuanian/Russian", description: "Lithuanian/Russian Speaking Meeting", worldid_mixed: "LANG" },
    { id: 51, key: "LC", name: "Living Clean", description: "This is a discussion of the NA book Living Clean -The Journey Continues.", worldid_mixed: "LC" },
    { id: 15, key: "M", name: "Men", description: "This meeting is meant to be attended by men only.", worldid_mixed: "M" },
    { id: 41, key: "ME", name: "Meditation", description: "This meeting encourages its participants to engage in quiet meditation.", worldid_mixed: "MED" },
    { id: 16, key: "NC", name: "No Children", description: "Please do not bring children to this meeting.", worldid_mixed: "NC" },
    { id: 37, key: "NS", name: "No Smoking", description: "Smoking is not allowed at this meeting.", worldid_mixed: "NS" },
    { id: 17, key: "O", name: "Open", description: "This meeting is open to addicts and non-addicts alike. All are welcome.", worldid_mixed: "OPEN" },
    { id: 35, key: "OE", name: "Open-Ended", description: "No fixed duration. The meeting continues until everyone present has had a chance to share.", worldid_mixed: "" },
    { id: 48, key: "PER", name: "Persian", description: "Persian speaking meeting", worldid_mixed: "LANG" },
    { id: 18, key: "Pi", name: "Pitch", description: "This meeting has a format that consists of each person who shares picking the next person.", worldid_mixed: "" },
    { id: 43, key: "QA", name: "Question and Answer", description: "Attendees may ask questions and expect answers from Group members.", worldid_mixed: "QA" },
    { id: 42, key: "RA", name: "Restricted Attendance", description: "This facility places restrictions on attendees.", worldid_mixed: "RA" },
    { id: 19, key: "RF", name: "Rotating Format", description: "This meeting has a format that changes for each meeting.", worldid_mixed: "VAR" },
    { id: 20, key: "Rr", name: "Round Robin", description: "This meeting has a fixed sharing order (usually a circle.)", worldid_mixed: "" },
    { id: 21, key: "SC", name: "Security Cameras", description: "This meeting is held in a facility that has security cameras.", worldid_mixed: "" },
    { id: 22, key: "SD", name: "Speaker/Discussion", description: "This meeting is lead by a speaker, then opened for participation by attendees.", worldid_mixed: "S-D" },
    { id: 23, key: "SG", name: "Step Working Guide", description: "This meeting is focused on discussion of the Step Working Guide text.", worldid_mixed: "SWG" },
    { id: 24, key: "SL", name: "ASL", description: "This meeting provides an American Sign Language (ASL) interpreter for the deaf.", worldid_mixed: "" },
    { id: 26, key: "So", name: "Speaker Only", description: "This meeting is a speaker-only meeting. Other attendees do not participate in the discussion.", worldid_mixed: "SPK" },
    { id: 27, key: "St", name: "Step", description: "This meeting is focused on discussion of the Twelve Steps of NA.", worldid_mixed: "STEP" },
    { id: 55, key: "TC", name: "Temporarily Closed Facility", description: "Facility is Temporarily Closed", worldid_mixed: "TC" },
    { id: 28, key: "Ti", name: "Timer", description: "This meeting has sharing time limited by a timer.", worldid_mixed: "" },
    { id: 29, key: "To", name: "Topic", description: "This meeting is based upon a topic chosen by a speaker or by group conscience.", worldid_mixed: "TOP" },
    { id: 30, key: "Tr", name: "Tradition", description: "This meeting is focused on discussion of the Twelve Traditions of NA.", worldid_mixed: "TRAD" },
    { id: 31, key: "TW", name: "Traditions Workshop", description: "This meeting engages in detailed discussion of one or more of the Twelve Traditions of N.A.", worldid_mixed: "TRAD" },
    { id: 54, key: "VM", name: "Virtual Meeting", description: "Meets Virtually", worldid_mixed: "VM" },
    { id: 32, key: "W", name: "Women", description: "This meeting is meant to be attended by women only.", worldid_mixed: "W" },
    { id: 33, key: "WC", name: "Wheelchair", description: "This meeting is wheelchair accessible.", worldid_mixed: "WCHR" },
    { id: 34, key: "YP", name: "Young People", description: "This meeting is focused on the needs of younger members of NA.", worldid_mixed: "Y" },
  ];

  // insert all the formats
  for (let i = 0, length = g_format_object_array.length; i < length; i++) {
    add_checkbox_row_to_table(g_format_object_array[i].key, g_format_object_array[i].id,g_format_object_array[i].name, g_format_object_array[i].description, "format-table");
  }

  var search_results_address =
    bmaw_bmlt_server_address +
    "/client_interface/jsonp/?switcher=GetSearchResults&lang_enum=en&data_field_key=location_postal_code_1,duration_time," +
    "start_time,time_zone,weekday_tinyint,service_body_bigint,longitude,latitude,location_province,location_municipality," +
    "location_street,location_info,location_neighborhood,formats,format_shared_id_list,comments,location_sub_province,worldid_mixed," +
    "root_server_uri,id_bigint,venue_type,meeting_name,location_text,virtual_meeting_additional_info,contact_name_1,contact_phone_1," +
    "contact_email_1,contact_name_2,contact_phone_2,contact_email_2&" +
    bmaw_service_areas +
    "&recursive=1&sort_keys=meeting_name";

  fetchJsonp(search_results_address)
    .then((response) => response.json())
    .then((data) => {
      mdata = data;

      for (let i = 0, length = mdata.length; i < length; i++) {
        let str = mdata[i].meeting_name + " [ " + weekdays[mdata[i].weekday_tinyint - 1] + ", " + mdata[i].start_time + " ]";
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
        if ($.trim(params.term) === "") {
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

      $(".select2-ajax").select2({
        data: mtext,
        placeholder: "Select a meeting",
        allowClear: true,
        dropdownAutoWidth: true,
        matcher: matchCustom,
      });

      $("#meeting-searcher").one("select2:open", function (e) {
        $("input.select2-search__field").prop("placeholder", "Begin typing your meeting name");
      });

      $(".select2-ajax").on("select2:select", function (e) {
        var data = e.params.data;
        var id = data.id;
        // set the weekday format
        var str = "";
        for (var i = 0; i < 7; i++) {
          if (i == mdata[id].weekday_tinyint - 1) {
            if (get_field_checked_index("weekday", i) != true) {
              put_field_checked_index("weekday", i, true);
            }
          } else if (get_field_checked_index("weekday", i)) {
            put_field_checked_index("weekday", i, false);
          }

          if (get_field_checked_index("weekday", i)) {
            str = str + weekdays[i] + ", ";
          }
        }
        if (str != "") {
          str = str.slice(0, -2);
        }
        // fill in the other fields from bmlt
        put_field("meeting_name", mdata[id].meeting_name);
        put_field("start_time", mdata[id].start_time);
        put_field("duration_time", mdata[id].duration_time);
        put_field("location_street", mdata[id].location_street);
        put_field("location_text", mdata[id].location_text);
        put_field("location_info", mdata[id].location_info);
        put_field("location_municipality", mdata[id].location_municipality);
        put_field("location_province", mdata[id].location_province);
        put_field("location_postal_code_1", mdata[id].location_postal_code_1);
        // put_field("comments", mdata[id].comments);
        // put_field("time_zone", mdata[id].time_zone);

        // store the selected meeting ID away
        put_field("id_bigint", mdata[id].id_bigint);

        // clear all the formats
        var formatlookup = {};
        for (let i = 0, length = g_format_object_array.length; i < length; i++) {
          put_field_checked_index("format-table", g_format_object_array[i].id, false);
        }
        // set the new formats
        var fmtspl = mdata[id].format_shared_id_list.split(",");
        for (var i = 0; i < fmtspl.length; i++) {
          put_field_checked_index("format-table", fmtspl[i], true);
        }

        // tweak form instructions
        var reason = $("#update_reason").val();
        switch (reason) {
          case "reason_change":
            $("#reason_change_text").show();
            $("#meeting_content").show();

            break;
          case "reason_close":
            $("#reason_close_text").show();
            $("#meeting_content").show();

            disable_edits();
            break;
        }
      });
    });
  // form submit handler
  $("#meeting_update_form").submit(function (event) {
    console.log("Handler for .submit() called.");
    // meeting formats
    var str = "";
    // $("#format-table tr").each(function(){
    //   var i = $(this).find("input").val('id');
    //   console.log(i);
    // });
    $("#format-table tr").each(function(){
      let inpid = $(this).find("td input").attr('id').replace('format-table-','');
      if (get_field_checked_index("format-table", inpid)) {
        str = str + inpid + ",";
      }
  });

    //  i++) {
    //   if (get_field_checked_index("format-table", i)) {
    //     str = str + get_field_value_index("format-table", i) + ",";
    //   }
    // }
    if (str != "") {
      str = str.slice(0, -1);
      put_field("format_shared_id_list", str);
    }

    var str = "";
    // weekdays
    for (var i = 0; i < 7; i++) {
      if (get_field_checked_index("weekday", i) == true) {
        str = str + weekdays[i] + ", ";
      }
    }
    if (str != "") {
      str = str.slice(0, -2);
      put_field("weekday", str);
    }
  });
});
