// Copyright (C) 2022 nigel.bmlt@gmail.com
// 
// This file is part of bmlt-workflow.
// 
// bmlt-workflow is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// 
// bmlt-workflow is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with bmlt-workflow.  If not, see <http://www.gnu.org/licenses/>.



jQuery(document).ready(function ($) {

  // click and display handler for fso options
  if(bw_fso_feature == 'hidden')
  {
    $("#fso_options").hide();
  } else {
    $("#fso_options").show();
  }

  $("#bw_fso_feature").on("change", function () {
    if (this.value == "hidden") {
      $("#fso_options").hide();
    } else {
      $("#fso_options").show();
    }
  });

  // click handler for hidden file browser button
  $("#bw_restore").on("click", function () {
    $("#bw_file_selector").trigger("click");
  });

  // perform a restore
  $("#bw_file_selector").on("change", function () {
    clear_notices();
    $("#bw_restore_warning_dialog").dialog("open");
  });

  $("#bw_restore_warning_dialog").dialog({
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
        restore_fr.readAsText($("#bw_file_selector")[0].files[0]);
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
  restore_fr.onload = function (e) {
    $.ajax({
      url: bw_admin_restore_rest_url,
      method: "POST",
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      data: e.target.result,
      processData: false,
      beforeSend: function (xhr) {
        turn_on_spinner("#bw-backup-spinner");
        clear_notices();
        xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
      },
    }).done(function (response) {
      turn_off_spinner("#bw-backup-spinner");
      notice_success(response, "bw-error-message");
    }).fail(function (xhr) {
      notice_error(xhr, "bw-error-message");
      turn_off_spinner("#bw-backup-spinner");
    });

  };


  // click handler for bmlt configuration popup
  $("#bw_configure_bmlt_server").on("click", function (event) {
    clear_notices();
    $("#bw_bmlt_configuration_dialog").dialog("open");
  });

  // update the test status
  get_test_status().then((data) => {
    update_from_test_result(data);
  });

  // click handler for backup
  $("#bw_backup").on("click", function () {
    $.ajax({
      url: bw_admin_backup_rest_url,
      method: "POST",
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      processData: false,
      beforeSend: function (xhr) {
        turn_on_spinner("#bw-backup-spinner");
        clear_notices();
        xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
      },
    })
      .done(function (response) {
        turn_off_spinner("#bw-backup-spinner");
        notice_success(response, "bw-error-message");
        var blob = new Blob([response.backup], { type: "application/json" });
        var link = document.createElement("a");
        var b_elem = document.getElementById("bw_backup_filename");
        if(b_elem != null)
        {
          b_elem.parentNode.removeChild(b_elem);
        }
        link.setAttribute("id", "bw_backup_filename");
        link.href = window.URL.createObjectURL(blob);
        var d = new Date();
        var datetime =
          d.getFullYear().toString() +
          ("0" + (d.getMonth() + 1).toString()).slice(-2) +
          ("0" + d.getDate().toString()).slice(-2) +
          ("0" + d.getHours().toString()).slice(-2) +
          ("0" + d.getMinutes().toString()).slice(-2);
        link.download = "backup-" + datetime + ".json";
        // stick it in the dom so we can find it later
        document.getElementById('bw_file_selector').appendChild(link);
        link.click();
      })
      .fail(function (xhr) {
        notice_error(xhr, "bw-error-message");
        turn_off_spinner("#bw-backup-spinner");
      });
  });

  var clipboard = new ClipboardJS(".clipboard-button");

  $("#bw_bmlt_change_server_warning_dialog").dialog({
    title: "BMLT Configuration Change Warning",
    autoOpen: false,
    draggable: false,
    width: "auto",
    maxWidth: "auto",
    zindex: 1001,
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
        if($("#yesimsure").prop("checked") == true)
        {
          wipe_service_bodies({"checked":"true"});
          save_results(this);
        }
        // trigger an update on the main page
        test_configuration(true);
        $(this).dialog("close");
        $("#bw_bmlt_change_server_warning_dialog").data("parent").dialog("close");
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

  $("#bw_bmlt_configuration_dialog").dialog({
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
        // check if server address changed
        if (bw_bmlt_server_address != $("#bw_bmlt_server_address").val()) {
          $("#bw_bmlt_change_server_warning_dialog").data("parent", $(this)).dialog("open");
        } else {
          save_results();
          // trigger an update on the main page
          test_configuration(true);
          $(this).dialog("close");
        }
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
    parameters["bw_bmlt_server_address"] = $("#bw_bmlt_server_address").val();
    parameters["bw_bmlt_username"] = $("#bw_bmlt_username").val();
    parameters["bw_bmlt_password"] = $("#bw_bmlt_password").val();

    $.ajax({
      url: bw_admin_bmltserver_rest_url,
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
        notice_success(response, "options_dialog_bw_error_message");
        if (saving) {
          update_from_test_result(response);
        }
      })
      .fail(function (xhr) {
        notice_error(xhr, "options_dialog_bw_error_message");
        if (saving) {
          update_from_test_result(xhr);
        }
      });
  }

  function get_test_status() {
    return new Promise((resolve) => {
      $.ajax({
        url: bw_admin_bmltserver_rest_url,
        type: "GET",
        dataType: "json",
        contentType: "application/json",
        beforeSend: function (xhr) {
          // clear_notices();
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
    if (data["bw_bmlt_test_status"] === "success") {
      $("#bw_bmlt_test_yes").show();
      $("#bw_bmlt_test_no").hide();
    } else {
      $("#bw_bmlt_test_no").show();
      $("#bw_bmlt_test_yes").hide();
    }

    // if (data["bw_servicebodies_test_status"] === "success") {
    //   $("#bw_servicebodies_test_yes").show();
    //   $("#bw_servicebodies_test_no").hide();
    // } else {
    //   $("#bw_servicebodies_test_no").show();
    //   $("#bw_servicebodies_test_yes").hide();
    // }

  }

  function save_results() {
    var parameters = {};
    parameters["bw_bmlt_server_address"] = $("#bw_bmlt_server_address").val();
    parameters["bw_bmlt_username"] = $("#bw_bmlt_username").val();
    parameters["bw_bmlt_password"] = $("#bw_bmlt_password").val();

    $.ajax({
      url: bw_admin_bmltserver_rest_url,
      type: "PATCH",
      dataType: "json",
      contentType: "application/json",
      data: JSON.stringify(parameters),
      beforeSend: function (xhr) {
        clear_notices();
        xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
      },
    }).done(function (response) {
      notice_success(response, "bw-error-message");
      update_from_test_result(response);
    })
    .fail(function (xhr) {
      notice_error(xhr, "bw-error-message");
      update_from_test_result(xhr);
    });

  }

  function wipe_service_bodies(parameters) {

    $.ajax({
      url: bw_admin_bw_service_bodies_rest_url,
      type: "DELETE",
      dataType: "json",
      data: JSON.stringify(parameters),
      contentType: "application/json",
      beforeSend: function (xhr) {
        xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
      },
    });
  }

});
