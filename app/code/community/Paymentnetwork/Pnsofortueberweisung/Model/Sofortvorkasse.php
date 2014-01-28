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
 * @copyright  Copyright (c) 2011 Payment Network AG
 * @author Payment Network AG http://www.payment-network.com (integration@payment-network.com)
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version	$Id: Sofortvorkasse.php 3844 2012-04-18 07:37:02Z dehn $
 */

require_once Mage::getModuleDir('', 'Paymentnetwork_Pnsofortueberweisung').'/Helper/library/sofortLib.php';

class Paymentnetwork_Pnsofortueberweisung_Model_Sofortvorkasse extends Paymentnetwork_Pnsofortueberweisung_Model_Abstract
{
	
	/**
	* Availability options
	*/
	protected $_code = 'sofortvorkasse';   
	protected $_formBlockType = 'pnsofortueberweisung/form_sofortvorkasse';	
	
	public function getUrl(){
		$order 		= $this->getOrder();
		$amount		= number_format($order->getGrandTotal(),2,'.','');
		$billing	= $order->getBillingAddress();
		$security 	= $this->getSecurityKey();
		$reason1 	= Mage::helper('pnsofortueberweisung')->__('Order No.: ').$order->getRealOrderId();
		$reason1 = preg_replace('#[^a-zA-Z0-9+-\.,]#', ' ', $reason1);
		$reason2 	= Mage::getStoreConfig('general/store_information/name');
		$reason2 = preg_replace('#[^a-zA-Z0-9+-\.,]#', ' ', $reason2);
		$success_url = Mage::getUrl('pnsofortueberweisung/sofort/returnSofortvorkasse',array('orderId'=>$order->getRealOrderId(), '_secure'=>true));
		$cancel_url = Mage::getUrl('pnsofortueberweisung/sofort/error',array('orderId'=>$order->getRealOrderId()));
		$notification_url = Mage::getUrl('pnsofortueberweisung/sofort/notification',array('orderId'=>$order->getRealOrderId(), 'secret' =>$security));
			
		$sObj = new SofortLib_Multipay(Mage::getStoreConfig('payment/sofort/configkey'));
		$sObj->setVersion(self::MODULE_VERSION);
		$sObj->setAmount($amount, $order->getOrderCurrencyCode());
		$sObj->setReason($reason1, $reason2);
		$sObj->setSuccessUrl($success_url);
		$sObj->setAbortUrl($cancel_url);
		$sObj->setNotificationUrl($notification_url);
		$sObj->addUserVariable($order->getRealOrderId());
		$sObj->setEmailCustomer($order->getCustomerEmail());
		$sObj->setSenderAccount('', '', $order->getBillingAddress()->getFirstname() .' '. $order->getBillingAddress()->getLastname());
		//$sObj->setPhoneNumberCustomer($order->getCustomerTelephone());

		$sObj->setSofortvorkasse();
		
		$sObj->sendRequest();
		if(!$sObj->isError()) {
			$url = $sObj->getPaymentUrl();
			$tid = $sObj->getTransactionId();
			$order->getPayment()->setTransactionId($tid)->setIsTransactionClosed(0);			
			$order->getPayment()->setAdditionalInformation('sofort_transaction', $tid);
			$order->getPayment()->setAdditionalInformation('sofort_lastchanged', 0);
			$order->getPayment()->setAdditionalInformation('sofort_secret', $security)->save();					
			return $url;
		} else {	
			$errors = $sObj->getErrors();
			foreach($errors as $error)
				Mage::getSingleton('checkout/session')->addError(Mage::helper('pnsofortueberweisung')->localizeXmlError($error));
						
			return $cancel_url;
		}
	}
	
	/**
	 * Retrieve information from payment configuration
	 *
	 * @param   string $field
	 * @return  mixed
	 */
	public function getConfigData($field, $storeId = null)
	{

		return parent::getConfigData($field, $storeId);
	}
	
}