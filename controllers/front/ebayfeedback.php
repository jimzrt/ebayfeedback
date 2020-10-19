<?php
/**
 * 2007-2020 PrestaShop.
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
 */

class EbayfeedbackEbayfeedbackModuleFrontController extends ModuleFrontController
{
    // Tools::formatDateStr from prestashop develop branch
    public function formatDateStr($date_str, $full = false)
    {
        $time = strtotime($date_str);
        $context = Context::getContext();
        $date_format = $full ? $context->language->date_format_full : $context->language->date_format_lite;
        $date = date($date_format, $time);

        return $date;
    }

    public function initContent()
    {
        parent::initContent();

        if (!Configuration::get('EBAYFEEDBACK_ACTIVE') && !Tools::getIsset('userName')) {
            //Todo: only on debug
            die(Tools::jsonEncode($this->module->l('ebay feedback module is not active!')));
        }

        if (Tools::getIsset('userName')) {
            $userName = Tools::getValue('userName');
        } else {
            $userName = Configuration::get('EBAYFEEDBACK_USERNAME');
        }

        $response_file = _PS_MODULE_DIR_ . $this->module->name . '/response_' . $userName . '.json';

        if (
            file_exists($response_file) &&
            Configuration::get('EBAYFEEDBACK_CACHE') &&
            !Tools::getIsset('userName') &&
            time() - Configuration::get('EBAYFEEDBACK_LASTCACHE') <= 24 * 60 * 60
        ) {
            $response = Tools::file_get_contents($response_file);
        } else {
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://ebay.jamestop.duckdns.org/EbayFeedback/?userName=' . $userName,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => ['presta: 1'],
            ]);

            $response = curl_exec($curl);

            curl_close($curl);

            file_put_contents($response_file, $response);

            Configuration::updateValue('EBAYFEEDBACK_LASTCACHE', time());
        }

        $feedback = json_decode($response, true);

        $feedback_status = $feedback['result'];
        if ($feedback_status != 'ok') {
            http_response_code(400);
            unlink($response_file);
            die(Tools::jsonEncode($feedback));
        }
        if (Tools::getIsset('userName')) {
            die(Tools::jsonEncode('success'));
        }

        $feedback_positve_count = $feedback['sentiments']['positive'];
        $feedback_negative_count = $feedback['sentiments']['negative'];
        $feedback_neutral_count = $feedback['sentiments']['neutral'];

        $feedback_ratings = [
            [
                'rating_percent' => $feedback['ratings'][0]['rating'],
                'count' => $feedback['ratings'][0]['count'],
                'detail' => $this->module->l("Item as described"),
            ],
            [
                'rating_percent' => $feedback['ratings'][1]['rating'],
                'count' => $feedback['ratings'][1]['count'],
                'detail' => $this->module->l("Communication"),
            ],
            [
                'rating_percent' => $feedback['ratings'][2]['rating'],
                'count' => $feedback['ratings'][2]['count'],
                'detail' => $this->module->l("Shipping time"),
            ],
            [
                'rating_percent' => $feedback['ratings'][3]['rating'],
                'count' => $feedback['ratings'][3]['count'],
                'detail' => $this->module->l("Shipping charges"),
            ],
        ];

        $feedback_comments = [];
        for ($i = 0; $i < count($feedback['comments']); ++$i) {
            $sentiment = $feedback['comments'][$i]['sentiment'];
            if ($sentiment == "positive") {
                $sentiment = "plus";
            } elseif ($sentiment == "neutral") {
                $sentiment = "disc";
            } else {
                $sentiment = "minus";
            }
            $feedback_comments[] = [
                'time' => $this->formatDateStr($feedback['comments'][$i]['date'], false),
                'text' => $feedback['comments'][$i]['text'],
                'sentiment_class' => $sentiment,
            ];
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
            'feedback_currentHook' => Tools::getValue('currentHook'),
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
