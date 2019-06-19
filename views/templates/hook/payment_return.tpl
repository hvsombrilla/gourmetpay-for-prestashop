{*
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
*}

{if $status == 'ok'}
    
      <h2 style="text-align: center;"> {l s='Su orden en %s ya ha sido generada y espera por su pago' sprintf=[$shop_name] d='Modules.Wirepayment.Shop'}</h2> 
      <p>{l s='Escanee el siguiente código desde la opcion pagar de GourmetPay:' d='Modules.Wirepayment.Shop'}</p>


    <p style="text-align: center;">
      <img src="{$imagesrc}" alt="" width="300px" height="300px">
    </p>
    {include file='module:gourmetpay/views/templates/hook/_partials/payment_infos.tpl'}

      <h2 class="text-center">O paga desde la web...</h2>
      <form action="https://bonosgourmet.com/pagar/" method="POST">
        <input type="hidden" name="email" value="{$gourmetpayOwner}">
        <input type="hidden" name="reference" value="{$reference}">
        <input type="hidden" name="amount" value="{$total_unformated}">
        <p class="text-center"><input type="submit" value="Pagar en la Web"  class="btn btn-primary"></p>
      </form>
      

    <p>
      {l s='Si tiene alguna duda contacte al [1]equipo de soporte[/1].' d='Modules.Wirepayment.Shop' sprintf=['[1]' => "<a href='{$contact_url}'>", '[/1]' => '</a>']}
    </p>
{else}
    <p class="warning">
      {l s='We noticed a problem with your order. If you think this is an error, feel free to contact our [1]expert customer support team[/1].' d='Modules.Wirepayment.Shop' sprintf=['[1]' => "<a href='{$contact_url}'>", '[/1]' => '</a>']}
    </p>
{/if}
