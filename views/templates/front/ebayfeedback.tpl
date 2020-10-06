<div class="{if $feedback_showBorder}starcard{/if}" style="clear:both">
    <div class="starcontainer" style="background: {if $feedback_transparent}transparent{else}{$feedback_bgColor}{/if};margin: auto;text-align:center;{if $feedback_maxWidth != 1}max-width: {$feedback_maxWidth}px;{/if}padding: 10px;">
        <h6 style="
margin:10px 0px 10px 0px;
font-size: 18px;
color: #414141;
font-weight: normal;
letter-spacing: 0.1em;
">
            EBAY BEWERTUNGEN {$feedback_lastCache}
        </h6>
        <div class="feedback_container">
            {foreach from=$feedback_ratings item=feedback_rating}

            <div class="feedback_row">
                <div class="feedback_cell">
                    <div class="ratings" style="font-size: {$feedback_starsize}pt;">
                        <div class="empty-stars"></div>
                        <div class="border-stars"></div>
                        <div class="full-stars" data-width="{$feedback_rating.rating.feedback_rating_percent}%"></div>
                    </div>
                </div>

                <div class="feedback_cell" style="width: 50px;">
                    {$feedback_rating.count}
                </div>

                <div class="feedback_cell" style="width: 200px;">
                    {$feedback_rating.detail}
                </div>
            </div>

            {/foreach}
        </div>

        <hr class="style14" />
        <div class="feedback_container">
            <div class="feedback_row">
                <div class="feedback_cell">
                    <div class="icon icon-plus"></div>
                    {$feedback_positve_count}
                </div>
                <div class="feedback_cell">
                    <div class="icon icon-disc"></div>
                    {$feedback_neutral_count}
                </div>
                <div class="feedback_cell">
                    <div class="icon icon-minus"></div>
                    {$feedback_negative_count}
                </div>
            </div>
            <div class="feedback_row">
                <div class="feedback_cell">
                    Positiv
                </div>
                <div class="feedback_cell">
                    Neutral
                </div>
                <div class="feedback_cell">
                    Negativ
                </div>
            </div>
            <div class="feedback_row" style="padding-top: 15px;">
                <div class="feedback_cell">
                    Bewertungen der letzten 12 Monate
                </div>
            </div>
        </div>
        {if $feedback_show_comments}
        <hr class="style14" />
        <div class="dot_crsl fb_cmnts col-3 feedback_comments" style="max-width: 250px; height: 100px">
            <div class="itm_ctr" style="width: 250px; height: 72px">
                <ul class="feedback_slider" style="width: {count($feedback_comments)*100}%; left: 0%">
                    {foreach from=$feedback_comments item=feedback_comment}
                    <li style="width: 250px; height: 72px">
                        <div class="feedback_row" style="flex-wrap: nowrap;">
                            <div class="" style="margin-right:15px;">
                                <div class="icon icon-plus"></div>
                            </div>
                            <div class="">
                                <div class="fb_dtls">
                                    <div class="fb_cmmt">
                                        {$feedback_comment.text}
                                    </div>
                                    <div class="itm_ttl">
                                        <span class="">{$feedback_comment.time}</span>
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
                    {foreach from=$feedback_comments item=feedback_comment name=dots}<span onclick="select_feedback(this,{$smarty.foreach.dots.index});" class="feedback_dot{if $smarty.foreach.dots.index == 0} feedback_dot_selected{/if}"></span>{/foreach}
                </div>
            </div>
        </div>
        {/if}
    </div>
</div>