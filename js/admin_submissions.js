jQuery(document).ready(function ($) {
  $("#approve").click(function (event) {
    event.preventDefault(); 
    $.post(
      "/flop/wp-json/bmaw-submission/v1/submissions/21/approve",
      {
          _wpnonce: $("#_wpnonce").val(),
      },
      function (response) {
        // alert(response);
      }
    );
  });
});
