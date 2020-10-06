<?php

class EbayfeedbackEbayfeedbackModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        // if (Tools::getIsset('authKey')) {
        //     die(Tools::jsonEncode(Tools::getValue('authKey')));
        // }

        if (!Configuration::get('EBAYFEEDBACK_ACTIVE') && !Tools::getIsset('authKey')) {
            die(Tools::jsonEncode("ebay feedback module is not active!"));
        }

        $response_file = _PS_MODULE_DIR_ . $this->module->name . '/response.xml';

        if (file_exists($response_file) && Configuration::get('EBAYFEEDBACK_CACHE') && !Tools::getIsset('authKey') && time() - Configuration::get('EBAYFEEDBACK_LASTCACHE') <= 24*60*60) {
            $response = file_get_contents($response_file);

        } else {

            if (Tools::getIsset('authKey')) {
                $authKey = Tools::getValue('authKey');
            } else {
                $authKey = Configuration::get('EBAYFEEDBACK_AUTH_KEY');
            }

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.ebay.com/ws/api.dll",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<GetFeedbackRequest xmlns=\"urn:ebay:apis:eBLBaseComponents\"><RequesterCredentials><eBayAuthToken>" . $authKey . "</eBayAuthToken></RequesterCredentials><ErrorLanguage>en_US</ErrorLanguage><WarningLevel>High</WarningLevel><FeedbackType>FeedbackReceivedAsSeller</FeedbackType><DetailLevel>ReturnAll</DetailLevel><OutputSelector>FeedbackDetailArray</OutputSelector><OutputSelector>NegativeFeedbackPeriodArray</OutputSelector><OutputSelector>NeutralFeedbackPeriodArray</OutputSelector><OutputSelector>PositiveFeedbackPeriodArray</OutputSelector><OutputSelector>SellerRatingSummaryArray</OutputSelector></GetFeedbackRequest>",
                CURLOPT_HTTPHEADER => array(
                    "X-EBAY-API-CALL-NAME: GetFeedback",
                    "X-EBAY-API-SITEID: 0",
                    "X-EBAY-API-COMPATIBILITY-LEVEL: 967",
                    "Content-Type: application/xml"
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            file_put_contents($response_file, $response);
            //$feedback->asXml('response.xml');

            Configuration::updateValue('EBAYFEEDBACK_LASTCACHE', time());


        }

        $feedback = new SimpleXMLElement($response);

        $feedback_status = (string)$feedback->Ack;
        if ($feedback_status != "Success") {
            http_response_code(400);
            unlink($response_file);
            die(Tools::jsonEncode($feedback));
        }
        if (Tools::getIsset('authKey')) {
            die(Tools::jsonEncode("success"));
        }



        $feedback_positve_count = (int)$feedback->FeedbackSummary->PositiveFeedbackPeriodArray->FeedbackPeriod[3]->Count;
        $feedback_negative_count = (int)$feedback->FeedbackSummary->NegativeFeedbackPeriodArray->FeedbackPeriod[3]->Count;
        $feedback_neutral_count = (int)$feedback->FeedbackSummary->NeutralFeedbackPeriodArray->FeedbackPeriod[3]->Count;

        $feedback_rating_obj = $feedback->FeedbackSummary->SellerRatingSummaryArray->AverageRatingSummary[0];
        $feedback_ratings = array();
        for ($i = 0; $i < 4; $i++) {
            $feedback_rating = (float)$feedback_rating_obj->AverageRatingDetails[$i]->Rating;
            $feedback_rating_percent = round(($feedback_rating / 5) * 100);
            //$feedback_rating_full = (int)$feedback_rating;
            //$feedback_rating_empty = 5 - $feedback_rating_full - (int)($feedback_rating_full != $feedback_rating);
            //$feedback_rating_half = round(($feedback_rating - $feedback_rating_full)*10);

            $feedback_ratings[] = array("rating" => array("rating" => $feedback_rating, "feedback_rating_percent" => $feedback_rating_percent), "count" => (int)$feedback_rating_obj->AverageRatingDetails[$i]->RatingCount, "detail" => "ItemAsDescribed"); //(string)$feedback_rating_obj->AverageRatingDetails[$i]->RatingDetail);
        }
        $feedback_comments = array();
        //  count($feedback->FeedbackDetailArray->FeedbackDetail)
        for ($i = 0; $i < 10; $i++) {
            $feedback_comments[] = array("type" => (string)$feedback->FeedbackDetailArray->FeedbackDetail[$i]->CommentType, "time" => Tools::formatDateStr((string)$feedback->FeedbackDetailArray->FeedbackDetail[$i]->CommentTime, true), "text" => (string)$feedback->FeedbackDetailArray->FeedbackDetail[$i]->CommentText);
        }

        $this->context->smarty->assign([
            'feedback_status' => $feedback_status,
            'feedback_positve_count' => $feedback_positve_count,
            'feedback_negative_count' => $feedback_negative_count,
            'feedback_neutral_count' => $feedback_neutral_count,
            'feedback_ratings' => $feedback_ratings,
            'feedback_comments' => $feedback_comments,
            'feedback_showBorder' => Configuration::get('EBAYFEEDBACK_BORDER'),
            'feedback_starsize' => Configuration::get('EBAYFEEDBACK_STARSIZE'),
            'feedback_transparent' => Configuration::get('EBAYFEEDBACK_TRANSPARENT'),
            'feedback_bgColor' => Configuration::get('EBAYFEEDBACK_BGCOLOR'),
            'feedback_maxWidth' => Configuration::get('EBAYFEEDBACK_MAXWIDTH'),
            'feedback_show_comments' => Configuration::get('EBAYFEEDBACK_COMMENTS'),
            'feedback_lastCache' => Configuration::get('EBAYFEEDBACK_LASTCACHE')

        ]);

        //http_response_code(400);
        $this->setTemplate('module:ebayfeedback/views/templates/front/ebayfeedback.tpl');
    }
}
