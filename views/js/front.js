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

function select_feedback(elem, index) {
  elem.parentNode.parentNode.previousElementSibling.querySelector(
    ".feedback_slider"
  ).style.left = index * -100 + "%";
  elem.parentNode
    .querySelector(".feedback_dot_selected")
    .classList.remove("feedback_dot_selected");

  elem.classList.add("feedback_dot_selected");
}

$(function () {
  $(".feedback_result").load(ebayfeedback.feedback_url, function (
    response,
    status,
    xhr
  ) {
    if (status == "error") {
      // var msg = "Sorry but there was an error: ";
      // $("#feedback_result").html( msg + xhr.status + " " + xhr.statusText );
      $(".feedback_result").empty();
      return;
    }
    document.querySelectorAll(".full-stars").forEach(function (stars) {
      //trigger render for transition to take
      stars.clientHeight;
      stars.style.width = stars.getAttribute("data-width");
    });
  });
});
