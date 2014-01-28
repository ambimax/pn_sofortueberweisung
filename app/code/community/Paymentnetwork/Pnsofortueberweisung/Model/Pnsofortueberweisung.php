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
 * @version	$Id: Pnsofortueberweisung.php 3844 2012-04-18 07:37:02Z dehn $
 */

require_once Mage::getModuleDir('', 'Paymentnetwork_Pnsofortueberweisung').'/Helper/library/sofortLib_sofortueberweisung_classic.php';

class Paymentnetwork_Pnsofortueberweisung_Model_Pnsofortueberweisung extends Paymentnetwork_Pnsofortueberweisung_Model_Abstract
{
	
	/**
	* Availability options
	*/
	protected $_code = 'pnsofortueberweisung';   
	
  

	public function getUrl(){
		$order 		= $this->getOrder();
		$amount		= number_format($order->getGrandTotal(),2,'.','');
		$billing	= $order->getBillingAddress();
		$security 	= $this->getSecurityKey();
		$reason1 	= Mage::helper('pnsofortueberweisung')->__('Order No.: ').$order->getRealOrderId();
		$reason1 	= preg_replace('#[^a-zA-Z0-9+-\.,]#', ' ', $reason1);
		$reason2 	= Mage::getStoreConfig('general/store_information/name');
		$reason2 	= preg_replace('#[^a-zA-Z0-9+-\.,]#', ' ', $reason2);
		$success_url = Mage::getUrl('pnsofortueberweisung/pnsofortueberweisung/return',array('orderId'=>$order->getRealOrderId()));
		$cancel_url = Mage::getUrl('pnsofortueberweisung/pnsofortueberweisung/error',array('orderId'=>$order->getRealOrderId()));
		$notification_url = Mage::getUrl('pnsofortueberweisung/pnsofortueberweisung/returnhttp',array('orderId'=>$order->getRealOrderId(), 'transId' => '-TRANSACTION-', 'var1' => '-USER_VARIABLE_1_MD5_PASS-', 'secret'=>$security));
					
		$sObj = new SofortLib_SofortueberweisungClassic(Mage::getStoreConfig('payment/pnsofortueberweisung/customer'), Mage::getStoreConfig('payment/pnsofortueberweisung/project'), Mage::getStoreConfig('payment/pnsofortueberweisung/project_pswd'));
		$sObj->setVersion(self::MODULE_VERSION);
		$sObj->setAmount($amount, $this->getOrder()->getOrderCurrencyCode());
		$sObj->setReason($reason1, $reason2);
		$sObj->setSuccessUrl($success_url);
		$sObj->setAbortUrl($cancel_url);
		$sObj->setNotificationUrl($notification_url);
		$sObj->addUserVariable($this->getOrder()->getRealOrderId());
		$sObj->addUserVariable($security);

		//$sObj->sendRequest();
		
		if(!$sObj->isError()) {
			$url = $sObj->getPaymentUrl();
			//$tid = $sObj->getTransactionId();
			//$order->getPayment()->setPnSuTransactionId($tid)->save();
			$order->getPayment()->setAdditionalInformation('sofort_lastchanged', 0);
			$order->getPayment()->setAdditionalInformation('sofort_secret', $security)->save();			
			return $url;
		} else {	
			$errors = $sObj->getErrors();
			foreach($errors as $error)
				Mage::getSingleton('checkout/session')->addError((array_key_exists('field', $error) ? $error['field'] . ' - ' : '') . $error['message']);
			
			return $cancel_url;
		} 
	}
	
	/**
	 * Retrieve information from payment configuration
	 *
	 * @param   string $field
	 * @return  mixed
	 */
	public function getConfigData($field, $storeId = null) {
		if (null === $storeId) {
			$storeId = $this->getStore();
		}		
	
		return Mage::getStoreConfig('payment/'.$this->getCode().'/'.$field, $storeId);

	}	
	
}