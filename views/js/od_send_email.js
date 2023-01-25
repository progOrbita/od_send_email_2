/**
 * envio de name y mail para hacer envio de mail
 *
 * @return string result
 */
$(document).on("click", ".od_sender", function () {
  let data = {
    nombre: window.name,
    mail: window.mail,
    ajax: 1
  };
  $.ajax({
    type: "POST",
    url: window.od_send_url,
    data: data,
    dataType: "json",
    success: function (response) {
      $(".od_result").remove();
      $(".od_sender").after("<div class='od_result'>" + response.result + "</div>");
    },
  });
});
