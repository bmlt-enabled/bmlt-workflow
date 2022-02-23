jQuery(document).ready(function ($) {
  if(bmaw_test_successful == "succeeded")
  {
    $("#bmaw_test_yes").show();
    $("#bmaw_test_no").hide();  
  }
  else
  {
    $("#bmaw_test_no").show();
    $("#bmaw_test_yes").hide();  
  }

  $("#bmaw_test_successful").val(bmaw_test_successful);

  $("#bmaw_test_bmlt_server").on("click", function (event) {
    console.log("clicked");
    var format_results_address = $("#bmaw_bmlt_server_address").val() + "/client_interface/jsonp/?switcher=GetFormats";

    fetchJsonp(format_results_address)
      .then((response) => response.json())
      .then((data) => {
        console.log("validated ok");
        $("#bmaw_test_successful").val("succeeded");
        $("#bmaw_test_yes").show();
        // update_option("bmaw_test_successful", "true");
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
