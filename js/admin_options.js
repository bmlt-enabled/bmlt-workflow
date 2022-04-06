function dismiss_notice(element) {
  jQuery(element)
    .parent()
    .slideUp("normal", function () {
      jQuery(this).remove();
    });
  return false;
}

jQuery(document).ready(function ($) {
  function clear_notices() {
    jQuery(".notice-dismiss").each(function (i, e) {
      dismiss_notice(e);
    });
  }

  function notice_success(response,notice_class) {
    var msg = "";
    if (response.message == "")
      msg =
        '<div class="notice notice-success is-dismissible"><p><strong>SUCCESS: </strong><button type="button" class="notice-dismiss" onclick="javascript: return dismiss_notice(this);"></button></div>';
    else
      msg =
        '<div class="notice notice-success is-dismissible"><p><strong>SUCCESS: </strong>' +
        response.message +
        '.</p><button type="button" class="notice-dismiss" onclick="javascript: return dismiss_notice(this);"></button></div>';
    $("."+notice_class).after(msg);
  }

  function notice_error(xhr,notice_class) {
    $("."+notice_class).after(
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
      "Save and Close": function () {
        save_and_close(this);
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

  if (test_status == "success") {
    $("#wbw_test_yes").show();
    $("#wbw_test_no").hide();
  } else {
    $("#wbw_test_no").show();
    $("#wbw_test_yes").hide();
  }

  $("form").on("submit", function () {
    $("#wbw_new_meeting_template_default").attr("disabled", "disabled");
    $("#wbw_existing_meeting_template_default").attr("disabled", "disabled");
    $("#wbw_other_meeting_template_default").attr("disabled", "disabled");
    $("#wbw_close_meeting_template_default").attr("disabled", "disabled");
  });

  $("#wbw_bmlt_test_status").val(test_status);

  $("#wbw_configure_bmlt_server").on("click", function (event) {
    $("#wbw_bmlt_configuration_dialog").dialog("open");
  });

  $("#wbw_test_bmlt_server").on("click", function (event) {
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
        notice_success(response, 'quickedit-wp-header-end');
        $("#wbw_bmlt_test_status").val("success");
        $("#wbw_test_yes").show();
        $("#wbw_test_no").hide();
      })
      .fail(function (xhr) {
        notice_error(xhr,'quickedit-wp-header-end');
        $("#wbw_bmlt_test_status").val("failure");
        $("#wbw_test_no").show();
        $("#wbw_test_yes").hide();
      });
  });

  function save_and_close(element) {
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
      }
    })

      .done(function (response) {
        notice_success(response,'quickedit-wp-header-end');
        $(element).dialog("close");
      })

      .fail(function (xhr) {
        notice_error(xhr,'quickedit-wp-header-end');
      });
  }
});
