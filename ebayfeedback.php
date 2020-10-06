<?php

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
 */

if (!defined('_PS_VERSION_')) {
    exit;
}


class Ebayfeedback extends Module implements PrestaShop\PrestaShop\Core\Module\WidgetInterface
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'ebayfeedback';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'TopSoft';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Ebay Feedback');
        $this->description = $this->l('Fetch and display ebay feedback');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        //Configuration::updateValue('EBAYFEEDBACK_ACTIVE', false);

        // set default values
        $this->postProcess(false);

        return parent::install() &&
            $this->registerHook('Header') &&
            $this->registerHook('displayFooter') &&
            $this->registerHook('displayHome') &&
            $this->registerHook('displayTop') &&
            $this->registerHook('displayLeftColumn');
    }

    public function uninstall()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::deleteByName($key);
        }

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        Cache::clean("hook_module_list");
        Media::addJsDef(array('ebayfeedback' => array('feedback_url' => Context::getContext()->link->getModuleLink('ebayfeedback', 'ebayfeedback'))));
        $this->context->controller->addJS($this->_path . '/views/js/back.js');
        $this->context->controller->addCSS($this->_path . '/views/css/back.css');


        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        /**
         * If values have been submitted in the form, process.
         */
        //  if (((bool)Tools::isSubmit('submitEbayfeedbackModule'))) {
        $this->postProcess((bool)Tools::isSubmit('submitEbayfeedbackModule'), $output);
        //  }


        // var_dump("header: " . $this->isRegisteredInHook("Header"));
        // var_dump("footer: " . $this->isRegisteredInHook("displayFooter"));
        // var_dump(Hook::getIdByName("displayHeader", true));
        // var_dump(Hook::getIdByName("Header", true));
        
        return $output . $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitEbayfeedbackModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }


    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        $possible_hooks = $this->getPossibleHooksList();
        $hook_options = array();
        $hook_options[] = array(
            "hook_id" => (int) Hook::getIdByName("Header", true),
            "name" => "Header - IMPORTANT"
        );

        foreach ($possible_hooks as $possible_hook) {
            $hook_name = $possible_hook["name"];
            if (strpos(strtolower($hook_name), "admin") !== false) {
                continue;
            }
            if ($possible_hook["description"] != "") {
                $hook_name .= " - " . $possible_hook["description"];
            }
            $hook_option = array(
                "hook_id" => (int)$possible_hook["id_hook"],
                "name" => $hook_name
            );
            $hook_options[] = $hook_option;
        }
        $validated = $this->getConfigFormValues()["EBAYFEEDBACK_VALIDATED"] === "true";
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'id' => 'EBAYFEEDBACK_VALIDATED',
                        'type' => 'hidden',
                        'name' => 'EBAYFEEDBACK_VALIDATED',
                        'value' => "false"
                    ),
                    array(
                        'id' => 'EBAYFEEDBACK_ACTIVE',
                        'type' => 'switch',
                        'label' => $this->l('Active'),
                        'name' => 'EBAYFEEDBACK_ACTIVE',
                        'is_bool' => true,
                        'disabled' => !$validated,
                        'desc' => $this->l('Activate module - Auth\'n\'Auth key must be validated'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'id' => 'EBAYFEEDBACK_AUTH_KEY',
                        'col' => 3,
                        'type' => 'textbutton',
                        'desc' => $this->l('Enter eBay Auth\'n\'Auth Key'),
                        'name' => 'EBAYFEEDBACK_AUTH_KEY',
                        'label' => $this->l('Auth\'n\'Auth Key'),
                        'button' => array(
                            'label' => 'Validate',
                            'class' => 'EBAYFEEDBACK_validate_button btn-primary' . ($validated ? " hidden" : ""),
                            'attributes' => array(
                                'onclick' => "validate();",
                            )
                        )
                    ),
                    array(
                        'type'    => 'checkbox',                   // This is an <input type="checkbox"> tag.
                        'label'   => $this->l('Hooks'),          // The <label> for this <input> tag.
                        'desc'    => $this->l('Choose hook to display feedback block'),  // A help text, displayed right next to the <input> tag.
                        'name'    => 'EBAYFEEDBACK_TEST',                    // The content of the 'id' attribute of the <input> tag.
                        'values'  => array(
                            'query' => $hook_options,
                            'id'    => 'hook_id',                  // The value of the 'id' key must be the same as the key
                            // for the 'value' attribute of the <option> tag in each $options sub-array.
                            'name'  => 'name'                        // The value of the 'name' key must be the same as the key
                            // for the text content of the <option> tag in each $options sub-array.
                        ),
                        'expand' => array(                      // 1.6-specific: you can hide the checkboxes when there are too many.
                            // A button appears with the number of options it hides.
                            'print_total' => count($hook_options),
                            'default' => 'show',
                            'show' => array('text' => $this->l('show'), 'icon' => 'plus-sign-alt'),
                            'hide' => array('text' => $this->l('hide'), 'icon' => 'minus-sign-alt')
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Star size'),
                        'name' => 'EBAYFEEDBACK_STARSIZE',
                        'options' => array(
                            'query' => array(
                                array(
                                    'id_option' => 18,
                                    'name' => $this->l('Normal')
                                ),
                                array(
                                    'id_option' => 28,
                                    'name' => $this->l('Big')
                                )
                            ),
                            'id' => 'id_option',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Show comments'),
                        'name' => 'EBAYFEEDBACK_COMMENTS',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Transparent Background'),
                        'name' => 'EBAYFEEDBACK_TRANSPARENT',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No')
                            )
                        ),
                    ),
                    array(
                        'type' => 'color',
                        'label' => $this->l('Background Color'),
                        'desc' => $this->l('Transparent Background needs to be turned off'),
                        'name' => 'EBAYFEEDBACK_BGCOLOR'						
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Max Width'),
                        'name' => 'EBAYFEEDBACK_MAXWIDTH',
                        'options' => array(
                            'query' => array(
                                array(
                                    'id_option' => 300,
                                    'name' => $this->l('300px')
                                ),
                                array(
                                    'id_option' => 400,
                                    'name' => $this->l('400px')
                                ),
                                array(
                                    'id_option' => 500,
                                    'name' => $this->l('500px')
                                ),
                                array(
                                    'id_option' => 600,
                                    'name' => $this->l('600px')
                                ),
                                array(
                                    'id_option' => 1,
                                    'name' => $this->l('Fullwidth')
                                )
                            ),
                            'id' => 'id_option',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Show border'),
                        'name' => 'EBAYFEEDBACK_BORDER',
                        'is_bool' => true,
                        'desc' => $this->l('Display border around feedback block'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Cache response'),
                        'name' => 'EBAYFEEDBACK_CACHE',
                        'is_bool' => true,
                        'desc' => $this->l('Cache eBay response (24h)'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No')
                            )
                        ),
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {


        $form_values = array(
            'EBAYFEEDBACK_ACTIVE' => Configuration::get('EBAYFEEDBACK_ACTIVE', null, null, null, false),
            'EBAYFEEDBACK_BORDER' => Configuration::get('EBAYFEEDBACK_BORDER', null, null, null, false),
            'EBAYFEEDBACK_AUTH_KEY' => Configuration::get('EBAYFEEDBACK_AUTH_KEY', null, null, null, 'AgAAAA**AQAAAA**aAAAAA**8GJzXw**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6wAlYukDJWDpAidj6x9nY+seQ**19YCAA**AAMAAA**M6UYDUww3zomvlEK22Ikqdc5zZLJ7jjiQ+6GduJg3G5/IVdLHk/gxPOnoj3vJz1Iz0vyio2r9tTZARRpM3zi32EJ1W8dNomV7UvpDcGnTDeNq1X7gsBA2iSUf6l0zGG3pDFJeSIveDupYa+VfUa0smRCN2Wqkoh/y3lpr7lGbE6qzfHczrFTP9FV440V3ZTd32rl+QVMqsNAHNDKHhWnP6v3CjRLMIU8z4g0eXLvwX4rslY2y43WSbO2P/tyDpTN3ZFu/ryTalvK3ZTI+OsIM/ZrLfHkzOmDF6dBhGBkNWvs+K9tu9yIcsMirohCYZ0U8rne8ji9FcOVsutM/kIkNSTolshN1rnXsyfqt5giuVO4Bx3dcVBBO9o1eCSKumn2PhL4ene1aAAQfrC6dZnb6NZut/CZ5DPj/VnTtxtIWFG0x0fp2HLwXK8bC9pQY9xU6T7p3x5bwmXzvW+ewJK7XPk1MswFZdqEYdtKWkucykr0SbbxJc4Jo7y2dCBQr2fcr2A1lW6Yp8943+v4GDrFN3jUBEqO1jNbmdpfJP034UraQ9WWyfrAaUTqQBOGeQXSDtlE+pz3nGmxV6v4+KDgtYYZzrdGp40hGy8dyqiORYngSlauskeORwgNbbRKeqLRHvQNo+3u68mUK0It/leBch8K1992O0g84LOS0jOiAvZwwQPlha2wGbDMLZyQOBVDZa5D89b8LxNVO3iKVHu63yp0Eu9aM8qtU5MJGWUKzGzn9alK7O601xIEiFqEag+0'),
            'EBAYFEEDBACK_CACHE' => Configuration::get('EBAYFEEDBACK_CACHE', null, null, null, true),
            'EBAYFEEDBACK_STARSIZE' => Configuration::get("EBAYFEEDBACK_STARSIZE", null, null, null, 18),
            'EBAYFEEDBACK_TRANSPARENT' => Configuration::get("EBAYFEEDBACK_TRANSPARENT", null, null, null, true),
            'EBAYFEEDBACK_BGCOLOR' => Configuration::get("EBAYFEEDBACK_BGCOLOR", null, null, null, "#ffffff"),
            'EBAYFEEDBACK_MAXWIDTH' => Configuration::get("EBAYFEEDBACK_MAXWIDTH", null, null, null, 500),
            'EBAYFEEDBACK_COMMENTS' => Configuration::get("EBAYFEEDBACK_COMMENTS", null, null, null, true),
            'EBAYFEEDBACK_VALIDATED' => Configuration::get("EBAYFEEDBACK_VALIDATED", null, null, null, "false")
        );
        $possible_hooks = $this->getPossibleHooksList();
        foreach ($possible_hooks as $possible_hook) {
            if (strpos(strtolower($possible_hook["name"]), "admin") !== false) {
                continue;
            }
            $form_values['EBAYFEEDBACK_TEST_' . $possible_hook["id_hook"]] = $possible_hook["registered"] ? "on" : null;
        }
        $form_values['EBAYFEEDBACK_TEST_' . ((int)Hook::getIdByName("Header", true))] = $this->isRegisteredInHook("Header") ? "on" : null;
        return $form_values;
    }

    private function stringStartsWith($string, $startString)
    {
        $len = strlen($startString);
        return (substr($string, 0, $len) === $startString);
    }

    /**
     * Save form data.
     */
    protected function postProcess($submit, &$output = NULL)
    {

        $error = false;
        $change_hook = false;
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            if ($this->stringStartsWith($key, "EBAYFEEDBACK_TEST")) {
                if ($submit) {
                    if ($form_values[$key] == null && Tools::getIsset($key)) {
                        //register
                        $tmp = explode('_', $key);
                        $hook_id = (int)end($tmp);
                        $hook_name = Hook::getNameById($hook_id);
                        $this->registerHook($hook_name);
                        $change_hook = true;
                    } else if ($form_values[$key] == "on" && !Tools::getIsset($key)) {
                        //unregister
                        $tmp = explode('_', $key);
                        $hook_id = (int)end($tmp);
                        $this->unregisterHook($hook_id);
                        $change_hook = true;
                    }
                }
                continue;
            }
            // if (!Tools::getIsset($key)) {
            //     $output .= $this->displayError($this->l('Invalid Configuration value: ' . $key));
            //     $error = true;
            //     continue;
            // }

            // if ($key === 'EBAYFEEDBACK_ACCOUNT_EMAIL' && !ValidateCore::isEmail(Tools::getValue($key))) {
            //     $output .= $this->displayError($this->l(Tools::getValue($key) . ' is not a valid email address'));
            //     $error = true;
            //     continue;
            // }
            if (!$submit) {
                Configuration::updateValue($key, $form_values[$key]);
            } else if (Tools::getIsset($key)) {
                Configuration::updateValue($key, Tools::getValue($key));
            }
        }
        if ($change_hook) {
            Cache::clean("hook_module_list");
        }
        if (!$error && $submit) {
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    // public function hookBackOfficeHeader()
    // {
    //     if (Tools::getValue('module_name') == $this->name) {
    //         $this->context->controller->addJS($this->_path.'views/js/back.js');
    //         $this->context->controller->addCSS($this->_path.'views/css/back.css');
    //     }
    // }

    // /**
    //  * Add the CSS & JavaScript files you want to be added on the FO.
    //  */
     public function hookHeader()
     {
    //     if (!Configuration::get('EBAYFEEDBACK_ACTIVE')) {
    //         return;
    //     }

         Media::addJsDef(array('ebayfeedback' => array('feedback_url' => Context::getContext()->link->getModuleLink('ebayfeedback', 'ebayfeedback'))));
         $this->context->controller->addJS($this->_path . '/views/js/front.js');
         $this->context->controller->addCSS($this->_path . '/views/css/front.css');
     }

    // public function hookDisplayFooter()
    // {
    //     return "Hallo";
    // }

    // public function hookDisplayHome()
    // {
    //     return "Hallo";
    // }

    // public function hookDisplayTop()
    // {
    //     return "Hallo";
    // }

    public function renderWidget($hookName, array $configuration)
    {

        //var_dump(__FILE__);
        // if (!Configuration::get('EBAYFEEDBACK_ACTIVE')) {
        //     return;
        // }

        //Media::addJsDef(array('ebayfeedback' => array('feedback_url' => Context::getContext()->link->getModuleLink('ebayfeedback', 'ebayfeedback'))));
        //$this->context->controller->addJS($this->_path . '/views/js/front.js');
        //$this->context->controller->addCSS($this->_path . '/views/css/front.css');
        
        // $this->context->controller->registerJavascript(
        //     $this->name,
        //     '/views/js/front.js',
        //     [
        //       'position' => 'head',
        //       'inline' => true,
        //       'priority' => 10,
        //     ]
        // );


        $this->context->smarty->assign([

            'feedback_url' => Context::getContext()->link->getModuleLink('ebayfeedback', 'ebayfeedback')
        ]);



        return $this->display(__FILE__, 'dummy.tpl');

        //return $result . " - " . $feedbackCount;
    }

    public function getWidgetVariables($hookName, array $configuration)
    {
        return false;
    }
}
