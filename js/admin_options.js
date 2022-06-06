// function dismiss_notice(element) {
//   jQuery(element)
//     .parent()
//     .slideUp("normal", function () {
//       jQuery(this).remove();
//     });
//   return false;
// }

jQuery(document).ready(function ($) {

  get_test_status()
  .then((data) => {
    update_from_test_result(data);
  });

  // function clear_notices() {
  //   jQuery(".notice-dismiss").each(function (i, e) {
  //     dismiss_notice(e);
  //   });
  // }

  $("#wbw_backup").on('click', function () {
    $.ajax({
      url: wbw_admin_backup_rest_url,
      method: "POST",
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      processData: false,
      beforeSend: function (xhr) {
        turn_on_spinner("#wbw-backup-spinner");
        clear_notices();
        xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
      },
    })
      .done(function (response) {
        turn_off_spinner("#wbw-backup-spinner");
        notice_success(response, "wbw-error-message");
        var blob=new Blob(response.backup);
        var link=document.createElement('a');
        link.href=window.URL.createObjectURL(blob);
        link.download="myFileName.txt";
        link.click();
      })
      .fail(function (xhr) {
        notice_error(xhr, "wbw-error-message");
        turn_off_spinner("#wbw-backup-spinner");
      });
  });

  var clipboard = new ClipboardJS(".clipboard-button");

  $("#wbw_bmlt_configuration_dialog").dialog({
    title: "BMLT Configuration",
    autoOpen: false,
    draggable: false,
    width: "auto",
    maxWidth: "auto",
    modal: true,
    resizable: false,
    closeOnEscape: true,
    position: {
      my: "center",
      at: "center",
      of: window,
    },
    buttons: {
      "Test Configuration": function () {
        test_configuration(false);
      },
      "Save and Close": function () {
        save_results(this);
        // trigger an update on the main page
        test_configuration(true);
        $(this).dialog("close");
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

  $("form").on("submit", function () {
    $("#wbw_new_meeting_template_default").attr("disabled", "disabled");
    $("#wbw_existing_meeting_template_default").attr("disabled", "disabled");
    $("#wbw_other_meeting_template_default").attr("disabled", "disabled");
    $("#wbw_close_meeting_template_default").attr("disabled", "disabled");
  });

  $("#wbw_configure_bmlt_server").on("click", function (event) {
    clear_notices();
    $("#wbw_bmlt_configuration_dialog").dialog("open");
  });

  function test_configuration(saving) {

    var parameters = {};
    parameters["wbw_bmlt_server_address"] = $("#wbw_bmlt_server_address").val();
    parameters["wbw_bmlt_username"] = $("#wbw_bmlt_username").val();
    parameters["wbw_bmlt_password"] = $("#wbw_bmlt_password").val();

    $.ajax({
      url: wbw_admin_bmltserver_rest_url,
      type: "POST",
      dataType: "json",
      contentType: "application/json",
      data: JSON.stringify(parameters),
      beforeSend: function (xhr) {
        clear_notices();
        xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
      },
    })
      .done(function (response) {
        notice_success(response, "quickedit-wbw-error-message");
        if(saving)
        {
          update_from_test_result(response);
        }
      })
      .fail(function (xhr) {
        notice_error(xhr, "quickedit-wbw-error-message");
        if(saving)
        {
          update_from_test_result(xhr);
        }
      })
    }

  
  function get_test_status() {
    return new Promise((resolve) => {
    $.ajax({
      url: wbw_admin_bmltserver_rest_url,
      type: "GET",
      dataType: "json",
      contentType: "application/json",
      beforeSend: function (xhr) {
        clear_notices();
        xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
      },
    }).done(function (response) {
      resolve(response)
    })
    .fail(function (xhr) {
      resolve(xhr);
    })
  })
  }

  function update_from_test_result(data) {
    if (data['wbw_bmlt_test_status'] === "success") {
      $("#wbw_test_yes").show();
      $("#wbw_test_no").hide();
    } else {
      $("#wbw_test_no").show();
      $("#wbw_test_yes").hide();
    }
  }

  function save_results(element) {
    var parameters = {};
    parameters["wbw_bmlt_server_address"] = $("#wbw_bmlt_server_address").val();
    parameters["wbw_bmlt_username"] = $("#wbw_bmlt_username").val();
    parameters["wbw_bmlt_password"] = $("#wbw_bmlt_password").val();

    $.ajax({
      url: wbw_admin_bmltserver_rest_url,
      type: "PATCH",
      dataType: "json",
      contentType: "application/json",
      data: JSON.stringify(parameters),
      beforeSend: function (xhr) {
        clear_notices();
        xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
      },
    })
  }
});
