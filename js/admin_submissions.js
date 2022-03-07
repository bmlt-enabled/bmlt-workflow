jQuery(document).ready(function ($) {

  dialog = $( "#dialog-form" ).dialog({
    autoOpen: false,
    height: 400,
    width: 350,
    modal: true,
    buttons: {
      Cancel: function() {
        dialog.dialog( "close" );
      }
    },
    close: function() {
      form[ 0 ].reset();
      allFields.removeClass( "ui-state-error" );
    }
  });

  $(".bmaw_submission_delete").click(function (event) {
    event.preventDefault();
    dialog.dialog( "open" );
  });

  $(".bmaw_submission_approve").click(function (event) {
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
  });
});
