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
 * @copyright  Copyright (c) 2012 initOS GmbH & Co. KG, 2012 Payment Network AG
 * @author Markus Schneider <markus.schneider@initos.com>
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version	$Id: Ideal.php 3844 2012-04-18 07:37:02Z dehn $
 */

require_once Mage::getModuleDir('', 'Paymentnetwork_Pnsofortueberweisung').'/Helper/library/sofortLib.php';
require_once Mage::getModuleDir('', 'Paymentnetwork_Pnsofortueberweisung').'/Helper/library/sofortLib_ideal_classic.php';

class Paymentnetwork_Pnsofortueberweisung_Model_Ideal extends Paymentnetwork_Pnsofortueberweisung_Model_Abstract {

    /**
     * payment code
     * 
     * @var string
     */
	protected $_code = 'sofort_ideal'; 
	
	/**
	 * name of template with extra form elements
	 * 
	 * @var string
	 */
	protected $_formBlockType = 'pnsofortueberweisung/form_ideal';	
	
	/**
	 * returns Url to redirect for payment or error
	 * 
	 * @return string
	 */
	public function getUrl(){
	    
	    // get order
	    $order 		= $this->getOrder();
	    
	    // basic setup
	    $sofort = new SofortLib_iDealClassic(Mage::getStoreConfig('payment/sofort_ideal/configkey'), Mage::getStoreConfig('payment/sofort_ideal/password'), 'sha1');
	    $sofort->setVersion(self::MODULE_VERSION);
	    // add amount
	    $amount		= number_format($order->getGrandTotal(),2,'.','');
	    $sofort->setAmount($amount, $order->getOrderCurrencyCode());
	    // add reason
	    $reason1 	= Mage::helper('pnsofortueberweisung')->__('Order No.: ').$order->getRealOrderId();
		$reason1 = preg_replace('#[^a-zA-Z0-9+-\.,]#', ' ', $reason1);
		$reason2 	= Mage::getStoreConfig('general/store_information/name');
		$reason2 = preg_replace('#[^a-zA-Z0-9+-\.,]#', ' ', $reason2);
	    $sofort->setReason($reason1, $reason2);
	    
	    // setup urls
		$success_url = Mage::getUrl('pnsofortueberweisung/sofort/return',array('orderId'=>$order->getRealOrderId(), '_secure'=>true));
		$success_url = preg_replace('/^http(s?):\/\//','',$success_url);
		$cancel_url = Mage::getUrl('pnsofortueberweisung/sofort/error',array('orderId'=>$order->getRealOrderId()));
		$cancel_url = preg_replace('/^http(s?):\/\//','',$cancel_url);
		$notification_url = Mage::getUrl('pnsofortueberweisung/sofort/notificationIdeal',array('orderId'=>$order->getRealOrderId()));
		$notification_url = preg_replace('/^http(s?):\/\//','',$notification_url);
		$sofort->setSuccessUrl($success_url);
		$sofort->setAbortUrl($cancel_url);
		$sofort->setNotificationUrl($notification_url);
	    
		// setup bank information
	    $sofort->setSenderCountryId('NL');
	    $sofort->setSenderBankCode(Mage::getSingleton('core/session')->getIdealBankCode());
	    $sofort->setSenderHolder(Mage::getSingleton('core/session')->getIdealHolder());
	    $sofort->setSenderAccountNumber(Mage::getSingleton('core/session')->getIdealAccountNumber());
	    
	    return $sofort->getPaymentUrl();
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
	
	/**
	 * save data from request to session
	 * 
	 * @param Varien_Object $data
	 * @return $this
	 */
	public function assignData($data)
	{
	   	if (!($data instanceof Varien_Object)) {
			$data = new Varien_Object($data);
		}

		Mage::getSingleton('core/session')
				->setIdealAccountNumber($data->getIdealAccountNumber())
				->setIdealBankCode($data->getIdealBankCode())
				->setIdealHolder($data->getIdealHolder());   
				  
		return $this;
	}	
	
	/**
	 * validates data of the payment form on server side
	 * 
	 */
	public function validate()
	{
		parent::validate();
		$session = Mage::getSingleton('core/session');
		if(Mage::getStoreConfig('payment/sofort_ideal/ask_holder')){
    		if (!$session->getIdealHolder()) {
    			Mage::throwException(Mage::helper('pnsofortueberweisung')->__('Please fill out the account holder'));
    		}
    		if (!$session->getIdealAccountNumber()) {
    			Mage::throwException(Mage::helper('pnsofortueberweisung')->__('Please fill out the account number'));
    		}
		}
		if (!$session->getIdealBankCode()) {
			Mage::throwException(Mage::helper('pnsofortueberweisung')->__('Please fill out the bank code'));
		}
		
		return $this;
	}	
}
