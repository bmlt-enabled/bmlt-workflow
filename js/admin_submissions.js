jQuery(document).ready(function ($) {

  var info = $("#modal-content");
  info.dialog({
    dialogClass: "wp-dialog",
    modal: true,
    autoOpen: false,
    closeOnEscape: true,
    buttons: {
      Close: function () {
        $(this).dialog("close");
      },
    },
  });

  $(".bmaw_submission_delete").click(function (event) {
    event.preventDefault();
    $info.dialog("open");
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
