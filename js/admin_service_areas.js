
jQuery(document).ready(function ($) {
    $('.bmaw-userlist').select2({
        ajax: {
          url: bmaw_admin_bmaw_users_rest_url + "/" + this.id.substring(this.id.indexOf("_id_") + 4, this.id.length),
          dataType: 'json',
          beforeSend: function (xhr) {
            xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
          },    
          // Additional AJAX parameters go here; see the end of this chapter for the full code of this example
        }
      });
    });