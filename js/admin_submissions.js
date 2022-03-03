jQuery(document).ready(function ($) {
  $('#approve').click(function(event){
    event.preventDefault(); 
    $.post('/flop/wp-json/bmaw-submission/v1/submissions/12/approve', function(response){
       alert(response);
    });
 });
});
