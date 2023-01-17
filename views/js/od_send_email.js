console.log(window.od_send);

/**
 * envio de name y mail para hacer envio de mail
 *
 * @return string result
 */

$(document).on("click", ".od_sender", function () {
  let data = {
    nombre: prestashop.customer["firstname"],
    ajax: 1
  }; 
  $.ajax({
    type: "POST",
    url: window.od_send,
    data: data,
    dataType: "json",
    success: function (response) {
      $(".od_result").remove();
      $(".od_sender").after("<div class='od_result'>"+ response.result +"</div>");
    },
  });
});
