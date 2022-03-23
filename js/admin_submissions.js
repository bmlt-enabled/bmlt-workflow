function dismiss_notice(element) {
  jQuery(element)
    .parent()
    .slideUp("normal", function () {
      jQuery(this).remove();
    });
  return false;
}

var bmaw_changedata = {};

jQuery(document).ready(function ($) {
  function populate_and_open_quickedit(id) {
    // clear quickedit

    // remove our change handler
    $(".quickedit-input").off("change");
    // remove the highlighting
    $(".quickedit-input").removeClass("bmaw-changed");
    // remove any content from the input fields
    $(".quickedit-input").val("");

    // fill quickedit

    // if it's a meeting change, fill from bmlt first
    if (bmaw_changedata[id].submission_type == "reason_change") {
      var meeting_id = bmaw_changedata[id].changes_requested["meeting_id"];
      var search_results_address =
        bmaw_bmlt_server_address +
        "client_interface/jsonp/?switcher=GetSearchResults&meeting_key=id_bigint&meeting_key_value=" +
        meeting_id +
        "&lang_enum=en&data_field_key=location_postal_code_1,duration_time,start_time,time_zone,weekday_tinyint,service_body_bigint,longitude,latitude,location_province,location_municipality,location_street,location_info,location_neighborhood,formats,format_shared_id_list,comments,location_sub_province,worldid_mixed,root_server_uri,id_bigint,venue_type,meeting_name,location_text,virtual_meeting_additional_info,contact_name_1,contact_phone_1,contact_email_1,contact_name_2,contact_phone_2,contact_email_2&&recursive=1&sort_keys=start_time";

      fetchJsonp(search_results_address)
        .then((response) => response.json())
        .then((data) => {
          // fill in all the bmlt stuff
          var item = data[0];
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
            if ($("#quickedit_" + element) instanceof jQuery) {
              $("#quickedit_" + element)
                .val(item[element]);
            }
          });
          // fill in and highlight the changes
          changes_requested = bmaw_changedata[id].changes_requested;

          if ("format_shared_id_list" in changes_requested) {
            changes_requested["format_shared_id_list"] = changes_requested["format_shared_id_list"].split(",");
          }

          Object.keys(changes_requested).forEach((element) => {
            if ($("#quickedit_" + element) instanceof jQuery) {
              $("#quickedit_" + element).addClass("bmaw-changed");
              $("#quickedit_" + element)
                .val(changes_requested[element]);
            }
          });
        });
    } else if (bmaw_changedata[id].submission_type == "reason_new") {
      // fill from changes
      changes_requested = bmaw_changedata[id].changes_requested;

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
        if ($("#quickedit_" + element) instanceof jQuery) {
          $("#quickedit_" + element).addClass("bmaw-changed");
          $("#quickedit_" + element)
            .val(changes_requested[element]);
        }
      });
    }
    // trigger adding of highlights when input changes
    $(".quickedit-input").on('input',function () {
      $(this).addClass("bmaw-changed");
    });
    $("#bmaw_submission_quickedit_dialog").data("id", id).dialog("open");
  }

  function clear_notices() {
    jQuery(".notice-dismiss").each(function (i, e) {
      dismiss_notice(e);
    });
  }

  var formatdata = [];
  Object.keys(bmaw_bmlt_formats).forEach((key) => {
    formatdata.push({ text: "(" + bmaw_bmlt_formats[key]["key_string"] + ")-" + bmaw_bmlt_formats[key]["name_string"], id: key });
  });

  $("#quickedit_format_shared_id_list").select2({
    placeholder: "Select from available formats",
    multiple: true,
    width: "90%",
    data: formatdata,
    dropdownParent: $("#bmaw_submission_quickedit_dialog"),
  });

  $("#dt-submission").DataTable({
    dom: "Bfrtip",
    select: true,
    buttons: [
      {
        name: "approve",
        text: "Approve",
        enabled: false,
        action: function (e, dt, button, config) {
          var id = dt.cell(".selected", 0).data();
          // clear text area from before
          $("#bmaw_submission_approve_dialog_textarea").val("");
          $("#bmaw_submission_approve_dialog").data("id", id).dialog("open");
        },
      },
      {
        name: "reject",
        text: "Reject",
        enabled: false,
        action: function (e, dt, button, config) {
          var id = dt.cell(".selected", 0).data();
          // clear text area from before
          $("#bmaw_submission_reject_dialog_textarea").val("");
          $("#bmaw_submission_reject_dialog").data("id", id).dialog("open");
        },
      },
      {
        name: "quickedit",
        text: "QuickEdit",
        extend: "selected",
        action: function (e, dt, button, config) {
          var id = dt.cell(".selected", 0).data();
          populate_and_open_quickedit(id);
        },
      },
      {
        name: "delete",
        text: "Delete",
        extend: "selected",
        action: function (e, dt, button, config) {
          var id = dt.cell(".selected", 0).data();
          $("#bmaw_submission_delete_dialog").data("id", id).dialog("open");
        },
      },
    ],
    ajax: {
      url: bmaw_admin_submissions_rest_url,
      beforeSend: function (xhr) {
        xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
      },
      dataSrc: function (json) {
        bmaw_changedata = {};
        for (var i = 0, ien = json.length; i < ien; i++) {
          json[i]["changes_requested"]["submission_type"] = json[i]["submission_type"];
          // store the json for us to use in quick editor
          bmaw_changedata[json[i]["id"]] = json[i];
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
        name: "changes_requested",
        data: "changes_requested",
        render: function (data, type, row) {
          var summary = "";
          var submission_type = "";
          var namestr = "";
          switch (data["submission_type"]) {
            case "reason_new":
              submission_type = "New Meeting";
              break;
            case "reason_close":
              submission_type = "Close Meeting";
              break;
            case "reason_change":
              submission_type = "Modify Meeting";
              meeting_name = data["original_meeting_name"];
              namestr = "<br>Meeting: " + meeting_name;
              break;
            case "reason_other":
              submission_type = "Other Request";
              break;
            default:
              submission_type = data["submission_type"];
          }
          summary = "Submission Type: " + submission_type + namestr + "<br><br>";
          for (var key in data) {
            friendlyname = key;
            friendlydata = data[key];

            switch (key) {
              // skip these ones - we already used them above
              case "meeting_id":
              case "submission_type":
              case "original_meeting_name":
                friendlyname = "";
                break;
              case "meeting_name":
                friendlyname = "Meeting Name";
                break;
              case "start_time":
                friendlyname = "Start Time";
                break;
              case "duration_time":
                friendlyname = "Duration";
                break;
              case "location_text":
                friendlyname = "Location";
                break;
              case "location_street":
                friendlyname = "Street";
                break;
              case "location_info":
                friendlyname = "Location Info";
                break;
              case "location_municipality":
                friendlyname = "Municipality";
                break;
              case "location_province":
                friendlyname = "Province/State";
                break;
              case "location_postal_code_1":
                friendlyname = "Postcode";
                break;
              case "weekday_tinyint":
                weekdays = ["Error", "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
                friendlydata = weekdays[data["weekday_tinyint"]];
                friendlyname = "Meeting Day";
                break;
              case "service_body_bigint":
                friendlydata = bmaw_admin_bmaw_service_areas[data["service_body_bigint"]]["name"];
                friendlyname = "Service Body";
                break;
              case "format_shared_id_list":
                friendlyname = "Meeting Formats";
                // convert the meeting formats to human readable
                friendlydata = "";
                strarr = data["format_shared_id_list"].split(",");
                strarr.forEach((element) => {
                  friendlydata += "(" + bmaw_bmlt_formats[element]["key_string"] + ")-" + bmaw_bmlt_formats[element]["name_string"] + " ";
                });
                break;
              default:
                break;
            }
            if (friendlyname != "" && friendlydata != "") {
              summary += friendlyname + ' <span class="dashicons dashicons-arrow-right-alt"></span> ' + friendlydata + "<br>";
            }
          }
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
        "className":      'disabled',
        "orderable":      false,
        "data":           null,
        "defaultContent": ''
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
    function format ( d ) {
      console.log(d);
      // `d` is the original data object for the row
      return '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">'+
          '<tr>'+
              '<td>Change Made:</td>'+
              '<td>'+d.change_made+'</td>'+
          '</tr>'+
          '<tr>'+
              '<td>Submission Time:</td>'+
              '<td>'+d.submission_time+'</td>'+
          '</tr>'+
          '<tr>'+
              '<td>Change Time:</td>'+
              '<td>'+d.change_time+'</td>'+
          '</tr>'+
      '</table>';
  }
  
    $('#dt-submission tbody').on('click', 'td.dt-control', function () {
      var tr = $(this).closest('tr');
      var row = table.row( tr );

      if ( row.child.isShown() ) {
          // This row is already open - close it
          row.child.hide();
          tr.removeClass('shown');
      }
      else {
          // Open this row
          row.child( format(row.data()) ).show();
          tr.addClass('shown');
      }
  } );

  function bmaw_create_generic_modal(dialogid, title, width, maxwidth) {
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
        $(".ui-widget-overlay").bind("click", function () {
          $this.dialog("close");
        });
      },
      create: function () {
        $(".ui-dialog-titlebar-close").addClass("ui-button");
      },
    });
  }

  function bmaw_create_quickedit_modal(dialogid, title, width, maxwidth) {
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
        $(".ui-widget-overlay").bind("click", function () {
          $this.dialog("close");
        });
      },
      create: function () {
        $(".ui-dialog-titlebar-close").addClass("ui-button");
      },
    });
  }

  bmaw_create_generic_modal("bmaw_submission_delete_dialog", "Delete Submission", "auto", "auto");
  bmaw_create_generic_modal("bmaw_submission_approve_dialog", "Approve Submission", "auto", "auto");
  bmaw_create_generic_modal("bmaw_submission_reject_dialog", "Reject Submission", "auto", "auto");
  bmaw_create_quickedit_modal("bmaw_submission_quickedit_dialog", "Submission QuickEdit", "60%", 768);

  bmaw_submission_approve_dialog_ok = function (id) {
    clear_notices();
    generic_approve_handler(id, "POST", "/approve", "bmaw_submission_approve");
  };
  bmaw_submission_reject_dialog_ok = function (id) {
    clear_notices();
    generic_approve_handler(id, "POST", "/reject", "bmaw_submission_reject");
  };
  bmaw_submission_delete_dialog_ok = function (id) {
    clear_notices();
    generic_approve_handler(id, "DELETE", "", "bmaw_submission_delete");
  };

  function generic_approve_handler(id, action, url, slug) {
    parameters = {};
    var action_message = String.prototype.trim($("#" + slug + "_dialog_textarea").val());
    if (action_message === '')
    {
      parameters["action_message"] = action_message;
    }
    $.ajax({
      url: bmaw_admin_submissions_rest_url + id + url,
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
    $(".bmaw-changed").each(function () {
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
      url: bmaw_admin_submissions_rest_url + id,
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
    $("#bmaw_submission_quickedit_dialog").dialog("close");
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
