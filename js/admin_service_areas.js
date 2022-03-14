jQuery(document).ready(function ($) {

  $(".bmaw-userlist").select2({
    multiple:true,
    width: '100%'
  });

  url = wp_rest_base + bmaw_admin_bmaw_service_areas_rest_route;

  $.ajax({
    url: url,
    dataType: "json",
    beforeSend: function (xhr) {
      xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
    },
  }).done(function (response) {
    url = wp_rest_base + 'wp/v2/users';
    $.ajax({
      url: url,
      dataType: "json",
      sblist: response,
      beforeSend: function (xhr) {
        xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
      },
    }).done(function (response) {
      console.log('service body list');
      console.log(this.sblist);
      console.log('userlist');
      console.log(response);
      $('#bmaw-userlist-table tbody').append("<tr><td>lol</td><td>lol2</td></tr>")
    });
  });
});


		// $request = new WP_REST_Request('GET', '/wp/v2/users');
		// $result = rest_do_request($request);

		// $data = $result->get_data();
		// $select = array('results' => array());
		// foreach ($data as $user) {
		// 	$data = array('id' => $user['id'], 'text' => $user['name']);
		// 	// if we have a match from the administration list, mark it as selected
		// 	if (in_array($user['id'], $arr)) {
		// 		$data['selected'] = true;
		// 	}
		// 	$select['results'][] = $data;
		// }


// response["results"].forEach((element) => {
//   var opt = new Option(element.text, element.id, false, element.selected);
//   $('#'+this.custom).append(opt).trigger("change");

// $(".bmaw-userlist").each(function () {
//   console.log("found " + $(this).attr("id"));
// });

// <?php
// foreach ($sblist as $item) {
//     echo '<tr class="bmaw-userlist-row">';
//     echo '<td>'.$item['name'].'</td>';
//     echo '<td><select class="bmaw-userlist" id="bmaw_userlist_id_'.$item['id'].'" style="width: auto"></select></td></tr>';
// }
// echo '</tr>';
// ?>
