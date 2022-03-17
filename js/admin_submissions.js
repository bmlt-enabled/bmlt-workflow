jQuery(document).ready(function ($) {
  // console.log(bmaw_admin_submissions_rest_url);

  $("#dt-submission").DataTable({
    dom: "Bfrtip",
    select: true,
    buttons: [
      {
        text: "Approve",
        extend: "selected",
        action: function (e, dt, button, config) {
          var id = dt.cell(".selected", 0).data();
          $("#bmaw_submission_approve_dialog").data("id", id).dialog("open");
        },
      },
      {
        text: "Reject",
        extend: "selected",
        action: function (e, dt, button, config) {
          var id = dt.cell(".selected", 0).data();
          $("#bmaw_submission_reject_dialog").data("id", id).dialog("open");
        },
      },
      {
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
        for (var i = 0, ien = json.length; i < ien; i++) {
          json[i]["changes_requested"]["submission_type"] = json[i]["submission_type"];
        }
        return json;
      },
    },
    columns: [
      { data: "id" },
      { data: "submitter_name" },
      { data: "submitter_email" },
      // { "data": "submission_type" },
      {
        data: "changes_requested",
        render: function (data, type, row) {
          var summary = "";
          summary = "<b>Change Type: " + data["submission_type"] + "</b><br><br>";
          for (var key in data) {
            friendlyname = key;
            friendlydata = data[key];

            switch (key) {
              case "meeting_id":
                friendlyname = "";
                break;
              case "submission_type":
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
              summary += friendlyname + ' <span class="dashicons dashicons-arrow-right-alt"></span> <b>' + friendlydata + "</b><br>";
            }
          }
          return summary;
        },
      },
      { data: "submission_time" },
      { data: "change_time" },
      { data: "changed_by" },
      { data: "change_made" },
    ],
  });

  function bmaw_create_generic_modal(dialogid, title) {
    $("#" + dialogid).dialog({
      title: title,
      dialogClass: "wp-dialog",
      autoOpen: false,
      draggable: false,
      width: "auto",
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
        // close dialog by clicking the overlay behind it
        $(".ui-widget-overlay").bind("click", function () {
          $(this).dialog("close");
        });
      },
      create: function () {
        $(".ui-dialog-titlebar-close").addClass("ui-button");
      },
    });
  }

  bmaw_create_generic_modal("bmaw_submission_delete_dialog", "Delete Submission");
  bmaw_create_generic_modal("bmaw_submission_approve_dialog", "Approve Submission");
  bmaw_create_generic_modal("bmaw_submission_reject_dialog", "Reject Submission");

  bmaw_submission_approve_dialog_ok = function (id) {
    generic_approve_handler(id, "POST", "/approve", "bmaw_submission_approve");
  };
  bmaw_submission_reject_dialog_ok = function (id) {
    generic_approve_handler(id, "POST", "/reject", "bmaw_submission_reject");
  };
  bmaw_submission_delete_dialog_ok = function (id) {
    generic_approve_handler(id, "DELETE", "", "bmaw_submission_delete");
  };

  function generic_approve_handler(id, action, url, slug) {
    parameters = {};
    if ($.trim($("#" + slug + "_dialog_textarea").val())) {
      parameters["custom_message"] = $("#" + slug + "_dialog_textarea");
    }
    // url = "/approve"
    $.ajax({
      url: bmaw_admin_submissions_rest_url + id + url,
      type: action,
      data: JSON.stringify(parameters),
      beforeSend: function (xhr) {
        xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
      },
    })
      .done(function (response) {
        var msg = "";
        if (response.error_message == "")
          msg =
            '<div class="notice notice-success is-dismissible my_notice"><p><strong>SUCCESS: </strong>This is my success message.</p><button type="button" class="notice-dismiss" onclick="javascript: return px_dissmiss_notice(this);"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
        else
          msg =
            '<div class="notice notice-error is-dismissible my_notice"><p><strong>ERROR: </strong>' +
            response.error_message +
            '.</p><button type="button" class="notice-dismiss" onclick="javascript: return px_dissmiss_notice(this);"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
        $(".wp-header-end").after(msg);
        $("#dt-submission").DataTable().ajax.reload();
      })
      .fail(function (xhr) {
        $(".wp-header-end").after(
          '<div class="notice notice-error is-dismissible my_notice"><p><strong>ERROR: </strong>' +
            xhr.status +
            " " +
            xhr.statusText +
            '.</p><button type="button" class="notice-dismiss" onclick="javascript: return px_dissmiss_notice(this);"><span class="screen-reader-text">Dismiss this notice.</span></button></div>'
        );
      });

    $("#" + slug + "_dialog").dialog("close");
  }
});
