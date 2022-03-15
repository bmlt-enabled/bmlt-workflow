jQuery(document).ready(function ($) {
  function attach_select_options_for_sbid(sblist, userlist, sbid, selectid) {
    Object.keys(userlist).forEach((item) => {
      var wp_uid = userlist[item]["id"];
      var username = userlist[item]["slug"];
      var membership = sblist[sbid]["membership"];
      var selected = false;
      if (membership.includes(wp_uid)) {
        selected = true;
      }
      var opt = new Option(username, wp_uid, false, selected);
      $(selectid).append(opt);
      // console.log(opt);
    });
    $(selectid).trigger("change");
  }

  // {
  //   1: { name: "nigel test area", membership: "1,2" },
  //   2: { name: "another top level SC", membership: "" },
  //   3: { name: "second level SC under nigel", membership: "" },
  //   4: { name: "another second level sc", membership: "" },
  // };

  function create_service_area_permission_post() {
    ret = {};
    $(".bmaw-userlist").each(function () {
      console.log("got real id " + $(this).data("id"));
      id =  $(this).data("id");
      console.log("got name " + $(this).data("name"));
      sbname = $(this).data("name");
      console.log("select vals = "+ $(this).val());
      membership = $(this).val();
      console.log("got show_on_form = "+ $(this).data("show_on_form"));
      show_on_form = $(this).data("show_on_form");

      ret[id] = { "name":sbname, "show_on_form": show_on_form, "membership":membership};
    });
    return ret;
  }

  $("#bmaw_submit").on("click", function () {
    console.log("clicked");
    $('#bmaw-userlist-table tbody tr').each(function() {
      tr = $(this);
      checked = $(tr).find('input:checkbox').prop('checked');
      console.log("got "+checked);
      select = $(tr).find('select');
      select.data('show_on_form', checked);
    });
    post = create_service_area_permission_post();
    console.log("post = "+post);
    $.ajax({
      url: wp_rest_base + bmaw_admin_bmaw_service_areas_rest_route,
      method: 'POST',
      data: JSON.stringify(post),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      processData: false,
      beforeSend: function (xhr) {
        xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
      },
    }).done(function (response) {
      console.log("posted");
    });
  });

  // get the permissions, and the userlist from wordpress, and create our select lists
  $.ajax({
    url: wp_rest_base + bmaw_admin_bmaw_service_areas_rest_route,
    dataType: "json",
    beforeSend: function (xhr) {
      xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
    },
  }).done(function (response) {
    $.ajax({
      url: wp_rest_base + "wp/v2/users",
      dataType: "json",
      sblist: response,
      beforeSend: function (xhr) {
        xhr.setRequestHeader("X-WP-Nonce", $("#_wprestnonce").val());
      },
    }).done(function (response) {
      console.log("service body list");
      console.log(this.sblist);
      console.log("userlist");
      // var select_userlist = { "results": {} };

      console.log(response);
      var sblist = this.sblist;
      var userlist = response;
      Object.keys(sblist).forEach((item) => {
        var id = "bmaw_userlist_id_" + item;
        var checked = sblist[item]["show_on_form"]?("checked"):("");
        var appendstr = "<tr>";

        appendstr += "<td>" + sblist[item]["name"] + "</td>";
        appendstr += '<td><input type="checkbox" '+checked+'></td>';
        appendstr += '<td><select class="bmaw-userlist" id="' + id + '" style="width: auto"></select></td>';
        appendstr += "</tr>";
        $("#bmaw-userlist-table tbody").append(appendstr);
        // store metadata away for later
        $("#"+id).data('id', item);
        $("#"+id).data('name', sblist[item]["name"]);
        
        $(".bmaw-userlist").select2({
          multiple: true,
          width: "100%",
        });
        attach_select_options_for_sbid(sblist, userlist, item, "#" + id);
      });
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
