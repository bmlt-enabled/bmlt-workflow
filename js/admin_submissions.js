function dismiss_notice(element) {
  jQuery(element)
    .parent()
    .slideUp("normal", function () {
      jQuery(this).remove();
    });
  return false;
}

function mysql2localdate(data) {
  var t = data.split(/[- :]/);
  var d = new Date(Date.UTC(t[0], t[1] - 1, t[2], t[3], t[4], t[5]));
  var ds = d.getFullYear() + "-" + ("0" + (d.getMonth() + 1)).slice(-2) + "-" + ("0" + d.getDate()).slice(-2) + " " + ("0" + d.getHours()).slice(-2) + ":" + ("0" + d.getMinutes()).slice(-2);
  return ds;
}

var wbw_changedata = {};

jQuery(document).ready(function ($) {
  weekdays = ["Error", "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

  // hide / show / required our optional fields
  switch (wbw_optional_location_nation) {
    case "hidden":
    case "":
      $("#optional_location_nation").hide();
      break;
    case "display":
    case "displayrequired":
      $("#optional_location_nation").show();
      break;
  }

  switch (wbw_optional_location_sub_province) {
    case "hidden":
    case "":
      $("#optional_location_sub_province").hide();
      break;
    case "display":
    case "displayrequired":
      $("#optional_location_sub_province").show();
      break;
  }

  function add_highlighted_changes_to_quickedit(wbw_requested) {
    // fill in and highlight the changes - use extend to clone
    changes_requested = $.extend(true, {}, wbw_requested);

    if ("format_shared_id_list" in changes_requested) {
      changes_requested["format_shared_id_list"] = changes_requested["format_shared_id_list"].split(",");
    }

    if ("duration_time" in changes_requested) {
      var durationarr = changes_requested["duration_time"].split(":");
      // hoping we got hours, minutes and seconds here
      if (durationarr.length == 3) {
        changes_requested["duration_hours"]= durationarr[0];
        changes_requested["duration_minutes"]= durationarr[1];
        delete changes_requested["duration_time"];
      }
  
    }

    Object.keys(changes_requested).forEach((element) => {
      if ($("#quickedit_" + element).length) {
        if (element === "format_shared_id_list") {
          $(".quickedit_format_shared_id_list-select2").addClass("wbw-changed");
        } else {
          $("#quickedit_" + element).addClass("wbw-changed");
        }
        $("#quickedit_" + element).val(changes_requested[element]);
        $("#quickedit_" + element).trigger("change");
      }
    });
    // trigger adding of highlights when input changes
    $(".quickedit-input").on("input.wbw-highlight", function () {
      $(this).addClass("wbw-changed");
    });
    $("#quickedit_format_shared_id_list").on("change.wbw-highlight", function () {
      $(".quickedit_format_shared_id_list-select2").addClass("wbw-changed");
    });
  }

  function populate_and_open_quickedit(id) {
    // clear quickedit

    // remove our change handler
    $(".quickedit-input").off("input.wbw-highlight");
    $("#quickedit_format_shared_id_list").off("change.wbw-highlight");
    // remove the highlighting
    $(".quickedit-input").removeClass("wbw-changed");
    $(".quickedit_format_shared_id_list-select2").removeClass("wbw-changed");

    // remove any content from the input fields
    $(".quickedit-input").val("");

    // fill quickedit

    // if it's a meeting change, fill from bmlt first
    if (wbw_changedata[id].submission_type == "reason_change") {
      var meeting_id = wbw_changedata[id]["meeting_id"];
      var search_results_address =
        wbw_bmlt_server_address +
        "client_interface/jsonp/?switcher=GetSearchResults&meeting_key=id_bigint&meeting_key_value=" +
        meeting_id +
        "&lang_enum=en&&recursive=1&sort_keys=start_time";

      fetchJsonp(search_results_address)
        .then((response) => response.json())
        .then((data) => {
          // fill in all the bmlt stuff
          var item = data[0];
          if (!Object.keys(data).length) {
            var a = {};
            a["responseJSON"] = {};
            a["responseJSON"]["message"] = "Error retrieving BMLT data";
            notice_error(a,"wbw-error-message");
          } else {
            // split up the duration so we can use it in the select
            if ("duration_time" in item) {
              var durationarr = item["duration_time"].split(":");
              // hoping we got hours, minutes and seconds here
              if (durationarr.length == 3) {
                $("#quickedit_duration_hours").val(durationarr[0]);
                $("#quickedit_duration_minutes").val(durationarr[1]);
              }
            }
            // split up the format list so we can use it in the select
            if ("format_shared_id_list" in item) {
              item["format_shared_id_list"] = item["format_shared_id_list"].split(",");
            }

            Object.keys(item).forEach((element) => {
              if ($("#quickedit_" + element).length) {
                $("#quickedit_" + element).val(item[element]);
                $("#quickedit_" + element).trigger("change");
              }
            });
            add_highlighted_changes_to_quickedit(wbw_changedata[id].changes_requested);
            $("#wbw_submission_quickedit_dialog").data("id", id).dialog("open");
          }
        });
    } else if (wbw_changedata[id].submission_type == "reason_new") {
      add_highlighted_changes_to_quickedit(wbw_changedata[id].changes_requested);
      $("#wbw_submission_quickedit_dialog").data("id", id).dialog("open");
    }
  }

  function clear_notices() {
    jQuery(".notice-dismiss").each(function (i, e) {
      dismiss_notice(e);
    });
  }

  // default close meeting radio button
  if (wbw_default_closed_meetings === "delete") {
    $("#close_delete").prop("checked", true);
  } else {
    $("#close_unpublish").prop("checked", true);
  }

  var formatdata = [];
  Object.keys(wbw_bmlt_formats).forEach((key) => {
    formatdata.push({ text: "(" + wbw_bmlt_formats[key]["key_string"] + ")-" + wbw_bmlt_formats[key]["name_string"], id: key });
  });

  $("#quickedit_format_shared_id_list").select2({
    placeholder: "Select from available formats",
    multiple: true,
    width: "100%",
    data: formatdata,
    selectionCssClass: ":all:",
    dropdownParent: $("#wbw_submission_quickedit_dialog"),
  });
  $("#quickedit_format_shared_id_list").trigger("change");

  var datatable = $("#dt-submission").DataTable({
    dom: "Bfrtip",
    select: true,
    searching: false,
    buttons: [
      {
        name: "approve",
        text: "Approve",
        enabled: false,
        action: function (e, dt, button, config) {
          var id = dt.row(".selected").data()["id"];
          var reason = dt.row(".selected").data()["submission_type"];
          if (reason === "reason_close") {
            // clear text area from before
            $("#wbw_submission_approve_close_dialog_textarea").val("");
            $("#wbw_submission_approve_close_dialog").data("id", id).dialog("open");
          } else {
            // clear text area from before
            $("#wbw_submission_approve_dialog_textarea").val("");
            $("#wbw_submission_approve_dialog").data("id", id).dialog("open");
          }
        },
      },
      {
        name: "reject",
        text: "Reject",
        enabled: false,
        action: function (e, dt, button, config) {
          var id = dt.row(".selected").data()["id"];
          // clear text area from before
          $("#wbw_submission_reject_dialog_textarea").val("");
          $("#wbw_submission_reject_dialog").data("id", id).dialog("open");
        },
      },
      {
        name: "quickedit",
        text: "QuickEdit",
        extend: "selected",
        action: function (e, dt, button, config) {
          var id = dt.row(".selected").data()["id"];
          populate_and_open_quickedit(id);
        },
      },
      {
        name: "delete",
        text: "Delete",
        extend: "selected",
        action: function (e, dt, button, config) {
          var id = dt.row(".selected").data()["id"];
          $("#wbw_submission_delete_dialog").data("id", id).dialog("open");
        },
      },
    ],
    ajax: {
      url: wbw_admin_submissions_rest_url,
      beforeSend: function (xhr) {
        xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
      },
      dataSrc: function (json) {
        wbw_changedata = {};
        for (var i = 0, ien = json.length; i < ien; i++) {
          json[i]["changes_requested"]["submission_type"] = json[i]["submission_type"];
          // store the json for us to use in quick editor
          wbw_changedata[json[i]["id"]] = json[i];
        }
        return json;
      },
    },
    columns: [
      {
        name: "id",
        data: "id",
      },
      {
        name: "submitter_name",
        data: "submitter_name",
      },
      {
        name: "submitter_email",
        data: "submitter_email",
      },
      {
        name: "service_body_bigint",
        data: "service_body_bigint",
        render: function (data, type, row) {
          return wbw_admin_wbw_service_bodies[data]["name"];
        },
      },
      {
        name: "changes_requested",
        data: "changes_requested",
        render: function (data, type, row) {
          var summary = "";
          var submission_type = "";
          var namestr = "";
          var original = "";
          switch (data["submission_type"]) {
            case "reason_new":
              submission_type = "New Meeting";
              namestr = data["meeting_name"];
              meeting_day = weekdays[data["weekday_tinyint"]];
              meeting_time = data["start_time"];
              break;
            case "reason_close":
              submission_type = "Close Meeting";
              // console.log(data);
              namestr = data["meeting_name"];
              meeting_day = weekdays[data["weekday_tinyint"]];
              meeting_time = data["start_time"];
              break;
            case "reason_change":
              submission_type = "Modify Meeting";
              namestr = data["original_meeting_name"];
              meeting_day = weekdays[data["original_weekday_tinyint"]];
              meeting_time = data["original_start_time"];
              original = "Original ";
              break;
            case "reason_other":
              submission_type = "Other Request";
              break;
            default:
              submission_type = data["submission_type"];
          }
          summary = "Submission Type: " + submission_type + "<br>";
          if (namestr !== "") {
            summary += "Meeting Name: " + namestr + "<br>";
          }
          if (meeting_day !== "" && meeting_time != "") {
            summary += original + "Time: " + meeting_day + " " + meeting_time;
          }
          return summary;
        },
      },
      {
        name: "submission_time",
        data: "submission_time",
        render: function (data, type, row) {
          return mysql2localdate(data);
        },
      },
      {
        name: "change_time",
        data: "change_time",
        render: function (data, type, row) {
          if (data === "0000-00-00 00:00:00") {
            return "(no change made)";
          }
          return mysql2localdate(data);
        },
      },
      {
        name: "changed_by",
        data: "changed_by",
      },
      {
        name: "change_made",
        data: "change_made",
        defaultContent: "",
        render: function (data, type, row) {
          if (data === null) {
            return "";
          }
          switch (data) {
            case "approved":
              return "Approved";
            case "rejected":
              return "Rejected";
            case "updated":
              return "Updated";
          }
          return data;
        },
      },
      {
        className: "dt-control",
        orderable: false,
        data: null,
        defaultContent: "",
      },
    ],
  });

  $("#dt-submission")
    .DataTable()
    .on("select deselect", function () {
      var actioned = true;
      if ($("#dt-submission").DataTable().row({ selected: true }).count()) {
        var change_made = $("#dt-submission").DataTable().row({ selected: true }).data()["change_made"];
        var submission_type = $("#dt-submission").DataTable().row({ selected: true }).data()["submission_type"];
        var actioned = change_made === "approved" || change_made === "rejected";
        var cantquickedit = change_made === "approved" || change_made === "rejected" || submission_type === "reason_close" || submission_type === "reason_other";
        $("#dt-submission").DataTable().button("approve:name").enable(!actioned);
        $("#dt-submission").DataTable().button("reject:name").enable(!actioned);
        $("#dt-submission").DataTable().button("quickedit:name").enable(!cantquickedit);
      } else {
        $("#dt-submission").DataTable().button("approve:name").enable(false);
        $("#dt-submission").DataTable().button("reject:name").enable(false);
        $("#dt-submission").DataTable().button("quickedit:name").enable(false);
      }
    });

  function column(col, key, value)
  {
    output = '<div class="c'+col+'k">';
    output += key;
    output += ':</div>';
    output += '<div class="c'+col+'v">';
    output += value;
    output += '</div>';
    return output;
  }

  // child rows
  function format(d) {
    // console.log(d);
    // table = '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">';
    table = '<div class="wrapper">';
    table += '<div class="cell-hdr c1k">Personal Details</div><div class="cell-hdr c1v"></div>';
    table += '<div class="cell-hdr c2k">Meeting Details</div><div class="cell-hdr c2v"></div>';
    table += '<div class="cell-hdr c3k">Virtual Meeting Details</div><div class="cell-hdr c3v"></div>';
    table += '<div class="cell-hdr c4k">FSO Request and Other Info</div><div class="cell-hdr c4v"></div>';

    for (var key in d["changes_requested"]) {
      switch (key) {
        case "meeting_name":
          mname = "Meeting Name (new)";
          if(d["submission_type"] === 'reason_close')
          {
            mname = "Meeting Name";
          }
          // table += '<div class="c2k">'+mname+':</div><div class="c2v">' + d["changes_requested"].meeting_name + "</div>";
          table += column(2, mname, d["changes_requested"].meeting_name);
          break;
        case "start_time":
          // table += '<div class="c2k">Start Time:</div><div class="c2v">' + d["changes_requested"].start_time + "</div>";
          table += column(2, "Start Time", d["changes_requested"].start_time);
          break;
        case "duration_time":
          var durationarr = d["changes_requested"].duration_time.split(":");
          table += column(2, "Duration", durationarr[0] + "h" + durationarr[1] + "m");

          // table += '<div class="c2k">Duration:</div><div class="c2v">' + durationarr[0] + "h" + durationarr[1] + "m</div>";
          break;
        case "location_text":
          // table += '<div class="c2k">Location:</div><div class="c2v">' + d["changes_requested"].location_text + "</div>";
          table += column(2, "Location", d["changes_requested"].location_text);

          break;
        case "location_street":
          // table += '<div class="c2k">Street:</div><div class="c2v">' + d["changes_requested"].location_street + "</div>";
          table += column(2, "Street", d["changes_requested"].location_street);
          break;
        case "location_info":
          // table += '<div class="c2k">Location Info:</div><div class="c2v">' + d["changes_requested"].location_info + "</div>";
          table += column(2, "Location Info", d["changes_requested"].location_info);
          break;
        case "location_municipality":
          // table += '<div class="c2k">Municipality:</div><div class="c2v">' + d["changes_requested"].location_municipality + "</div>";
          table += column(2, "Municipality", d["changes_requested"].location_municipality);
          break;
        case "location_province":
          // table += '<div class="c2k">Province/State:</div><div class="c2v">' + d["changes_requested"].location_province + "</div>";
          table += column(2, "Province", d["changes_requested"].location_province);
          break;
        case "location_sub_province":
          // table += '<div class="c2k">SubProvince:</div><div class="c2v">' + d["changes_requested"].location_sub_province + "</div>";
          table += column(2, "SubProvince", d["changes_requested"].location_sub_province);
          break;
        case "location_nation":
          // table += '<div class="c2k">Nation:</div><div class="c2v">' + d["changes_requested"].location_nation + "</div>";
          table += column(2, "Nation", d["changes_requested"].location_nation);
          break;
        case "location_postal_code_1":
          // table += '<div class="c2k">PostCode:</div><div class="c2v">' + d["changes_requested"].location_postal_code_1 + "</div>";
          table += column(2, "PostCode", d["changes_requested"].location_postal_code_1);
          break;
        case "group_relationship":
          // table += '<div class="c4k">Relationship to Group:</div><div class="c4v">' + d["changes_requested"].group_relationship + "</div>";
          table += column(4, "Relationship to Group", d["changes_requested"].group_relationship);
          break;
        case "weekday_tinyint":
          // table += '<div class="c2k">Meeting Day:</div><div class="c2v">' + weekdays[d["changes_requested"].weekday_tinyint] + "</div>";
          table += column(2, "Meeting Day", d["changes_requested"].weekday_tinyint);
          break;
        case "additional_info":
          // table += '<div class="c4k">Additional Info:</div><div class="c4v"><textarea rows="5" columns="50" disabled>' + d["changes_requested"].additional_info + "</textarea></div>";
          table += column(4, "Additional Info", '<textarea rows="5" columns="50" disabled>' + d["changes_requested"].additional_info + '</textarea>');
          break;
        case "other_reason":
          // table += '<tr><td>Other Reason:</td><td><textarea rows="5" columns="50" disabled>' + d["changes_requested"].other_reason + "</textarea></div>";
          table += column(4, "Other Reason", '<textarea rows="5" columns="50" disabled>' + d["changes_requested"].other_reason + '</textarea>');
          break;
        case "contact_number_confidential":
          // table += "<tr><td>Contact number (confidential):</td><td>" + d["changes_requested"].contact_number_confidential + "</div>";
          table += column(1, "Contact number (confidential)", d["changes_requested"].contact_number_confidential);
          break;
        case "add_email":
          // table += "<tr><td>Add email to meeting:</td><td>" + (d["changes_requested"].add_email === "yes" ? "Yes" : "No") + "</div>";
          table += column(1, "Add email to meeting",(d["changes_requested"].add_email === "yes" ? "Yes" : "No"));
          break;
        case "virtual_meeting_additional_info":
          // table += '<div class="c3k">Virtual Meeting Additional Info:</div><div class="c3v"> + d["changes_requested"].virtual_meeting_additional_info + "</div>";
          table += column(3, "Virtual Meeting Additional Info", d["changes_requested"].virtual_meeting_additional_info);
          break;
        case "phone_meeting_number":
          // table += '<div class="c3k">Virtual Meeting Phone Details:</div><div class="c3v"> + d["changes_requested"].phone_meeting_number + "</div>";
          table += column(3, "Virtual Meeting Phone Details", d["changes_requested"].phone_meeting_number);
          break;
        case "virtual_meeting_link":
          // table += '<div class="c3k">Virtual Meeting Link:</td><td>" + d["changes_requested"].virtual_meeting_link + "</div>";
          table += column(3, "Virtual Meeting Link", d["changes_requested"].virtual_meeting_link);
          break;

        case "format_shared_id_list":
          friendlyname = "Meeting Formats";
          // convert the meeting formats to human readable
          friendlydata = "";
          strarr = d["changes_requested"]["format_shared_id_list"].split(",");
          strarr.forEach((element) => {
            friendlydata += "(" + wbw_bmlt_formats[element]["key_string"] + ")-" + wbw_bmlt_formats[element]["name_string"] + " ";
          });
          // table += "<tr><td>Meeting Formats:</td><td>" + friendlydata + "</div>";
          table += column(2, "Meeting Formats", friendlydata);

          break;
      }
    }
    if ("action_message" in d && d["action_message"] != "" && d["action_message"] != null) {
      // table += "<tr><td>Message to submitter:</td><td>" + d["action_message"] + "</div>";
      table += column(4, "Message to submitter", d["action_message"]);

    }

    table += "</div>";

    // table = '<div class="wrapper">';
    // table += '<div class="cell-hdr c1">Personal Details</div>';
    // table += '<div class="cell-hdr c2">Meeting Details</div>';
    // table += '<div class="cell-hdr c3">Virtual Meeting Details</div>';
    // table += '<div class="cell-hdr c4">FSO Request and Other Info</div>';

    // table += '<div class="c1">1a</div>';
    // table += '<div class="c2">2a</div>';
    // table += '<div class="c3">3a</div>';
    // table += '<div class="c4">4a</div>';
    // table += '<div class="c1">1b</div>';
    // table += '<div class="c2">2b</div>';
    // table += '<div class="c3">3b</div>';
    // table += '<div class="c4">4b</div>';
    // table += '<div class="c4">4c</div>';
    // table += '<div class="c1">1c</div>';
    // table += '<div class="c1">1d</div>';
    // table += '<div class="c3">3c</div>';
    // table += '<div class="c2">2c</div>';
    // table += '<div class="c1">1c</div>';
    // table += '<div class="c4">4d</div>';

    // table += '</div>';
    return table;
  }

  $("#dt-submission tbody").on("click", "td.dt-control", function () {
    var tr = $(this).closest("tr");
    var row = datatable.row(tr);

    if (row.child.isShown()) {
      // This row is already open - close it
      row.child.hide();
      tr.removeClass("shown");
    } else {
      // Open this row
      row.child(format(row.data())).show();
      tr.addClass("shown");
    }
  });

  function wbw_create_generic_modal(dialogid, title, width, maxwidth) {
    $("#" + dialogid).dialog({
      title: title,
      autoOpen: false,
      draggable: false,
      width: width,
      maxWidth: maxwidth,
      modal: true,
      resizable: false,
      closeOnEscape: true,
      position: {
        my: "center",
        at: "center",
        of: window,
      },
      buttons: {
        Ok: function () {
          fn = window[this.id + "_ok"];
          if (typeof fn === "function") fn($(this).data("id"));
        },
        Cancel: function () {
          $(this).dialog("close");
        },
      },
      open: function () {
        var $this = $(this);
        // close dialog by clicking the overlay behind it
        $(".ui-widget-overlay").on("click", function () {
          $this.dialog("close");
        });
      },
      create: function () {
        $(".ui-dialog-titlebar-close").addClass("ui-button");
      },
    });
  }

  function wbw_create_quickedit_modal(dialogid, title, width, maxwidth) {
    $("#" + dialogid).dialog({
      title: title,
      classes: { "ui-dialog-content": "quickedit" },
      autoOpen: false,
      draggable: false,
      width: width,
      maxWidth: maxwidth,
      modal: true,
      resizable: false,
      closeOnEscape: true,
      position: {
        my: "center",
        at: "center",
        of: window,
      },
      buttons: {
        "Check Geolocate": function () {
          geolocate_handler($(this).data("id"));
        },
        Save: function () {
          save_handler($(this).data("id"));
        },
        Cancel: function () {
          $(this).dialog("close");
        },
      },
      open: function () {
        var $this = $(this);
        // close dialog by clicking the overlay behind it
        $(".ui-widget-overlay").on("click", function () {
          $this.dialog("close");
        });
      },
      create: function () {
        $(".ui-dialog-titlebar-close").addClass("ui-button");
      },
    });
  }

  wbw_create_generic_modal("wbw_submission_delete_dialog", "Delete Submission", "auto", "auto");
  wbw_create_generic_modal("wbw_submission_approve_dialog", "Approve Submission", "auto", "auto");
  wbw_create_generic_modal("wbw_submission_approve_close_dialog", "Approve Submission", "auto", "auto");
  wbw_create_generic_modal("wbw_submission_reject_dialog", "Reject Submission", "auto", "auto");
  wbw_create_quickedit_modal("wbw_submission_quickedit_dialog", "Submission QuickEdit", "auto", "auto");

  wbw_submission_approve_dialog_ok = function (id) {
    clear_notices();
    generic_approve_handler(id, "POST", "/approve", "wbw_submission_approve");
  };

  wbw_submission_approve_close_dialog_ok = function (id) {
    clear_notices();
    generic_approve_handler(id, "POST", "/approve", "wbw_submission_approve_close");
  };

  wbw_submission_reject_dialog_ok = function (id) {
    clear_notices();
    generic_approve_handler(id, "POST", "/reject", "wbw_submission_reject");
  };
  wbw_submission_delete_dialog_ok = function (id) {
    clear_notices();
    generic_approve_handler(id, "DELETE", "", "wbw_submission_delete");
  };

  function generic_approve_handler(id, action, url, slug) {
    parameters = {};
    if ($("#" + slug + "_dialog_textarea").length) {
      var action_message = $("#" + slug + "_dialog_textarea")
        .val()
        .trim();
      if (action_message !== "") {
        parameters["action_message"] = action_message;
      }
    }

    // delete/unpublish handling on the approve+close dialog
    if (slug === "wbw_submission_approve_close") {
      option = $("#" + slug + '_dialog input[name="close_action"]:checked').attr("id");
      if (option === "close_delete") {
        parameters["delete"] = true;
      } else {
        parameters["delete"] = false;
      }
    }

    $.ajax({
      url: wbw_admin_submissions_rest_url + id + url,
      type: action,
      dataType: "json",
      contentType: "application/json",
      data: JSON.stringify(parameters),
      beforeSend: function (xhr) {
        xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
      },
    })
      .done(function (response) {
        notice_success(response,"wbw-error-message");
        // reload the table to pick up any changes
        $("#dt-submission").DataTable().ajax.reload();
        // reset the buttons correctly
        $("#dt-submission").DataTable().rows().deselect();
      })
      .fail(function (xhr) {
        notice_error(xhr,"wbw-error-message");
      });
    $("#" + slug + "_dialog").dialog("close");
  }

  function geolocate_handler(id) {

    // $locfields = array("location_street", "location_municipality", "location_province", "location_postal_code_1", "location_sub_province", "location_nation");
    // $locdata = array();
    // foreach($locfields as $field)
    // {
    //     if(!empty($change[$field]))
    //     {
    //         $locdata[]=$change[$field];
    //     }
    // }
    // $locstring = implode(', ',$locdata);
    var locfields = ["location_street", "location_municipality", "location_province", "location_postal_code_1", "location_sub_province", "location_nation" ];
    var locdata = [];

    locfields.forEach((item,i) => 
    {
      var el = "#quickedit_" + item;
      var val = $(el).val();
      if (val != '')
      {
        locdata.push(val);
      }

    })

    var address = "address="+locdata.join(',');

    $.ajax({
      url: wbw_bmltserver_geolocate_rest_url,
      type: 'GET',
      dataType: "json",
      contentType: "application/json",
      data: encodeURI(address),
      beforeSend: function (xhr) {
        xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
      },
    })
      .done(function (response) {
        $("#quickedit_latitude").val(response['latitude']);
        $("#quickedit_longitude").val(response['longitude']);
        notice_success(response,"wbw-error-message");
      })
      .fail(function (xhr) {
        notice_error(xhr,"wbw-error-message");
      });
  
  }

  function save_handler(id) {
    parameters = {};
    changes_requested = {};
    quickedit_changes_requested = {};

    clear_notices();
    var duration_hours = "00";
    var duration_minutes = "00";

    // pull out all the changed elements
    $(".wbw-changed").each(function () {
      if ($(this).is("textarea,select,input")) {
        var short_id = $(this).attr("id").replace("quickedit_", "");
        // turn the format list into a comma seperated array
        if (short_id === "format_shared_id_list") {
          quickedit_changes_requested[short_id] = $(this).val().join(",");
        } 
        // reconstruct our duration from the select list
        else if (short_id === "duration_hours")
        {
          duration_hours = $(this).val();
        }
        else if (short_id === "duration_minutes")
        {
          duration_minutes = $(this).val();
        }
        else
        {
          quickedit_changes_requested[short_id] = $(this).val();
        }
      }
    });

    quickedit_changes_requested["duration_time"] = duration_hours + ":" + duration_minutes + ":00";

    parameters["changes_requested"] = quickedit_changes_requested;

    $.ajax({
      url: wbw_admin_submissions_rest_url + id,
      type: "PATCH",
      dataType: "json",
      contentType: "application/json",
      data: JSON.stringify(parameters),
      beforeSend: function (xhr) {
        xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
      },
    })
      .done(function (response) {
        notice_success(response, "wbw-error-message");

        // reload the table to pick up any changes
        $("#dt-submission").DataTable().ajax.reload();
        // reset the buttons correctly
        $("#dt-submission").DataTable().rows().deselect();
      })
      .fail(function (xhr) {
        notice_error(xhr,"wbw-error-message");
      });
    $("#wbw_submission_quickedit_dialog").dialog("close");
  }

});
