var clipboard = new ClipboardJS(".clipboard-button");

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

  if (test_status == "success") {
    $("#bmaw_test_yes").show();
    $("#bmaw_test_no").hide();
  } else {
    $("#bmaw_test_no").show();
    $("#bmaw_test_yes").hide();
  }

  $("form").on("submit", function () {
    $("#bmaw_new_meeting_template_default").attr("disabled", "disabled");
    $("#bmaw_existing_meeting_template_default").attr("disabled", "disabled");
    $("#bmaw_other_meeting_template_default").attr("disabled", "disabled");
    $("#bmaw_close_meeting_template_default").attr("disabled", "disabled");
  });

  $("#bmaw_bmlt_test_status").val(test_status);

  $("#bmaw_test_bmlt_server").on("click", function (event) {

    var parameters={};
    parameters['bmaw_bmlt_server_address'] = $("#bmaw_bmlt_server_address").val();
    parameters['bmaw_bmlt_username'] = $("#bmaw_bmlt_username").val();
    parameters['bmaw_bmlt_password'] = $("#bmaw_bmlt_password").val();

    $.ajax({
      url: bmaw_admin_bmltserver_rest_url,
      type: 'POST',
      dataType: 'json',
      contentType: 'application/json',
      data: JSON.stringify(parameters),
      beforeSend: function (xhr) {
        clear_notices();
        xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
      },
    })
      .done(function (response) {
        var msg = "";
        if (response.message == "")
          msg =
            '<div class="notice notice-success is-dismissible"><p><strong>SUCCESS: </strong><button type="button" class="notice-dismiss" onclick="javascript: return dismiss_notice(this);"></button></div>';
        else
          msg =
            '<div class="notice notice-success is-dismissible"><p><strong>SUCCESS: </strong>' +
            response.message +
            '.</p><button type="button" class="notice-dismiss" onclick="javascript: return dismiss_notice(this);"></button></div>';
        $(".wp-header-end").after(msg);
      })
      .fail(function (xhr) {
        $(".wp-header-end").after(
          '<div class="notice notice-error is-dismissible"><p><strong>ERROR: </strong>' +
            xhr.responseJSON.message +
            '.</p><button type="button" class="notice-dismiss" onclick="javascript: return dismiss_notice(this);"></button></div>'
        );
      });
    });

  $("#bmaw-service-committee-table tbody").on("click", "tr td:nth-child(4)", function (event) {
    var rowCount = $("#bmaw-service-committee-table tr").length - 2;
    var clicked = $(this).closest("tr").index();
    console.log("table length " + rowCount + " row clicked " + clicked);
    if (clicked == rowCount) {
      console.log("add row");
      $("#bmaw-service-committee-table > tbody > tr")
        .eq(rowCount - 1)
        .after(
          '<tr><td><input type="text" name="bmaw_service_committee_option_array[' +
            rowCount +
            '][name]" value="" required/></td><td><input type="text" name="bmaw_service_committee_option_array[' +
            rowCount +
            '][e1]" value="" required/></td><td><input type="text" name="bmaw_service_committee_option_array[' +
            rowCount +
            '][e2]" value=""/></td><td><span id="bmaw-service-committee-new-row" class="dashicons dashicons-remove"></span></td></tr>'
        );
    } else if (rowCount != 1) {
      $("#bmaw-service-committee-table > tbody > tr").eq(clicked).remove();
      for (var i = clicked; i < rowCount; i++) {
        console.log("i = " + i + " rowCount = " + rowCount);
        var row = $("#bmaw-service-committee-table > tbody > tr").eq(i);
        row.find("td input").each(function () {
          var a = $(this).attr("name");
          console.log("a = " + a);
          var b = a.replace(/\[[0-9]\]/i, "[" + i + "]");
          console.log("b = " + b);
          $(this).attr("name", b);
        });
      }
    }
  });
});
