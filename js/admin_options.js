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
  if(bmltwf_fso_feature == 'hidden')
  {
    $("#fso_options").hide();
  } else {
    $("#fso_options").show();
  }
  // class="bmltwf_'.$option.'_disable"
  $(".bmltwf_optional_visible_checkbox").on("change", function(){
    // bmltwf_optional_postcode
    disableclass = '.' + this.id.slice(0,-('_visible_checkbox'.length)) + "_disable";
    if (this.checked) {
      $(disableclass).prop("disabled", true);
    }
  else {
    $(disableclass).prop("disabled", false);
  }
  });

  $("#bmltwf_fso_feature").on("change", function () {
    if (this.value == "hidden") {
      $("#fso_options").hide();
    } else {
      $("#fso_options").show();
    }
  });

  // click handler for hidden file browser button
  $("#bmltwf_restore").on("click", function () {
    $("#bmltwf_file_selector").trigger("click");
  });

  // perform a restore
  $("#bmltwf_file_selector").on("change", function () {
    clear_notices();
    $("#bmltwf_restore_warning_dialog").dialog("open");
  });

  $("#bmltwf_restore_warning_dialog").dialog({
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
        restore_fr.readAsText($("#bmltwf_file_selector")[0].files[0]);
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
      url: bmltwf_admin_restore_rest_url,
      method: "POST",
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      data: e.target.result,
      processData: false,
      beforeSend: function (xhr) {
        turn_on_spinner("#bmltwf-backup-spinner");
        clear_notices();
        xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
      },
    }).done(function (response) {
      turn_off_spinner("#bmltwf-backup-spinner");
      notice_success(response, "bmltwf-error-message");
    }).fail(function (xhr) {
      notice_error(xhr, "bmltwf-error-message");
      turn_off_spinner("#bmltwf-backup-spinner");
    });

  };


  // click handler for bmlt configuration popup
  $("#bmltwf_configure_bmlt_server").on("click", function (event) {
    clear_notices();
    $("#bmltwf_bmlt_configuration_dialog").dialog("open");
  });

  // update the test status
  get_test_status().then((data) => {
    update_from_test_result(data);
  });

  // click handler for backup
  $("#bmltwf_backup").on("click", function () {
    $.ajax({
      url: bmltwf_admin_backup_rest_url,
      method: "POST",
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      processData: false,
      beforeSend: function (xhr) {
        turn_on_spinner("#bmltwf-backup-spinner");
        clear_notices();
        xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
      },
    })
      .done(function (response) {
        turn_off_spinner("#bmltwf-backup-spinner");
        notice_success(response, "bmltwf-error-message");
        var blob = new Blob([response.backup], { type: "application/json" });
        var link = document.createElement("a");
        var b_elem = document.getElementById("bmltwf_backup_filename");
        if(b_elem != null)
        {
          b_elem.parentNode.removeChild(b_elem);
        }
        link.setAttribute("id", "bmltwf_backup_filename");
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
        document.getElementById('bmltwf_file_selector').appendChild(link);
        link.click();
      })
      .fail(function (xhr) {
        notice_error(xhr, "bmltwf-error-message");
        turn_off_spinner("#bmltwf-backup-spinner");
      });
  });

  var clipboard = new ClipboardJS(".clipboard-button");

  $("#bmltwf_bmlt_change_server_warning_dialog").dialog({
    title: "BMLT Root Server Configuration Change Warning",
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
        $("#bmltwf_bmlt_change_server_warning_dialog").data("parent").dialog("close");
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

  $("#bmltwf_bmlt_configuration_dialog").dialog({
    title: "BMLT Root Server Configuration",
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
        if (bmltwf_bmlt_server_address != $("#bmltwf_bmlt_server_address").val()) {
          $("#bmltwf_bmlt_change_server_warning_dialog").data("parent", $(this)).dialog("open");
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
    parameters["bmltwf_bmlt_server_address"] = $("#bmltwf_bmlt_server_address").val();
    parameters["bmltwf_bmlt_username"] = $("#bmltwf_bmlt_username").val();
    parameters["bmltwf_bmlt_password"] = $("#bmltwf_bmlt_password").val();

    $.ajax({
      url: bmltwf_admin_bmltserver_rest_url,
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
        notice_success(response, "options_dialog_bmltwf_error_message");
        if (saving) {
          update_from_test_result(response);
        }
      })
      .fail(function (xhr) {
        notice_error(xhr, "options_dialog_bmltwf_error_message");
        if (saving) {
          update_from_test_result(xhr);
        }
      });
  }

  function get_test_status() {
    return new Promise((resolve) => {
      $.ajax({
        url: bmltwf_admin_bmltserver_rest_url,
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
    if (data["bmltwf_bmlt_test_status"] === "success") {
      $("#bmltwf_bmlt_test_yes").show();
      $("#bmltwf_bmlt_test_no").hide();
    } else {
      $("#bmltwf_bmlt_test_no").show();
      $("#bmltwf_bmlt_test_yes").hide();
    }

    // if (data["bmltwf_servicebodies_test_status"] === "success") {
    //   $("#bmltwf_servicebodies_test_yes").show();
    //   $("#bmltwf_servicebodies_test_no").hide();
    // } else {
    //   $("#bmltwf_servicebodies_test_no").show();
    //   $("#bmltwf_servicebodies_test_yes").hide();
    // }

  }

  function save_results() {
    var parameters = {};
    parameters["bmltwf_bmlt_server_address"] = $("#bmltwf_bmlt_server_address").val();
    parameters["bmltwf_bmlt_username"] = $("#bmltwf_bmlt_username").val();
    parameters["bmltwf_bmlt_password"] = $("#bmltwf_bmlt_password").val();

    $.ajax({
      url: bmltwf_admin_bmltserver_rest_url,
      type: "PATCH",
      dataType: "json",
      contentType: "application/json",
      data: JSON.stringify(parameters),
      beforeSend: function (xhr) {
        clear_notices();
        xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
      },
    }).done(function (response) {
      notice_success(response, "bmltwf-error-message");
      update_from_test_result(response);
    })
    .fail(function (xhr) {
      notice_error(xhr, "bmltwf-error-message");
      update_from_test_result(xhr);
    });

  }

  function wipe_service_bodies(parameters) {

    $.ajax({
      url: bmltwf_admin_bmltwf_service_bodies_rest_url,
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
