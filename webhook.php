<?php
/**
 * Integral logistics for eCommerce with pickup or fulfillment through Shipit
 *
 * @author    Rolige <www.rolige.com>
 * @copyright 2011-2018 Rolige - All Rights Reserved
 * @license   Proprietary and confidential
 */

require_once(dirname(__FILE__).'/../../config/config.inc.php');
include_once(dirname(__FILE__).'/gourmetpay.php');

$module = new Gourmetpay();



function getByRef($ref){
$sql = '
          SELECT id_order
            FROM `' . _DB_PREFIX_ . 'orders` o WHERE o.`reference` = \'' . pSQL($ref) . '\'';

        $id = (int) Db::getInstance()->getValue($sql);

        return $id;
}



if ($module->active AND Configuration::get('GOURMETCOIN_SECRET_KEY') != '' AND Configuration::get('GOURMETCOIN_SECRET_KEY') == Tools::getValue('key')) {	
	$reference =  Tools::getValue('reference');
	$amount =  Tools::getValue('amount');
	$id = getByRef($reference);

if (empty($amount) OR empty($reference) OR empty($id)) {
	die(':D');
}
	$ordenes = new OrderCore($id);



	if ($ordenes->total_paid == $amount ) {

		$ordenes->setCurrentState(2);
		exit('Orden Completada');
	}else{
		exit('Monto Incorrecto');
	}
	
}else{
	exit('Hay un error');
}


