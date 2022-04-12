function dismiss_notice(element) {
  jQuery(element)
    .parent()
    .slideUp("normal", function () {
      jQuery(this).remove();
    });
  return false;
}

jQuery(document).ready(function ($) {

  get_test_status()
  .then((data) => {
    update_from_test_result(data);
  });

  function clear_notices() {
    jQuery(".notice-dismiss").each(function (i, e) {
      dismiss_notice(e);
    });
  }

  function notice_success(response, notice_class) {
    var msg = "";
    if (response.message == "")
      msg =
        '<div class="notice notice-success is-dismissible"><p><strong>SUCCESS: </strong><button type="button" class="notice-dismiss" onclick="javascript: return dismiss_notice(this);"></button></div>';
    else
      msg =
        '<div class="notice notice-success is-dismissible"><p><strong>SUCCESS: </strong>' +
        response.message +
        '.</p><button type="button" class="notice-dismiss" onclick="javascript: return dismiss_notice(this);"></button></div>';
    $("." + notice_class).after(msg);
  }

  function notice_error(xhr, notice_class) {
    $("." + notice_class).after(
      '<div class="notice notice-error is-dismissible"><p><strong>ERROR: </strong>' +
        xhr.responseJSON.message +
        '.</p><button type="button" class="notice-dismiss" onclick="javascript: return dismiss_notice(this);"></button></div>'
    );
  }

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
        test_configuration();
      },
      "Save and Close": function () {
        save_results(this);
        // trigger an update on the main page
        test_configuration().then(update_from_test_result(data), update_from_test_result(data));
        // }).catch((data) => {
        //   update_from_test_result(data);
        // });
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

  function test_configuration() {
    return new Promise((resolve) => {

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
        notice_success(response, "quickedit-wp-header-end");
        resolve(response);
      })
      .fail(function (xhr) {
        notice_error(xhr, "quickedit-wp-header-end");
        resolve(xhr);
      })
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
