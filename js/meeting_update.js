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

  function get_field(fieldname) {
    var field = "#" + fieldname;
    return $(field)[0].value;
  }

  function get_field_checked_index(fieldname, index) {
    var field = "#" + fieldname + "-" + index;
    return $(field)[0].checked;
  }

  function get_field_value_index(fieldname, index) {
    var field = "#" + fieldname + "-" + index;
    return $(field)[0].value;
  }

  function get_field_optionval(fieldname) {
    var field = "#" + fieldname;
    return $(field).val();
  }

  function put_field(fieldname, value) {
    var field = "#" + fieldname;
    $(field)[0].value = value;
    $(field).trigger("change");
  }

  function clear_field(fieldname, value) {
    var field = "#" + fieldname;
    $(field)[0].value = "";
    $(field).trigger("change");
  }

  function put_field_checked_index(fieldname, index, value) {
    var field = "#" + fieldname + "-" + index;
    $(field)[0].checked = value;
    $(field).trigger("change");
  }

  function add_checkbox_row_to_table(formatcode, name, description, container_id) {
    let container = $("#" + container_id);
    let next_id = $("#" + container_id + " tr").length;
    let row = "<tr><td>";
    $("#" + container_id + " > tbody:last-child").append(
      "<tr>" +
        '<td><input type="checkbox" id="' +
        container_id +
        "-" +
        next_id +
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

  function clear_form()
  {
    clear_field("meeting_name");
    clear_field("start_time");
    clear_field("duration_time");
    clear_field("location_street");
    clear_field("location_text");
    clear_field("location_info");
    clear_field("location_municipality");
    clear_field("location_province");
    clear_field("location_postal_code_1");
    // clear_field("comments", mdata[id].comments);
    // clear_field("time_zone", mdata[id].time_zone);

    clear_field("id_bigint");

    // clear all the formats
    for (var i = 0; i < $("#format-table tr").length; i++) {
      if (get_field_checked_index("format-table", i) == true) {
        put_field_checked_index("format-table", i, false);
      }
    }
    // reset selector
    $("#meeting-searcher").val('').trigger('change')
  }

  // meeting logic
  $("#meeting_selector").hide();
  $("#other_reason").hide();
  $("#meeting_content").hide();

  $("#update_reason").change(function () {
    if ($(this).val() === "reason_new") {
      clear_form();
      $("#meeting_selector").hide();
      $("#other_reason").hide();
      $("#meeting_content").show();
      $("#starter_pack").show();
    } else if ($(this).val() === "reason_change" || $(this).val() === "reason_close") {
      clear_form();
      $("#meeting_selector").show();
      $("#other_reason").hide();
      $("#meeting_content").show();
      $("#starter_pack").hide();
    } else if ($(this).val() === "reason_other") {
      clear_form();
      $("#meeting_selector").hide();
      $("#other_reason").show();
      $("#meeting_content").show();
      $("#starter_pack").hide();
    }
  });

  // $bmlt_address = get_option('bmaw_bmlt_server_address');
  var format_results_address = bmaw_bmlt_server_address + "/client_interface/jsonp/?switcher=GetFormats";

  fetchJsonp(format_results_address)
    .then((response) => response.json())
    .then((data) => {
      let fdata = data;
      for (let i = 0, length = fdata.length; i < length; i++) {
        add_checkbox_row_to_table(fdata[i].key_string, fdata[i].name_string, fdata[i].description_string, "format-table");
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
    "&recursive=1&sort_keys=start_time";

  fetchJsonp(search_results_address)
    .then((response) => response.json())
    .then((data) => {
      mdata = data;

      for (let i = 0, length = mdata.length; i < length; i++) {
        let str = mdata[i].meeting_name + " [ " + weekdays[mdata[i].weekday_tinyint - 1] + ", " + mdata[i].start_time + " ]";
        // let a = ;
        if (mdata[i].location_municipality == "" && mdata[i].location_province == "") {
          str = str + "[ " + mdata[i].location_municipality + "," + mdata[i].location_province + " ]";
        }
        mtext[i] = { text: str, id: i };
      }

      $(".select2-ajax").select2({
        data: mtext,
        placeholder: "Select a meeting",
        allowClear: true,
        dropdownAutoWidth: true,
      });

      $("#meeting-searcher").one("select2:open", function (e) {
        $("input.select2-search__field").prop("placeholder", "Begin typing your meeting name");
      });

      $(".select2-ajax").on("select2:select", function (e) {
        var data = e.params.data;
        var id = data.id;

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
        for (var i = 0; i < $("#format-table tr").length; i++) {
          if (get_field_checked_index("format-table", i) == true) {
            put_field_checked_index("format-table", i, false);
          }
          formatlookup[$("#format-table-" + i).attr("value")] = i;
        }
        // set the new formats
        var fmtspl = mdata[id].formats.split(",");
        for (var i = 0; i < fmtspl.length; i++) {
          var j = formatlookup[fmtspl[i]];
          put_field_checked_index("format-table", j, true);
        }
      });
    });
  // form submit handler
  $("#meeting_update_form").submit(function (event) {
    console.log("Handler for .submit() called.");
    // meeting formats
    var str = "";
    for (var i = 0; i < $("#format-table tr").length; i++) {
      if (get_field_checked_index("format-table", i)) {
        str = str + get_field_value_index("format-table", i) + ",";
      }
    }
    if (str != "") {
      str = str.slice(0, -1);
      put_field("formats", str);
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
