<?php

class EbayfeedbackEbayfeedbackModuleFrontController extends ModuleFrontController
{

    // Tools::formatDateStr from prestashop develop branch
    public function formatDateStr($date_str, $full = false)
    {
        $time = strtotime($date_str);
        $context = Context::getContext();
        $date_format = ($full ? $context->language->date_format_full : $context->language->date_format_lite);
        $date = date($date_format, $time);

        return $date;
    }


    public function initContent()
    {
        parent::initContent();

        // if (Tools::getIsset('authKey')) {
        //     die(Tools::jsonEncode(Tools::getValue('authKey')));
        // }

        if (!Configuration::get('EBAYFEEDBACK_ACTIVE') && !Tools::getIsset('authKey')) {
            die(Tools::jsonEncode("ebay feedback module is not active!"));
        }

        if (Tools::getIsset('userName')) {
            $userName = Tools::getValue('userName');
        } else {
            $userName = Configuration::get('EBAYFEEDBACK_USERNAME');
        }

        $response_file = _PS_MODULE_DIR_ . $this->module->name . '/response_' . $userName . '.json';

        if (file_exists($response_file) && Configuration::get('EBAYFEEDBACK_CACHE') && !Tools::getIsset('authKey') && time() - Configuration::get('EBAYFEEDBACK_LASTCACHE') <= 24 * 60 * 60) {
            $response = file_get_contents($response_file);
        } else {



            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://ebay.jamestop.duckdns.org/EbayFeedback/?userName=" . $userName,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "presta: 1"
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            file_put_contents($response_file, $response);
            //$feedback->asXml('response.xml');

            Configuration::updateValue('EBAYFEEDBACK_LASTCACHE', time());
        }


        $feedback = json_decode($response, true);

        // {
        //     "comments": [
        //         {
        //             "date": "Oct 12, 2020",
        //             "text": "Rasche Antwort und prompte Bezahlung. Perfekt! Filamentwerk_de sagt Danke!"
        //         },
        //         {
        //             "date": "Oct 12, 2020",
        //             "text": "Although described as lightweight the item is very heavy but looks good."
        //         },
        //         {
        //             "date": "Oct 05, 2020",
        //             "text": "all like description, very good comunication, delivery andexcellent seller"
        //         },
        //         {
        //             "date": "Oct 05, 2020",
        //             "text": "PERFECT SAGA MINK COAT! absolutely love it! AMAZING QUALITY! Soft fur, thank you"
        //         },
        //         {
        //             "date": "Oct 05, 2020",
        //             "text": "Simply Gorgeous!!! Super Fast Shipping!"
        //         }
        //     ],

        // }

        $feedback_status = $feedback["result"];
        if ($feedback_status != "ok") {
            http_response_code(400);
            unlink($response_file);
            die(Tools::jsonEncode($feedback));
        }
        if (Tools::getIsset('userName')) {
            die(Tools::jsonEncode("success"));
        }



        $feedback_positve_count = $feedback["sentiments"]["positive"];
        $feedback_negative_count = $feedback["sentiments"]["negative"];
        $feedback_neutral_count = $feedback["sentiments"]["neutral"];

        //$feedback_rating_obj = $feedback->FeedbackSummary->SellerRatingSummaryArray->AverageRatingSummary[0];
        $feedback_ratings = array();
        for ($i = 0; $i < 4; $i++) {
            $feedback_rating_percent = $feedback["ratings"][$i]["rating"];
            //$feedback_rating_full = (int)$feedback_rating;
            //$feedback_rating_empty = 5 - $feedback_rating_full - (int)($feedback_rating_full != $feedback_rating);
            //$feedback_rating_half = round(($feedback_rating - $feedback_rating_full)*10);

            $feedback_ratings[] = array("rating" => array("feedback_rating_percent" => $feedback_rating_percent), "count" => $feedback["ratings"][$i]["count"], "detail" => $feedback["ratings"][$i]["type"]); //(string)$feedback_rating_obj->AverageRatingDetails[$i]->RatingDetail);
        }
        $feedback_comments = array();
        //  count($feedback->FeedbackDetailArray->FeedbackDetail)
        for ($i = 0; $i < 5; $i++) {
            $feedback_comments[] = array("time" => $this->formatDateStr($feedback["comments"][$i]["date"], false), "text" => $feedback["comments"][$i]["text"]);
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
            // 'feedback_lastCache' => Configuration::get('EBAYFEEDBACK_LASTCACHE')

        ]);

        //http_response_code(400);
        if (Tools::version_compare(_PS_VERSION_, '1.7', '>=')) {

            $this->setTemplate('module:ebayfeedback/views/templates/front/ebayfeedback.tpl');
        } else {

            header('Content-Type: text/html');
            die($this->context->smarty->fetch(_PS_MODULE_DIR_ . 'ebayfeedback/views/templates/front/ebayfeedback.tpl'));
        }
    }
}
