jQuery(document).ready(function ($) {
  $(".bmaw_approve").click(function (event) {
    event.preventDefault();
    var id = this.id.replace("bmaw_approve_id_","");
    $.post(
      "/flop/wp-json/bmaw-submission/v1/submissions/"+id+"/approve",
      {
          _wpnonce: $("#_wprestnonce").val(),
      },
      function (response) {
        // alert(response);
      }
    );
  });
});
