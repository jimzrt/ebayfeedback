/**
 * 2007-2020 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2020 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

$(function () {
  document.getElementById("EBAYFEEDBACK_USERNAME").oninput = function () {
    document.getElementById("EBAYFEEDBACK_ACTIVE_off").click();
    document.getElementById("EBAYFEEDBACK_ACTIVE_on").disabled = true;
    document.getElementById("EBAYFEEDBACK_ACTIVE_off").disabled = true;
    document.getElementById("EBAYFEEDBACK_VALIDATED").value = false;
    document
      .querySelector(".EBAYFEEDBACK_validate_button")
      .classList.remove("hidden");
  };

  $("#module_form").submit(function (event) {
    event.preventDefault();
    document.getElementById("EBAYFEEDBACK_ACTIVE_on").disabled = false;
    document.getElementById("EBAYFEEDBACK_ACTIVE_off").disabled = false;
    $(this).unbind("submit").submit();
  });
});

function validate() {
  $("#loading_overlay").addClass("ebayfeedback_loading");
  $.ajax({
    method: "POST",
    url: ebayfeedback.feedback_url,
    data: { userName: document.getElementById("EBAYFEEDBACK_USERNAME").value },
  })
    .done(function () {
        $.growl.notice({
            title: "",
            size: "large",
            message: "Success!"
        });
      document.getElementById("EBAYFEEDBACK_ACTIVE_on").disabled = false;
      document.getElementById("EBAYFEEDBACK_ACTIVE_off").disabled = false;
      document.getElementById("EBAYFEEDBACK_VALIDATED").value = true;
      document
        .querySelector(".EBAYFEEDBACK_validate_button")
        .classList.add("hidden");
    })
    .fail(function (msg) {  
      try {
        errorMessage = JSON.parse(msg.responseText).message
      } catch(e) {
        errorMessage = "Unknown Error!"
      }
      $.growl.error({
        title: "",
        size: "large",
        message: errorMessage
    });
    })
    .always(function(){
        $("#loading_overlay").removeClass("ebayfeedback_loading");
    });
}
