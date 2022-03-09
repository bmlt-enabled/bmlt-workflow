jQuery(document).ready(function ($) {

  console.log(bmaw_admin_submissions_rest_url);
  
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
  bmaw_create_row_link_modal("bmaw_submission_reject", "Reject Submission");

  bmaw_submission_approve_dialog_ok = function(id) { generic_approve_handler(id,'POST','/approve','bmaw_submission_approve') };
  bmaw_submission_reject_dialog_ok = function(id) { generic_approve_handler(id,'POST','/reject','bmaw_submission_reject') };
  bmaw_submission_delete_dialog_ok = function(id) { generic_approve_handler(id,'DELETE','','bmaw_submission_delete') };

  function generic_approve_handler(id, action, url, slug) {
    $parameters = {};
    if (!$.trim($("#"+slug+"_dialog_textarea").val())) {
      $parameters["custom_message"] = $("#"+slug+"_dialog_textarea");
    }
    // url = "/approve"
    $.ajax({
      url: "/flop/wp-json/bmaw-submission/v1/submissions/" + id + url,
      type: action,
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
        location.reload();
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

    $("#"+slug+"_dialog").dialog("close");
  };

//   bmaw_submission_approve_dialog_ok = function (id) {
//     $parameters = {};
//     if (!$.trim($("#bmaw_submission_approve_dialog_textarea").val())) {
//       $parameters["approve_message"] = $("#bmaw_submission_approve_dialog_textarea");
//     }

//     $.ajax({
//       url: "/flop/wp-json/bmaw-submission/v1/submissions/" + id + "/approve",
//       type: "POST",
//       beforeSend: function (xhr) {
//         xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
//       },
//     })
//       .done(function (response) {
//         var msg = "";
//         if (response.error_message == "")
//           msg =
//             '<div class="notice notice-success is-dismissible my_notice"><p><strong>SUCCESS: </strong>This is my success message.</p><button type="button" class="notice-dismiss" onclick="javascript: return px_dissmiss_notice(this);"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
//         else
//           msg =
//             '<div class="notice notice-error is-dismissible my_notice"><p><strong>ERROR: </strong>' +
//             response.error_message +
//             '.</p><button type="button" class="notice-dismiss" onclick="javascript: return px_dissmiss_notice(this);"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
//         $(".wp-header-end").after(msg);
//         location.reload();
//       })
//       .fail(function (xhr) {
//         $(".wp-header-end").after(
//           '<div class="notice notice-error is-dismissible my_notice"><p><strong>ERROR: </strong>' +
//             xhr.status +
//             " " +
//             xhr.statusText +
//             '.</p><button type="button" class="notice-dismiss" onclick="javascript: return px_dissmiss_notice(this);"><span class="screen-reader-text">Dismiss this notice.</span></button></div>'
//         );
//       });

//     $("#bmaw_submission_approve_dialog").dialog("close");
//   };

//   bmaw_submission_delete_dialog_ok = function (id) {
//     $.ajax({
//       url: "/flop/wp-json/bmaw-submission/v1/submissions/" + id,
//       type: "DELETE",
//       beforeSend: function (xhr) {
//         xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
//       },
//     })
//       .always(function (response) {
//         console.log(response);
//       })
//       .done(function (response) {
//         var msg = "";
//         if (response.error_message == "")
//           msg =
//             '<div class="notice notice-success is-dismissible my_notice"><p><strong>SUCCESS: </strong>This is my success message.</p><button type="button" class="notice-dismiss" onclick="javascript: return px_dissmiss_notice(this);"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
//         else
//           msg =
//             '<div class="notice notice-error is-dismissible my_notice"><p><strong>ERROR: </strong>' +
//             response.error_message +
//             '.</p><button type="button" class="notice-dismiss" onclick="javascript: return px_dissmiss_notice(this);"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
//         $(".wp-header-end").after(msg);
//         location.reload();
//       })
//       .fail(function (xhr) {
//         $(".wp-header-end").after(
//           '<div class="notice notice-error is-dismissible my_notice"><p><strong>ERROR: </strong>' +
//             xhr.status +
//             " " +
//             xhr.statusText +
//             '.</p><button type="button" class="notice-dismiss" onclick="javascript: return px_dissmiss_notice(this);"><span class="screen-reader-text">Dismiss this notice.</span></button></div>'
//         );
//       });

//     $("#bmaw_submission_delete_dialog").dialog("close");
//   };
});
