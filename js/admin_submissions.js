function dismiss_notice(element) {
  jQuery(element)
    .parent()
    .slideUp("normal", function () {
      jQuery(this).remove();
    });
  return false;
}

var wbw_changedata = {};

jQuery(document).ready(function ($) {
  function populate_and_open_quickedit(id) {
    // clear quickedit

    // remove our change handler
    $(".quickedit-input").off("input");
    // remove the highlighting
    $(".quickedit-input").removeClass("wbw-changed");
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
        "&lang_enum=en&data_field_key=location_postal_code_1,duration_time,start_time,time_zone,weekday_tinyint,service_body_bigint,longitude,latitude,location_province,location_municipality,location_street,location_info,location_neighborhood,formats,format_shared_id_list,comments,location_sub_province,worldid_mixed,root_server_uri,id_bigint,venue_type,meeting_name,location_text,virtual_meeting_additional_info,contact_name_1,contact_phone_1,contact_email_1,contact_name_2,contact_phone_2,contact_email_2&&recursive=1&sort_keys=start_time";

      fetchJsonp(search_results_address)
        .then((response) => response.json())
        .then((data) => {
          // fill in all the bmlt stuff
          var item = data[0];
          if (!Object.keys(data).length) {
            var a = {};
            a["responseJSON"] = {};
            a["responseJSON"]["message"] = "Error retrieving BMLT data";
            notice_error(a);
          } else {
            // split up the duration so we can use it in the select
            if ("duration_time" in item) {
              var durationarr = item["duration_time"].split(":");
              // hoping we got both hours, minutes and seconds here
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
                $("#quickedit_" + element).trigger('change');
              }
            });

            // fill in and highlight the changes - use extend to clone
            changes_requested = $.extend(true,{},wbw_changedata[id].changes_requested);

            if ("format_shared_id_list" in changes_requested) {
              changes_requested["format_shared_id_list"] = changes_requested["format_shared_id_list"].split(",");
            }

            Object.keys(changes_requested).forEach((element) => {
              if ($("#quickedit_" + element).length) {
                $("#quickedit_" + element).addClass("wbw-changed");
                $("#quickedit_" + element).val(changes_requested[element]);
                $("#quickedit_" + element).trigger('change');

              }
            });
            // trigger adding of highlights when input changes
            $(".quickedit-input").on("input", function () {
              $(this).addClass("wbw-changed");
            });
            $("#wbw_submission_quickedit_dialog").data("id", id).dialog("open");
          }
        });
    } else if (wbw_changedata[id].submission_type == "reason_new") {
      // fill in and highlight the changes - use extend to clone
      changes_requested = $.extend(true,{},wbw_changedata[id].changes_requested);

      // split up the duration so we can use it in the select
      if ("duration_time" in changes_requested) {
        var durationarr = changes_requested["duration_time"].split(":");
        // hoping we got both hours, minutes and seconds here
        if (durationarr.length == 3) {
          $("#quickedit_duration_hours").val(durationarr[0]);
          $("#quickedit_duration_minutes").val(durationarr[1]);
        }
      }

      if ("format_shared_id_list" in changes_requested) {
        changes_requested["format_shared_id_list"] = changes_requested["format_shared_id_list"].split(",");
      }
      Object.keys(changes_requested).forEach((element) => {
        if ($("#quickedit_" + element).length) {
          $("#quickedit_" + element).addClass("wbw-changed");
          $("#quickedit_" + element).val(changes_requested[element]);
        }
      });

      // trigger adding of highlights when input changes
      $(".quickedit-input").on("input", function () {
        $(this).addClass("wbw-changed");
      });
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
    width: "90%",
    data: formatdata,
    dropdownParent: $("#wbw_submission_quickedit_dialog"),
  });
  $("#quickedit_format_shared_id_list").trigger('change');

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
          switch (data["submission_type"]) {
            case "reason_new":
              submission_type = "New Meeting";
              namestr = data["meeting_name"];
              break;
            case "reason_close":
              submission_type = "Close Meeting";
              console.log(data);
              namestr = data["meeting_name"];
              break;
            case "reason_change":
              submission_type = "Modify Meeting";
              namestr = data["original_meeting_name"];
              break;
            case "reason_other":
              submission_type = "Other Request";
              break;
            default:
              submission_type = data["submission_type"];
          }
          summary = "Submission Type: " + submission_type + "<br>";
          summary += "Meeting Name: " + namestr;

          return summary;
        },
      },
      {
        name: "submission_time",
        data: "submission_time",
      },
      {
        name: "change_time",
        data: "change_time",
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
        var actioned = change_made === "approved" || change_made === "rejected";
      }
      $("#dt-submission").DataTable().button("approve:name").enable(!actioned);
      $("#dt-submission").DataTable().button("reject:name").enable(!actioned);
      $("#dt-submission").DataTable().button("quickedit:name").enable(!actioned);
    });

  // child rows
  function format(d) {
    console.log(d);
    table = '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">';

    for (var key in d["changes_requested"]) {
      switch (key) {
        case "start_time":
          table += "<tr><td>Start Time:</td><td>" + d["changes_requested"].start_time + "</td></tr>";
          break;
        case "duration":
          table += "<tr><td>Duration:</td><td>" + d["changes_requested"].duration + "</td></tr>";
          break;
        case "location_text":
          table += "<tr><td>Location:</td><td>" + d["changes_requested"].location_text + "</td></tr>";
          break;
        case "location_street":
          table += "<tr><td>Street:</td><td>" + d["changes_requested"].location_street + "</td></tr>";
          break;
        case "location_info":
          table += "<tr><td>Location Info:</td><td>" + d["changes_requested"].location_info + "</td></tr>";
          break;
        case "location_municipality":
          table += "<tr><td>Municipality:</td><td>" + d["changes_requested"].location_municipality + "</td></tr>";
          break;
        case "location_province":
          table += "<tr><td>Province/State:</td><td>" + d["changes_requested"].location_province + "</td></tr>";
          break;
        case "location_sub_province":
          table += "<tr><td>SubProvince:</td><td>" + d["changes_requested"].location_sub_province + "</td></tr>";
          break;
        case "location_nation":
          table += "<tr><td>Nation:</td><td>" + d["changes_requested"].location_nation + "</td></tr>";
          break;
        case "location_postal_code_1":
          table += "<tr><td>PostCode:</td><td>" + d["changes_requested"].location_postal_code_1 + "</td></tr>";
          break;
        case "group_relationship":
          table += "<tr><td>Relationship to Group:</td><td>" + d["changes_requested"].group_relationship + "</td></tr>";
          break;
        case "weekday_tinyint":
          weekdays = ["Error", "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
          table += "<tr><td>Meeting Day:</td><td>" + weekdays[d["changes_requested"].weekday_tinyint] + "</td></tr>";
          break;
        case "additional_info":
          table += '<tr><td>Additional Info:</td><td><textarea rows="5" columns="50" disabled>' + d["changes_requested"].additional_info + "</textarea></td></tr>";
          break;
        case "other_reason":
          table += '<tr><td>Other Reason:</td><td><textarea rows="5" columns="50" disabled>' + d["changes_requested"].other_reason + "</textarea></td></tr>";
          break;
        case "contact_number_confidential":
          table += "<tr><td>Contact number (confidential):</td><td>" + d["changes_requested"].contact_number_confidential + "</td></tr>";
          break;
        case "add_email":
          table += "<tr><td>Add email to meeting:</td><td>" + ((d["changes_requested"].add_email === "yes") ? "Yes" : "No") + "</td></tr>";
          break;

        case "format_shared_id_list":
          friendlyname = "Meeting Formats";
          // convert the meeting formats to human readable
          friendlydata = "";
          strarr = d["changes_requested"]["format_shared_id_list"].split(",");
          strarr.forEach((element) => {
            friendlydata += "(" + wbw_bmlt_formats[element]["key_string"] + ")-" + wbw_bmlt_formats[element]["name_string"] + " ";
          });
          table += "<tr><td>Meeting Formats:</td><td>" + friendlydata + "</td></tr>";
          break;
      }
    }
    if ("action_message" in d && d["action_message"] != "" && d["action_message"] != null) {
      table += "<tr><td>Message to submitter:</td><td>" + d["action_message"] + "</td></tr>";
    }
    table += "</table>";

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
        // "Save and Approve": function () {
        //   save_approve_handler($(this).data("id"));
        // },
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
  wbw_create_quickedit_modal("wbw_submission_quickedit_dialog", "Submission QuickEdit", "80%", auto);

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
        notice_success(response);
        // reload the table to pick up any changes
        $("#dt-submission").DataTable().ajax.reload();
        // reset the buttons correctly
        $("#dt-submission").DataTable().rows().deselect();
      })
      .fail(function (xhr) {
        notice_error(xhr);
      });
    $("#" + slug + "_dialog").dialog("close");
  }

  function save_handler(id) {
    parameters = {};
    changes_requested = {};
    quickedit_changes_requested = {};

    clear_notices();

    // pull out all the changed elements
    $(".wbw-changed").each(function () {
      var short_id = $(this).attr("id").replace("quickedit_", "");
      // turn the format list into a comma seperated array
      if (short_id === "format_shared_id_list") {
        quickedit_changes_requested[short_id] = $(this).val().join(",");
      } else {
        quickedit_changes_requested[short_id] = $(this).val();
      }
    });

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
        notice_success(response);

        // reload the table to pick up any changes
        $("#dt-submission").DataTable().ajax.reload();
        // reset the buttons correctly
        $("#dt-submission").DataTable().rows().deselect();
      })
      .fail(function (xhr) {
        notice_error(xhr);
      });
    $("#wbw_submission_quickedit_dialog").dialog("close");
  }

  function notice_success(response) {
    var msg = "";
    if (response.message == "")
      msg =
        '<div class="notice notice-success is-dismissible"><p><strong>SUCCESS: </strong><button type="button" class="notice-dismiss" onclick="javascript: return dismiss_notice(this);"></button></div>';
    else
      msg =
        '<div class="notice notice-success is-dismissible"><p><strong>SUCCESS: </strong>' +
        response.message +
        '.</p><button type="button" class="notice-dismiss" onclick="javascript: return dismiss_notice(this);"></button></div>';
    $(".wp-header-end").after(msg);
  }

  function notice_error(xhr) {
    $(".wp-header-end").after(
      '<div class="notice notice-error is-dismissible"><p><strong>ERROR: </strong>' +
        xhr.responseJSON.message +
        '.</p><button type="button" class="notice-dismiss" onclick="javascript: return dismiss_notice(this);"></button></div>'
    );
  }
});
