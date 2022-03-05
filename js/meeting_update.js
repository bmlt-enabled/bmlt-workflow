"use strict";

var mdata = [];
var mtext = [];

jQuery(document).ready(function ($) {

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
    event.preventDefault();
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
  });
});
