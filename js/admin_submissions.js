jQuery(document).ready(function ($) {

  function bmaw_create_modal($element, $title) {
    $("#" + $element + "-dialog").dialog({
      title: $title,
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
        "Ok": function() {
          fn = window[$element+'_ok']
          if (typeof fn === "function") fn();
        },
        Cancel: function() {
          $( this ).dialog( "close" );
        }
      },
      open: function () {
        // close dialog by clicking the overlay behind it
        $(".ui-widget-overlay").bind("click", function () {
          $($element).dialog("close");
        });
      },
      create: function () {
        $(".ui-dialog-titlebar-close").addClass("ui-button");
      },
    });
    // Add open hook
    $("." + $element).on("click", function (event) {
      event.preventDefault();
      $("#" + $element + "-dialog").dialog("open");
    });
  
  }

  bmaw_create_modal("bmaw_submission_delete", "Delete Submission");
  bmaw_create_modal("bmaw_submission_approve", "Approve Submission");

  function bmaw_submission_approve_ok(event) {
    event.preventDefault();
    var id = this.id.replace("bmaw_approve_id_", "");
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
  };
});
