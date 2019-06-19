<?php
/*
* 2007-2015 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Gourmetpay extends PaymentModule
{


    protected $_html = '';
    protected $_postErrors = array();

    public $details;
    public $owner;
    public $address;
    public $extra_mail_vars;

    public function __construct()
    {
        $this->name = 'gourmetpay';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->ps_versions_compliancy = array('min' => '1.5.0', 'max' => _PS_VERSION_);
        $this->author = 'BonosGourmet';
        $this->controllers = array('payment', 'validation');
        $this->is_eu_compatible = 1;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $config = Configuration::getMultiple(array('GOURMETCOIN_DETAILS', 'GOURMETCOIN_EMAIL', 'GOURMETCOIN_SECRET_KEY'));
        if (!empty($config['GOURMETCOIN_EMAIL'])) {
            $this->owner = $config['GOURMETCOIN_EMAIL'];
        }

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('GourmetPay', array(), 'Modules.Gourmetpay.Admin');
        $this->description = $this->trans('Acepta pagos con Bonos Gourmet.', array(), 'Modules.Gourmetpay.Admin');
        $this->confirmUninstall = $this->trans('Are you sure about removing these details?', array(), 'Modules.Gourmetpay.Admin');
        if (!isset($this->owner)) {
            $this->warning = $this->trans('Aun no ha especificado su correo de Bonos Gourmet.', array(), 'Modules.Gourmetpay.Admin');
        }
        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->trans('No currency has been set for this module.', array(), 'Modules.Gourmetpay.Admin');
        }

        $this->extra_mail_vars = array(
                                        '{gourmetcoin_owner}' => Configuration::get('GOURMETCOIN_EMAIL'),
                                        
                                        );
    }

    public function install()
    {

        if (!parent::install() || !$this->registerHook('paymentReturn') || !$this->registerHook('paymentOptions')) {
            return false;
        }
        return true;
    }

    public function uninstall()
    {
        $languages = Language::getLanguages(false);
        foreach ($languages as $lang) {
            if (!Configuration::deleteByName('GOURMETCOIN_CUSTOM_TEXT', $lang['id_lang'])) {
                return false;
            }
        }

        if (!Configuration::deleteByName('GOURMETCOIN_DETAILS')
                || !Configuration::deleteByName('GOURMETCOIN_EMAIL')
                || !parent::uninstall()) {
            return false;
        }
        return true;
    }

    protected function _postValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {
            

            if (!Tools::getValue('GOURMETCOIN_EMAIL')) {
                //$this->_postErrors[] = $this->trans('Account details are required.', array(), 'Modules.Gourmetpay.Admin');
            } elseif (!Tools::getValue('GOURMETCOIN_EMAIL')) {
                $this->_postErrors[] = $this->trans('Su correo de Bonos Gourmet es requerido.', array(), "Modules.Gourmetpay.Admin");
            }
        }
    }

    protected function _postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
           // Configuration::updateValue('GOURMETCOIN_DETAILS', Tools::getValue('GOURMETCOIN_DETAILS'));
            Configuration::updateValue('GOURMETCOIN_EMAIL', Tools::getValue('GOURMETCOIN_EMAIL'));
             Configuration::updateValue('GOURMETCOIN_SECRET_KEY', Tools::getValue('GOURMETCOIN_SECRET_KEY'));

            $custom_text = array();
            $languages = Language::getLanguages(false);
            foreach ($languages as $lang) {
                if (Tools::getIsset('GOURMETCOIN_CUSTOM_TEXT_'.$lang['id_lang'])) {
                    $custom_text[$lang['id_lang']] = Tools::getValue('GOURMETCOIN_CUSTOM_TEXT_'.$lang['id_lang']);
                }
            }
            Configuration::updateValue('GOURMETCOIN_CUSTOM_TEXT', $custom_text);
        }
        $this->_html .= $this->displayConfirmation($this->trans('Settings updated', array(), 'Admin.Global'));
    }

    protected function _displayBankWire()
    {
        return $this->display(__FILE__, 'infos.tpl');
    }

    public function getContent()
    {
        if (Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            if (!count($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
        } else {
            $this->_html .= '<br />';
        }

        $this->_html .= $this->_displayBankWire();
        $this->_html .= $this->renderForm();


        return $this->_html;
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $this->smarty->assign(
            $this->getTemplateVarInfos()
        );

        $newOption = new PaymentOption();
        $newOption->setModuleName($this->name)
                ->setCallToActionText($this->trans('Pagar usando Gourmet Pay', array(), 'Modules.Gourmetcoin.Shop'))
                ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
                ->setAdditionalInformation($this->fetch('module:gourmetpay/views/templates/hook/gourmetpay_intro.tpl'));
        $payment_options = [
            $newOption,
        ];

        return $payment_options;
    }

    public function hookPaymentReturn($params)
    {
        // if (!$this->active) {
        //     return;
        // }

        $state = $params['order']->getCurrentState();
        if (
            in_array(
                $state,
                array(
                    Configuration::get('PS_OS_BANKWIRE'),
                    Configuration::get('PS_OS_OUTOFSTOCK'),
                    Configuration::get('PS_OS_OUTOFSTOCK_UNPAID'),
                )
        )) {
            $bankwireOwner = $this->owner;
            if (!$bankwireOwner) {
                $bankwireOwner = '___________';
            }





            $this->smarty->assign(array(
                'shop_name' => $this->context->shop->name,
                'total' => Tools::displayPrice(
                    $params['order']->getOrdersTotalPaid(),
                    new Currency($params['order']->id_currency),
                    false
                ),

                'gourmetpayOwner' => $bankwireOwner,
                'status' => 'ok',
                'reference' => $params['order']->reference,
                'total_unformated' => number_format($params['order']->getOrdersTotalPaid(), 2),
                'imagesrc' => 'http://chart.apis.google.com/chart?cht=qr&chs=300x300&chl=' . urlencode($bankwireOwner) . '%3A' . urlencode(number_format($params['order']->getOrdersTotalPaid(), 2)). '%3A' . urlencode($params['order']->reference). '&chld=H|0',
                'contact_url' => $this->context->link->getPageLink('contact', true)
            ));
        } else {
            $this->smarty->assign(
                array(
                    'status' => 'failed',
                    'contact_url' => $this->context->link->getPageLink('contact', true),
                )
            );
        }

        return $this->fetch('module:gourmetpay/views/templates/hook/payment_return.tpl');
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->trans('Account details', array(), 'Modules.Gourmetpay.Admin'),
                    'icon' => 'icon-envelope'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Correo de Bonos Gourmet', array(), 'Modules.Gourmetpay.Admin'),
                        'name' => 'GOURMETCOIN_EMAIL',
                        'required' => true
                    ),
                    // array(
                    //     'type' => 'textarea',
                    //     'label' => $this->trans('Account details', array(), 'Modules.Gourmetpay.Admin'),
                    //     'name' => 'GOURMETCOIN_DETAILS',
                    //     'desc' => $this->trans('Such as bank branch, IBAN number, BIC, etc.', array(), 'Modules.Gourmetpay.Admin'),
                    //     'required' => true
                    // ),

                    array(
                        'type' => 'text',
                        'label' => $this->trans('Secret Key', array(), 'Modules.Gourmetpay.Admin'),
                        'name' => 'GOURMETCOIN_SECRET_KEY',
                        'desc' => $this->trans('Agregue una clave secreta para las validaciones automaticas.', array(), 'Modules.Gourmetpay.Admin'),
                        'required' => true
                    ),



                ),
                'submit' => array(
                    'title' => $this->trans('Save', array(), 'Admin.Actions'),
                )
            ),
        );
        
        if (Configuration::get('GOURMETCOIN_SECRET_KEY') != '') {
           $fields_form['form']['input'][] = array(
                        'type' => 'text',
                        'name' => 'GOURMETCOIN_WEBHOOK',
                        'desc' => 'Pegue esta url en su backoffice de Bonos Gourmet > Ajustes > Opciones de Tienda > WebHook',
                        'label' => $this->trans('WebHook', array(), 'Modules.Gourmetpay.Admin'),
                        'disabled' => true
                    );
        }
        

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? : 0;
        $this->fields_form = array();
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='
            .$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        $custom_text = array();
        $languages = Language::getLanguages(false);
        foreach ($languages as $lang) {
            $custom_text[$lang['id_lang']] = Tools::getValue(
                'GOURMETCOIN_CUSTOM_TEXT_'.$lang['id_lang'],
                Configuration::get('GOURMETCOIN_CUSTOM_TEXT', $lang['id_lang'])
            );
        }

        return array(
            'GOURMETCOIN_DETAILS' => Tools::getValue('GOURMETCOIN_DETAILS', Configuration::get('GOURMETCOIN_DETAILS')),
            'GOURMETCOIN_EMAIL' => Tools::getValue('GOURMETCOIN_EMAIL', Configuration::get('GOURMETCOIN_EMAIL')),
            'GOURMETCOIN_SECRET_KEY' => Tools::getValue('GOURMETCOIN_SECRET_KEY', Configuration::get('GOURMETCOIN_SECRET_KEY')),
            'GOURMETCOIN_WEBHOOK' => _PS_BASE_URL_ . __PS_BASE_URI__  ."modules/gourmetpay/webhook.php?key=".Configuration::get('GOURMETCOIN_SECRET_KEY'),
            'GOURMETCOIN_CUSTOM_TEXT' => $custom_text
        );
    }

    public function getTemplateVarInfos()
    {
        $cart = $this->context->cart;
        $total = sprintf(
            $this->trans('%1$s (tax incl.)', array(), 'Modules.Gourmetcoin.Shop'),
            Tools::displayPrice($cart->getOrderTotal(true, Cart::BOTH))
        );

         $gourmetpayOwner = $this->owner;
        if (!$bankwireOwner) {
            $gourmetpayOwner = '___________';
        }



        return array(
            'total' => $total,
            'gourmetpayOwner' => $gourmetpayOwner
        );
    }
}
