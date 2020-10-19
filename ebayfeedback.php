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

if (!defined('_PS_VERSION_')) {
    exit();
}

class Ebayfeedback extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'ebayfeedback';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'TopSoft';
        $this->need_instance = 0;

        /*
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Ebay Feedback');
        $this->description = $this->l('Fetch and display ebay feedback');

        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => _PS_VERSION_];
    }

    public function __call($func, $params)
    {
        $params;
        if ($this->stringStartsWith(Tools::strtolower($func), 'hookdisplay')) {
            return $this->renderWidget($func);
        }
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update.
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
            $this->registerHook('displayHeader') &&
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
     * Load the configuration form.
     */
    public function getContent()
    {
        Cache::clean('hook_module_list');
        Media::addJsDef([
            'ebayfeedback' => [
                'feedback_url' => Context::getContext()->link->getModuleLink('ebayfeedback', 'ebayfeedback'),
            ],
        ]);
        $this->context->controller->addJS($this->_path . '/views/js/back.js');
        $this->context->controller->addCSS($this->_path . '/views/css/back.css');

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        /*
         * If values have been submitted in the form, process.
         */
        //  if (((bool)Tools::isSubmit('submitEbayfeedbackModule'))) {
        $this->postProcess((bool) Tools::isSubmit('submitEbayfeedbackModule'), $output);
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
        $helper->currentIndex =
            $this->context->link->getAdminLink('AdminModules', false) .
            '&configure=' .
            $this->name .
            '&tab_module=' .
            $this->tab .
            '&module_name=' .
            $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues() /* Add values for your inputs */,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        $possible_hooks = $this->getPossibleHooksList();
        $hook_options = [];
        $hook_options[] = [
            'hook_id' => (int) Hook::getIdByName('Header', true),
            'name' => 'Header - IMPORTANT',
        ];

        foreach ($possible_hooks as $possible_hook) {
            $hook_name = $possible_hook['name'];
            if (!$this->stringStartsWith($hook_name, 'display')) {
                continue;
            }
            if (strpos(Tools::strtolower($hook_name), 'admin') !== false) {
                continue;
            }
            if (strpos(Tools::strtolower($hook_name), 'backoffice') !== false) {
                continue;
            }
            if (array_key_exists('description', $possible_hook) && $possible_hook['description'] != '') {
                $hook_name .= ' - ' . $possible_hook['description'];
            }
            $hook_option = [
                'hook_id' => (int) $possible_hook['id_hook'],
                'name' => $hook_name,
            ];
            $hook_options[] = $hook_option;
        }
        $validated = $this->getConfigFormValues()['EBAYFEEDBACK_VALIDATED'] === 'true';

        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'id' => 'EBAYFEEDBACK_VALIDATED',
                        'type' => 'hidden',
                        'name' => 'EBAYFEEDBACK_VALIDATED',
                        'value' => 'false',
                    ],
                    [
                        'id' => 'EBAYFEEDBACK_ACTIVE',
                        'type' => 'switch',
                        'label' => $this->l('Active'),
                        'name' => 'EBAYFEEDBACK_ACTIVE',
                        'is_bool' => true,
                        'disabled' => !$validated,
                        'desc' => $this->l('Activate module - username must be validated'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                    [
                        'id' => 'EBAYFEEDBACK_USERNAME',
                        'col' => 3,
                        'type' => 'textbutton',
                        'desc' => $this->l('Enter ebay username'),
                        'name' => 'EBAYFEEDBACK_USERNAME',
                        'label' => $this->l('Username'),
                        'button' => [
                            'label' => 'Validate',
                            'class' => 'EBAYFEEDBACK_validate_button btn-primary' . ($validated ? ' hidden' : ''),
                            'attributes' => [
                                'onclick' => 'validate();',
                            ],
                        ],
                    ],
                    [
                        'type' => 'checkbox', // This is an <input type="checkbox"> tag.
                        'label' => $this->l('Hooks'), // The <label> for this <input> tag.
                        'desc' => $this->l('Choose hook to display feedback block'),
                        'name' => 'EBAYFEEDBACK_TEST', // The content of the 'id' attribute of the <input> tag.
                        'values' => [
                            'query' => $hook_options,
                            'id' => 'hook_id', // The value of the 'id' key must be the same as the key
                            // for the 'value' attribute of the <option> tag in each $options sub-array.
                            'name' => 'name', // The value of the 'name' key must be the same as the key
                            // for the text content of the <option> tag in each $options sub-array.
                        ],
                        'expand' => [
                            // 1.6-specific: you can hide the checkboxes when there are too many.
                            // A button appears with the number of options it hides.
                            'print_total' => count($hook_options),
                            'default' => 'show',
                            'show' => ['text' => $this->l('show'), 'icon' => 'plus-sign-alt'],
                            'hide' => ['text' => $this->l('hide'), 'icon' => 'minus-sign-alt'],
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Star size'),
                        'name' => 'EBAYFEEDBACK_STARSIZE',
                        'options' => [
                            'query' => [
                                [
                                    'id_option' => 18,
                                    'name' => $this->l('Normal'),
                                ],
                                [
                                    'id_option' => 28,
                                    'name' => $this->l('Big'),
                                ],
                            ],
                            'id' => 'id_option',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Show comments'),
                        'name' => 'EBAYFEEDBACK_COMMENTS',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Transparent Background'),
                        'name' => 'EBAYFEEDBACK_TRANSPARENT',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->l('Background Color'),
                        'desc' => $this->l('Transparent Background needs to be turned off'),
                        'name' => 'EBAYFEEDBACK_BGCOLOR',
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Max Width'),
                        'name' => 'EBAYFEEDBACK_MAXWIDTH',
                        'options' => [
                            'query' => [
                                [
                                    'id_option' => 300,
                                    'name' => '300px',
                                ],
                                [
                                    'id_option' => 400,
                                    'name' => '400px',
                                ],
                                [
                                    'id_option' => 500,
                                    'name' => '500px',
                                ],
                                [
                                    'id_option' => 600,
                                    'name' => '600px',
                                ],
                                [
                                    'id_option' => 1,
                                    'name' => $this->l('full width'),
                                ],
                            ],
                            'id' => 'id_option',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Show border'),
                        'name' => 'EBAYFEEDBACK_BORDER',
                        'is_bool' => true,
                        'desc' => $this->l('Display border around feedback block'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Cache response'),
                        'name' => 'EBAYFEEDBACK_CACHE',
                        'is_bool' => true,
                        'desc' => $this->l('Cache eBay response (24h)'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        $form_values = [
            'EBAYFEEDBACK_ACTIVE' => Configuration::hasKey('EBAYFEEDBACK_ACTIVE')
                ? Configuration::get('EBAYFEEDBACK_ACTIVE')
                : false,
            'EBAYFEEDBACK_BORDER' => Configuration::hasKey('EBAYFEEDBACK_BORDER')
                ? Configuration::get('EBAYFEEDBACK_BORDER')
                : false,
            'EBAYFEEDBACK_USERNAME' => Configuration::hasKey('EBAYFEEDBACK_USERNAME')
                ? Configuration::get('EBAYFEEDBACK_USERNAME')
                : 'furs-and-more-germany',
            'EBAYFEEDBACK_CACHE' => Configuration::hasKey('EBAYFEEDBACK_CACHE')
                ? Configuration::get('EBAYFEEDBACK_CACHE')
                : true,
            'EBAYFEEDBACK_STARSIZE' => Configuration::hasKey('EBAYFEEDBACK_STARSIZE')
                ? Configuration::get('EBAYFEEDBACK_STARSIZE')
                : 18,
            'EBAYFEEDBACK_TRANSPARENT' => Configuration::hasKey('EBAYFEEDBACK_TRANSPARENT')
                ? Configuration::get('EBAYFEEDBACK_TRANSPARENT')
                : true,
            'EBAYFEEDBACK_BGCOLOR' => Configuration::hasKey('EBAYFEEDBACK_BGCOLOR')
                ? Configuration::get('EBAYFEEDBACK_BGCOLOR')
                : '#ffffff',
            'EBAYFEEDBACK_MAXWIDTH' => Configuration::hasKey('EBAYFEEDBACK_MAXWIDTH')
                ? Configuration::get('EBAYFEEDBACK_MAXWIDTH')
                : 500,
            'EBAYFEEDBACK_COMMENTS' => Configuration::hasKey('EBAYFEEDBACK_COMMENTS')
                ? Configuration::get('EBAYFEEDBACK_COMMENTS')
                : true,
            'EBAYFEEDBACK_VALIDATED' => Configuration::hasKey('EBAYFEEDBACK_VALIDATED')
                ? Configuration::get('EBAYFEEDBACK_VALIDATED')
                : 'false',
        ];
        $possible_hooks = $this->getPossibleHooksList();
        foreach ($possible_hooks as $possible_hook) {
            $hook_name = $possible_hook['name'];
            if (!$this->stringStartsWith($hook_name, 'display')) {
                continue;
            }
            if (strpos(Tools::strtolower($hook_name), 'admin') !== false) {
                continue;
            }
            if (strpos(Tools::strtolower($hook_name), 'backoffice') !== false) {
                continue;
            }
            if (array_key_exists('registered', $possible_hook)) {
                $registered = $possible_hook['registered'];
            } else {
                $registered = $this->isRegisteredInHook($possible_hook['name']);
            }
            $form_values['EBAYFEEDBACK_TEST_' . $possible_hook['id_hook']] = $registered ? 'on' : null;
        }
        $form_values['EBAYFEEDBACK_TEST_' . ((int) Hook::getIdByName('Header', true))] = $this->isRegisteredInHook(
            'Header'
        )
            ? 'on'
            : null;

        return $form_values;
    }

    private function stringStartsWith($string, $startString)
    {
        $len = Tools::strlen($startString);

        return Tools::substr($string, 0, $len) === $startString;
    }

    /**
     * Save form data.
     */
    protected function postProcess($submit, &$output = null)
    {
        $error = false;
        $change_hook = false;
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            if ($this->stringStartsWith($key, 'EBAYFEEDBACK_TEST')) {
                if ($submit) {
                    if ($form_values[$key] == null && Tools::getIsset($key)) {
                        //register
                        $tmp = explode('_', $key);
                        $hook_id = (int) end($tmp);
                        $hook_name = Hook::getNameById($hook_id);
                        $this->registerHook($hook_name);
                        $change_hook = true;
                    } elseif ($form_values[$key] == 'on' && !Tools::getIsset($key)) {
                        //unregister
                        $tmp = explode('_', $key);
                        $hook_id = (int) end($tmp);
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
            } elseif (Tools::getIsset($key)) {
                Configuration::updateValue($key, Tools::getValue($key));
            }
        }
        if ($change_hook) {
            Cache::clean('hook_module_list');
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

        Media::addJsDef([
            'ebayfeedback' => [
                'feedback_url' => Context::getContext()->link->getModuleLink('ebayfeedback', 'ebayfeedback'),
            ],
        ]);
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    public function hookDisplayHeader()
    {
        //     if (!Configuration::get('EBAYFEEDBACK_ACTIVE')) {
        //         return;
        //     }

        Media::addJsDef([
            'ebayfeedback' => [
                'feedback_url' => Context::getContext()->link->getModuleLink('ebayfeedback', 'ebayfeedback'),
            ],
        ]);
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

    //public function renderWidget($hookName, array $configuration)
    public function renderWidget($hookName)
    {
        $this->context->smarty->assign([
            'feedback_url' => Context::getContext()->link->getModuleLink('ebayfeedback', 'ebayfeedback'),
            'currentHook' => $hookName
        ]);

        return $this->display(__FILE__, 'dummy.tpl');

        //return $result . " - " . $feedbackCount;
    }

    // public function getWidgetVariables($hookName, array $configuration)
    // {
    //     return false;
    // }
}
