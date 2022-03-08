jQuery(document).ready(function ($) {
  function bmaw_create_row_link_modal(element, title) {
    dialogname = "#" + element + "_dialog";
    classname = "." + element;
    idname = element + "_id_";

    $(dialogname).dialog({
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
    // hook the approve flow
    $(classname).on("click", function (event) {
      event.preventDefault();
      let id = this.id.substring(this.id.indexOf("_id_") + 4, this.id.length);
      let dialog = "#" + this.id.substring(0, this.id.indexOf("_id_")) + "_dialog";
      $(dialog).data("id", id).dialog("open");
    });
  }
  bmaw_create_row_link_modal("bmaw_submission_delete", "Delete Submission");
  bmaw_create_row_link_modal("bmaw_submission_approve", "Approve Submission");

  bmaw_submission_approve_dialog_ok = function (id) {
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

    $("#bmaw_submission_approve_dialog").dialog("close");
    location.reload();
  };

  bmaw_submission_delete_dialog_ok = function (id) {
    $.ajax({
      url: "/flop/wp-json/bmaw-submission/v1/submissions/" + id,
      type: "DELETE",
      data: {
        _wpnonce: $("#_wprestnonce").val(),
      },
    })
      .always(function (response) {
        console.log(response);
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

    $("#bmaw_submission_delete_dialog").dialog("close");
    // location.reload();
  };
});
