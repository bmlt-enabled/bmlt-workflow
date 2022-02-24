jQuery(document).ready(function ($) {
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
    var format_results_address = $("#bmaw_bmlt_server_address").val() + "/client_interface/jsonp/?switcher=GetFormats";

    fetchJsonp(format_results_address)
      .then((response) => response.json())
      .then((data) => {
        $("#bmaw_bmlt_test_status").val("success");
        $("#bmaw_test_yes").show();
        $("#bmaw_test_no").hide();
      })
      .catch((error) => {
        $("#bmaw_bmlt_test_status").val("failure");
        $("#bmaw_test_no").show();
        $("#bmaw_test_yes").hide();
      });
  });

  $("#bmaw_bmlt_server_address").on("change", function (event) {
    $("#bmaw_test_yes").hide();
    $("#bmaw_test_no").hide();
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
            '][name]" value=""/></td><td><input type="text" name="bmaw_service_committee_option_array[' +
            rowCount +
            '][e1]" value=""/></td><td><input type="text" name="bmaw_service_committee_option_array[' +
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
