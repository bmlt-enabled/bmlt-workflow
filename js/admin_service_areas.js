jQuery(document).ready(function ($) {
    $('#bmaw-userlist-1').select2({
        ajax: {
          url: bmaw_admin_bmaw_users_rest_url,
          dataType: 'json'
          // Additional AJAX parameters go here; see the end of this chapter for the full code of this example
        }
      });
    });