<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Paymentnetwork
 * @package	Paymentnetwork_Sofortueberweisung
 * @copyright  Copyright (c) Payment Network AG
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version	$Id: Pnsofortueberweisung.php 3844 2012-04-18 07:37:02Z dehn $
 */
class Paymentnetwork_Pnsofortueberweisung_Block_Pnsofortueberweisung extends Mage_Core_Block_Abstract
{
	protected function _toHtml()
	{
		$payment = $this->getOrder()->getPayment()->getMethodInstance();
		
			$form = new Varien_Data_Form();

			$url = $payment->getAdditionalInformation('sofort_payment_url');

			//$url = $payment->getUrl();
			$form->setAction($url)
				->setId('pnsofortueberweisung')
				->setName('pnsofortueberweisung')
				->setMethod('POST')
				->setUseContainer(true);

			$html = '<html><body>';
			$html.= $this->__('You will be redirected in a few seconds. %s', $url);
			$html.= $form->toHtml();
			$html.= '<script type="text/javascript">document.getElementById("pnsofortueberweisung").submit();</script>';
			$html.= '</body></html>';

			return $html;
	}
}