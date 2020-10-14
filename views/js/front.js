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
    //indexMap.set(elem.parentNode, index);
    elem.parentNode.parentNode.previousElementSibling.querySelector(".feedback_slider").style.left = index * -100 + "%";
    elem.parentNode.querySelector(".feedback_dot_selected").classList.remove("feedback_dot_selected");

    elem.classList.add("feedback_dot_selected");
}

function get_feedback_index(elem) {
    return (
        Math.abs(
            elem.parentNode.parentNode.previousElementSibling.querySelector(".feedback_slider").style.left.slice(0, -1)
        ) / 100
    );
}

function select_feedback_next(elem) {
    currIndex = get_feedback_index(elem);
    newIndex = currIndex + 1;
    if (newIndex == elem.parentNode.querySelectorAll(".feedback_dot").length) {
        newIndex = 0;
    }
    select_feedback($(elem).parent().children()[newIndex + 1], newIndex);
}

function select_feedback_prev(elem) {
    currIndex = get_feedback_index(elem);
    newIndex = currIndex - 1;
    if (newIndex < 0) {
        newIndex = elem.parentNode.querySelectorAll(".feedback_dot").length - 1;
    }
    select_feedback($(elem).parent().children()[newIndex + 1], newIndex);
}

$(function () {
    $(".feedback_result").load(ebayfeedback.feedback_url, function (response, status, xhr) {
        if (status == "error") {
            // Todo: debug error message
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
