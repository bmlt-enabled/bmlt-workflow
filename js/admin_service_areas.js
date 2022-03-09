
jQuery(document).ready(function ($) {
    $('.bmaw-userlist').select2({
        ajax: {
          url: function () { return bmaw_admin_bmaw_service_areas_rest_url + "/" + $(this).attr('id').substring($(this).attr('id').indexOf("_id_") + 4, $(this).attr('id').length) },
          dataType: 'json',
          beforeSend: function (xhr) {
            xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
          },    
          // Additional AJAX parameters go here; see the end of this chapter for the full code of this example
        }
      });
    });