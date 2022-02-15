"use strict";

var mdata = [];
var mtext = [];

jQuery(document).ready(function ($) {
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

  function put_field_checked_index(fieldname, index, value) {
    var field = "#" + fieldname + "-" + index;
    $(field)[0].checked = value;
    $(field).trigger("change");
  }

  function check_if_changed(fieldname) {
    if (get_field("hidden_orig_" + fieldname) != get_field(fieldname)) {
      put_field("hidden_new_" + fieldname, get_field(fieldname));
    }
  }

  function check_if_changed_option(fieldname) {
    if (get_field("hidden_orig_" + fieldname) != get_field_optionval(fieldname)) {
      put_field("hidden_new_" + fieldname, get_field_optionval(fieldname));
    }
  }

  function add_checkbox_to_ul(name, inputvalue, container_id) {
    let container = $("#" + container_id + " ul");
    let next_id = $("#" + container_id + " ul li").length;
    let li = $("<li>");

    li.append($("<input />", { type: "checkbox", id: container_id + "-" + next_id, value: inputvalue }));
    li.append($("<label />", { for: container_id + "-" + next_id, text: name }));
    container.append(li);
  }

  // meeting logic
  $("#meeting_selector").hide();
  $("#other_reason").hide();
  $("#meeting_content").hide();

  $("#update_reason").change(function () {
    if ($(this).val() === "reason_new") {
      console.log("new");
      $("#meeting_selector").hide();
      $("#other_reason").hide();
      $("#meeting_content").show();
      $("#starter_pack").show();

    } else if ($(this).val() === "reason_change" || $(this).val() === "reason_close") {
      $("#meeting_selector").show();
      $("#other_reason").hide();
      $("#meeting_content").show();
      $("#starter_pack").hide();


    } else if ($(this).val() === "reason_other") {
      $("#meeting_selector").hide();
      $("#other_reason").show();
      $("#meeting_content").show();
      $("#starter_pack").hide();

    }
  });
  // populate format list
  fetchJsonp("https://na.org.au/main_server/client_interface/jsonp/?switcher=GetFormats")
    .then((response) => response.json())
    .then((data) => {
      let fdata = data;
      for (let i = 0, length = fdata.length; i < length; i++) {
        let name = "(" + fdata[i].key_string + ") " + fdata[i].name_string + " - " + fdata[i].description_string;
        add_checkbox_to_ul(name, fdata[i].key_string, "formats");
      }
    });

  fetchJsonp(
    "https://na.org.au/main_server/client_interface/jsonp/?switcher=GetSearchResults&lang_enum=en&data_field_key=location_postal_code_1,duration_time,start_time,time_zone,weekday_tinyint,service_body_bigint,longitude,latitude,location_province,location_municipality,location_street,location_info,location_neighborhood,formats,format_shared_id_list,comments,location_sub_province,worldid_mixed,root_server_uri,id_bigint,venue_type,meeting_name,location_text,virtual_meeting_additional_info,contact_name_1,contact_phone_1,contact_email_1,contact_name_2,contact_phone_2,contact_email_2&services[]=1&services[]=13&recursive=1&sort_keys=start_time"
  )
    .then((response) => response.json())
    .then((data) => {
      mdata = data;

      for (let i = 0, length = mdata.length; i < length; i++) {
        let str = mdata[i].meeting_name + " [ " + weekdays[mdata[i].weekday_tinyint - 1] + ", " + mdata[i].start_time + " ]";
        // let a = ;
        if ((mdata[i].location_municipality == "") && (mdata[i].location_province == "")) {
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
        put_field("hidden_orig_weekday", str);

        put_field("meeting_name", mdata[id].meeting_name);
        put_field("hidden_orig_meeting_name", mdata[id].meeting_name);

        put_field("start_time", mdata[id].start_time);
        put_field("hidden_orig_start_time", mdata[id].start_time);

        put_field("duration_time", mdata[id].duration_time);
        put_field("hidden_orig_duration_time", mdata[id].duration_time);

        put_field("location_street", mdata[id].location_street);
        put_field("hidden_orig_duration_time", mdata[id].duration_time);

        put_field("location_text", mdata[id].location_text);
        put_field("hidden_orig_duration_time", mdata[id].duration_time);

        put_field("location_info", mdata[id].location_info);
        put_field("hidden_orig_duration_time", mdata[id].duration_time);

        put_field("location_municipality", mdata[id].location_municipality);
        put_field("hidden_orig_duration_time", mdata[id].duration_time);

        put_field("location_province", mdata[id].location_province);
        put_field("hidden_orig_duration_time", mdata[id].duration_time);

        put_field("location_postal_code_1", mdata[id].location_postal_code_1);
        put_field("hidden_orig_duration_time", mdata[id].duration_time);

        put_field("comments", mdata[id].comments);
        put_field("hidden_orig_comments", mdata[id].comments);

        put_field("time_zone", mdata[id].time_zone);
        put_field("hidden_orig_time_zone", mdata[id].time_zone);

        // clear all the formats
        var formatlookup = {};
        for (var i = 0; i < $("#formats ul li").length; i++) {
          if (get_field_checked_index("formats", i) == true) {
            put_field_checked_index("formats", i, false);
          }
          formatlookup[$("#formats-" + i).attr("value")] = i;
        }
        // set the new formats
        var fmtspl = mdata[id].formats.split(",");
        for (var i = 0; i < fmtspl.length; i++) {
          var j = formatlookup[fmtspl[i]];
          put_field_checked_index("formats", j, true);
        }
        var str = "";
        for (var i = 0; i < $("#formats ul li").length; i++) {
          if (get_field_checked_index("formats", i)) {
            str = str + get_field_value_index("formats", i) + ", ";
          }
        }
        if (str != "") {
            str = str.slice(0, -2);
            put_field("hidden_orig_formats", str);
        }
      });
    });
});
