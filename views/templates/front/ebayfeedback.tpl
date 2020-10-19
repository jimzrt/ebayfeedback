{*
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
* @author PrestaShop SA <contact@prestashop.com>
    * @copyright 2007-2020 PrestaShop SA
    * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
    * International Registered Trademark & Property of PrestaShop SA
    *}
    <div class="{if $feedback_showBorder}starcard{/if}" style="clear:both;background: {if $feedback_transparent}transparent{else}{$feedback_bgColor|escape:'htmlall':'UTF-8'}{/if};margin: auto;text-align:center;{if $feedback_maxWidth != 1}max-width: {$feedback_maxWidth|escape:'htmlall':'UTF-8'}px;{/if}padding: 10px;">
        <div class="starcontainer">
            <h6 class="feedback_heading">
                {l s='Ebay Feedback' mod='ebayfeedback'}
            </h6>
            <div class="feedback_container">
                {foreach from=$feedback_ratings item=feedback_rating}

                <div class="feedback_row">
                    <div class="feedback_cell">
                        <div class="ratings" style="font-size: {$feedback_starsize|escape:'htmlall':'UTF-8'}pt;">
                            <div class="empty-stars"></div>
                            <div class="border-stars"></div>
                            <div class="full-stars" data-width="{$feedback_rating.rating_percent|escape:'htmlall':'UTF-8'}%"></div>
                        </div>
                    </div>

                    <div class="feedback_cell feeback_counts">
                        {$feedback_rating.count|escape:'htmlall':'UTF-8'}
                    </div>

                    <div class="feedback_cell">
                        {$feedback_rating.detail|escape:'htmlall':'UTF-8'}
                    </div>
                </div>

                {/foreach}
            </div>

            <hr class="style14" />
            <div class="feedback_container">
                <div class="feedback_row">
                    <div class="feedback_cell">
                        <div class="icon icon-plus"></div>
                        {$feedback_positve_count|escape:'htmlall':'UTF-8'}
                    </div>
                    <div class="feedback_cell">
                        <div class="icon icon-disc"></div>
                        {$feedback_neutral_count|escape:'htmlall':'UTF-8'}
                    </div>
                    <div class="feedback_cell">
                        <div class="icon icon-minus"></div>
                        {$feedback_negative_count|escape:'htmlall':'UTF-8'}
                    </div>
                </div>
                <div class="feedback_row">
                    <div class="feedback_cell">
                        {l s='Positive' mod='ebayfeedback'}
                    </div>
                    <div class="feedback_cell">
                        {l s='Neutral' mod='ebayfeedback'}
                    </div>
                    <div class="feedback_cell">
                        {l s='Negative' mod='ebayfeedback'}
                    </div>
                </div>
                <div class="feedback_row" style="padding-top: 15px;">
                    <div class="feedback_cell">
                        {l s='Feedback from the last 12 months' mod='ebayfeedback'}
                    </div>
                </div>
            </div>
            {if $feedback_show_comments && count($feedback_comments|escape:'htmlall':'UTF-8') > 0}
            <hr class="style14" />
            <div class="dot_crsl fb_cmnts col-3 feedback_comments" style="max-width: 230px; height: 100px">
                <div class="itm_ctr" style="width: 230px; height: 72px">
                    <ul class="feedback_slider" style="width: {count($feedback_comments|escape:'htmlall':'UTF-8')*100}%; left: 0%">
                        {foreach from=$feedback_comments item=feedback_comment}
                        <li style="width: 230px; height: 72px">
                            <div class="feedback_row" style="flex-wrap: nowrap;">
                                <div class="" style="margin-right:15px;">
                                    <div class="icon icon-{$feedback_comment.sentiment_class|escape:'htmlall':'UTF-8'}"></div>
                                </div>
                                <div class="">
                                    <div class="fb_dtls">
                                        <div class="fb_cmmt">
                                            {$feedback_comment.text|escape:'htmlall':'UTF-8'}
                                        </div>
                                        <div class="itm_ttl">
                                            <span class="">{$feedback_comment.time|escape:'htmlall':'UTF-8'}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        {/foreach}
                    </ul>
                </div>
                <div class="dots_ctr" style="width: 100%">
                    <div class="dots">
                        <i class="feedback_arrow feedback_left" onclick="select_feedback_prev(this)"></i>{foreach from=$feedback_comments item=feedback_comment name=dots}<span onclick="select_feedback(this,{$smarty.foreach.dots.index|escape:'htmlall':'UTF-8'});" class="feedback_dot{if $smarty.foreach.dots.index == 0} feedback_dot_selected{/if}"></span>{/foreach}<i class="feedback_arrow feedback_right" onclick="select_feedback_next(this)"></i>
                    </div>
                </div>
            </div>
            {/if}
        </div>
    </div>