jQuery(document).ready(function ($) {
  $(".bmaw-userlist").each(function () {
    console.log("found " + $(this).attr("id"));
    var id = $(this).attr('id');
    url = bmaw_admin_bmaw_service_areas_rest_url +"/"+id.substring(id.indexOf("_id_") + 4, id.length)
    $.ajax({
      url: url,
      dataType: "json",
      beforeSend: function (xhr) {
        xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
      },
    }).done(function (response, id) {
      response["results"].forEach((element) => {
        var opt = new Option(element.text, element.id, false, element.selected);
        $('#'+this.id).append(opt).trigger("change");
      });
    });
  });

  $(".bmaw-userlist").select2({
    multiple:true,
    width: '100%'
  });
});
