jQuery(document).ready(function ($) {

  // click handler for hidden file browser button
  $("#wbw_restore").on("click", function () {
    $("#wbw_file_selector").trigger("click");
  });

  // perform a restore
  $("#wbw_file_selector").on("change", function () {
    clear_notices();
    $("#wbw_bmlt_erase_warning_dialog").dialog("open");
  });

  $("#wbw_bmlt_erase_warning_dialog").dialog({
    title: "Clear plugin warning",
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
      Ok: function () {
        // trigger the restore
        restore_fr.readAsText($("#wbw_file_selector")[0].files[0]);
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

  // restore hook
  var restore_fr = new FileReader();
  fr.onload = function (e) {
    $.ajax({
      url: wbw_admin_restore_rest_url,
      method: "POST",
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      data: e.target.result,
      processData: false,
      beforeSend: function (xhr) {
        turn_on_spinner("#wbw-backup-spinner");
        clear_notices();
        xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
      },
    }).done(function (response) {
      turn_off_spinner("#wbw-backup-spinner");
      notice_success(response, "wbw-error-message");
    }).fail(function (xhr) {
      notice_error(xhr, "wbw-error-message");
      turn_off_spinner("#wbw-backup-spinner");
    });

  };


  // click handler for bmlt configuration popup
  $("#wbw_configure_bmlt_server").on("click", function (event) {
    clear_notices();
    $("#wbw_bmlt_configuration_dialog").dialog("open");
  });

  // update the test status
  get_test_status().then((data) => {
    update_from_test_result(data);
  });

  // click handler for backup
  $("#wbw_backup").on("click", function () {
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
        var blob = new Blob([response.backup], { type: "application/json" });
        var link = document.createElement("a");
        link.href = window.URL.createObjectURL(blob);
        var d = new Date();
        var datetime =
          d.getFullYear().toString() +
          ("0" + (d.getMonth() + 1).toString()).slice(-2) +
          ("0" + d.getDate().toString()).slice(-2) +
          ("0" + d.getHours().toString()).slice(-2) +
          ("0" + d.getMinutes().toString()).slice(-2);
        link.download = "backup-" + datetime + ".json";
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
        notice_success(response, "options_dialog_wbw_error_message");
        if (saving) {
          update_from_test_result(response);
        }
      })
      .fail(function (xhr) {
        notice_error(xhr, "options_dialog_wbw_error_message");
        if (saving) {
          update_from_test_result(xhr);
        }
      });
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
      })
        .done(function (response) {
          resolve(response);
        })
        .fail(function (xhr) {
          resolve(xhr);
        });
    });
  }

  function update_from_test_result(data) {
    if (data["wbw_bmlt_test_status"] === "success") {
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
    });
  }
});
