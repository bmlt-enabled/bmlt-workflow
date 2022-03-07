jQuery(document).ready(function ($) {
  function bmaw_create_row_link_modal(element, title) {
    $("#" + element + "_dialog").dialog({
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
          fn = window[element + "_ok"];
          if (typeof fn === "function") fn($(this).data("id"));
        },
        Cancel: function () {
          $(this).dialog("close");
        },
      },
      open: function () {
        // close dialog by clicking the overlay behind it
        $(".ui-widget-overlay").bind("click", function () {
          $(element).dialog("close");
        });
      },
      create: function () {
        $(".ui-dialog-titlebar-close").addClass("ui-button");
      },
    });
    // hook the approve flow
    $("." + element).on("click", function (event) {
      event.preventDefault();
      var id = this.id.replace(element + "_id_", "");
      $("#" + element + "_dialog")
        .data("id", id)
        .dialog("open");
    });
  }
  bmaw_create_row_link_modal("bmaw_submission_delete", "Delete Submission");
  bmaw_create_row_link_modal("bmaw_submission_approve", "Approve Submission");
  // hook the approve flow
  // $('#bmaw_submission_approve').on("click", function (event)
  // {
  //   event.preventDefault();
  //   var id = this.id.replace("bmaw_submission_approve_id_", "");
  //   $("#" + $element + "-dialog").data('id', id).dialog("open");
  // });

  // hook the delete flow
  // $('#bmaw_submission_delete').on("click", function (event)
  // {
  //   event.preventDefault();
  //   var id = this.id.replace("bmaw_submission_delete_id_", "");
  //   $("#" + $element + "-dialog").data('id', id).dialog("open");
  // });

  function bmaw_submission_approve_ok(id) {
    $.post("/flop/wp-json/bmaw-submission/v1/submissions/" + id + "/approve", {
      _wpnonce: $("#_wprestnonce").val(),
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
  }
});
