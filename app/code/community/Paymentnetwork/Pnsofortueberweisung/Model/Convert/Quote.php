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
 * @copyright  Copyright (c) 2008 [m]zentrale GbR, 2010 Payment Network AG
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version	$Id: Quote.php 3844 2012-04-18 07:37:02Z dehn $
 */
class Paymentnetwork_Pnsofortueberweisung_Model_Convert_Quote extends Mage_Sales_Model_Convert_Quote
{

	/**
	 * Convert quote payment to order payment
	 *
	 * @param   Mage_Sales_Model_Quote_Payment $payment
	 * @return  Mage_Sales_Model_Quote_Payment
	 */
	public function paymentToOrderPayment(Mage_Sales_Model_Quote_Payment $payment)
	{
		$orderPayment = parent::paymentToOrderPayment($payment);
		$orderPayment->setSuAccountNumber($payment->getSuAccountNumber())
						->setSuBankCode($payment->getSuBankCode())
						->setSuNlBankCode($payment->getSuNlBankCode())
						->setSuPaycode($payment->getSuPaycode())
						->setSuSecurity($payment->getSuSecurity())
						->setSuIban($payment->getSuIban())
						->setSuBic($payment->getSuBic())
						->setSuHolder($payment->getSuHolder());	
		
		return $orderPayment;
	}

}
