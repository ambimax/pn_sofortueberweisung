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
 * @copyright  Copyright (c) 2011 Payment Network AG, 2012 initOS GmbH & Co. KG
 * @author Payment Network AG http://www.payment-network.com (integration@payment-network.com)
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version	$Id: Sofortrechnung.php 3844 2012-04-18 07:37:02Z dehn $
 */
require_once Mage::getModuleDir('', 'Paymentnetwork_Pnsofortueberweisung').'/Helper/library/sofortLib.php';

class Paymentnetwork_Pnsofortueberweisung_Model_Sofortrechnung extends Paymentnetwork_Pnsofortueberweisung_Model_Abstract
{
	
	/**
	* Availability options
	*/
	protected $_code = 'sofortrechnung'; 
	protected $_formBlockType = 'pnsofortueberweisung/form_sofortrechnung';
	protected $_infoBlockType = 'pnsofortueberweisung/info_sofortrechnung';
	protected $_canCapture = true;
	protected $_canCancelInvoice = true;
	protected $_canCapturePartial = true;
	protected $_canVoid = false;
	protected $_canRefund = true;
	protected $_isGateway = true;
	
	/**
	 * returns url to call to register payment with sofortrechnung
	 * 
	 * @return string
	 */
	public function getUrl(){
	    // get current order
		$order 		= $this->getOrder();
		// generate new key
		$security 	= $this->getSecurityKey();
		// generate payment request object	
		try{
		    $sObj = $this->createPaymentFromOrder($order, $security);
		}catch (Exception $e){
		    Mage::getSingleton('checkout/session')->addError($e->getMessage());
		    return Mage::getUrl('checkout/cart', array('_secure'=>true));
		}
        // register payment to service
		$sObj->sendRequest();
		// if everythink fine
		if(!$sObj->isError()) {
			$tid = $sObj->getTransactionId();
			$order->getPayment()->setTransactionId($tid)->setIsTransactionClosed(0);			
			$order->getPayment()->setAdditionalInformation('sofort_transaction', $tid);
			$order->getPayment()->setAdditionalInformation('sofort_lastchanged', 0);
			$order->getPayment()->setAdditionalInformation('sofort_secret', $security)->save();
			
			Mage::getSingleton('checkout/session')->setData('sofort_aborted', 1);
			
			return $sObj->getPaymentUrl();
		// something wrong
		} else {	
			$errors = $sObj->getErrors();
			foreach($errors as $error) {
				Mage::getSingleton('checkout/session')->addError(Mage::helper('pnsofortueberweisung')->localizeXmlError($error));
			}

			return Mage::getUrl('pnsofortueberweisung/sofort/error',array('orderId'=>$order->getRealOrderId()));
		}
	}
	
	/**
	 * create the connection class and add order info
	 * 
	 * @param Mage_Sales_Model_Order_Item $order
	 * @param string $security key for information
	 */
	public function createPaymentFromOrder($order, $security = null){
	    
	    // check if security key is given
		if($security === null){
		    // get existing security key
		    $security = $order->getPayment()->getAdditionalInformation('sofort_secret');
		    // generate new one
		    if(empty($security)){
		        $security 	= $this->getSecurityKey();
		    }
		}
		
		// create new object
		$sObj = new SofortLib_Multipay(Mage::getStoreConfig('payment/sofort/configkey'));
		$sObj->setVersion(self::MODULE_VERSION);
		
		// set type
		$sObj->setSofortrechnung();
		
		// basic information
		$sObj->addUserVariable($order->getRealOrderId());
		$sObj->setEmailCustomer($order->getCustomerEmail());
		$sObj->setSofortrechnungCustomerId($order->getCustomerId());
		$sObj->setSofortrechnungOrderId($order->getRealOrderId());
		
		// add order number and shop name
	    $reason1 = Mage::helper('pnsofortueberweisung')->__('Order No.: ').$order->getRealOrderId();
		$reason1 = preg_replace('#[^a-zA-Z0-9+-\.,]#', ' ', $reason1);
		$reason2 = Mage::getStoreConfig('general/store_information/name');
		$reason2 = preg_replace('#[^a-zA-Z0-9+-\.,]#', ' ', $reason2);
		$sObj->setReason($reason1, $reason2);
		
		// set amount
		$amount		= number_format($order->getGrandTotal(),2,'.','');
		$sObj->setAmount($amount, $order->getOrderCurrencyCode());
				
		// setup urls
		$success_url = Mage::getUrl('pnsofortueberweisung/sofort/return',array('orderId'=>$order->getRealOrderId(), '_secure'=>true));
		$cancel_url = Mage::getUrl('pnsofortueberweisung/sofort/error',array('orderId'=>$order->getRealOrderId()));
		$notification_url = Mage::getUrl('pnsofortueberweisung/sofort/notification',array('orderId'=>$order->getRealOrderId(), 'secret' =>$security));
		$sObj->setSuccessUrl($success_url);
		$sObj->setAbortUrl($cancel_url);
		$sObj->setNotificationUrl($notification_url);

		// items, shipping, discount
	    $this->__appendItems($order, $sObj);
		
		// invoice address
		$address = $order->getBillingAddress();
		$sObj->setSofortrechnungInvoiceAddress( $this->_getFirstname($address),
		                                        $this->_getLastname($address),
		                                        $this->_getStreet($address), 
		                                        $this->_getNumber($address), 
		                                        $this->_getPostcode($address),
		                                        $this->_getCity($address), 
		                                        $this->_getSalutation($address), 
		                                        $this->_getCountry($address),
		                                        $this->_getNameAdditive($address));

		// shipping address
		$address = $order->getShippingAddress();
		$sObj->setSofortrechnungShippingAddress($this->_getFirstname($address),
		                                        $this->_getLastname($address),
		                                        $this->_getStreet($address), 
		                                        $this->_getNumber($address), 
		                                        $this->_getPostcode($address),
		                                        $this->_getCity($address), 
		                                        $this->_getSalutation($address), 
		                                        $this->_getCountry($address),
		                                        $this->_getNameAdditive($address));				
	    
	    return $sObj;
	}

	/**
	 * append items, shipping and discount to the payment object
	 * 
	 * @param Mage_Sales_Model_Order_Item $order
	 * @param SofortLib_Multipay $sofortPayment
	 */
	private function __appendItems($order, $sofortPayment) {
		//items
		$discountTax = 19;
		foreach ($order->getAllVisibleItems() as $item) {
		    
		    if(($item->product_type == 'downloadable' || $item->product_type == 'virtual')
		        && $item->getRowTotal() > 0){
		        throw new Exception(Mage::helper('pnsofortueberweisung')->__('Kauf auf Rechnung not allowed for downloadable or virtual products'));
		    }
		    
            $name = $item->getName();
            $uid = $item->getSku()."-".$item->getItemId();
            // FIXME getDescription is not default method for Mage_Sales_Model_Order_Item ?
            $desc = $item->getDescription();
            // configurable product
            if ($item->product_type == 'configurable'){
                $productOptions= unserialize($item->product_options);
                // check attributes
                if(!empty($productOptions['attributes_info'])){
                    $configAttr = array();
                    foreach ($productOptions['attributes_info'] as $pOp){
                        $configAttr[] =  $pOp['value'];
                    }
                    if(!empty($configAttr)){
                        $desc = implode(", ",$configAttr)."\n".$desc;
                    }
                }
            }
            // handle bundles
            else if ($item->product_type == 'bundle'){
                $productOptions = unserialize($item->product_options);
                // check bundle options
                if(!empty($productOptions['bundle_options'])){
                    $bundleTitle = array();
                    foreach ($productOptions['bundle_options'] as $pOp){
                        if(!empty($pOp['value'])){
                            foreach ($pOp['value'] as $bValue){
                                $bundleTitle[] = $bValue['title'];
                            }
                        }
                        
                    }
                    if(!empty($bundleTitle)){
                        $desc = implode(", ",$bundleTitle)."\n".$desc;
                    }
                }
            }
            
			// add item
            $sofortPayment->addSofortrechnungItem(md5($uid), 
		                                          $item->getSku(), 
		                                          $name, 
		                                          $this->_getPriceInclTax($item), 
		                                          0, 
		                                          $desc, 
		                                          ( $item->getQtyOrdered() - $item->getQtyCanceled() ), 
		                                          $item->getTaxPercent()
		                                          );
		                                          
			if($item->getTaxPercent() > 0) {
			    // tax of discount is min of cart-items
				$discountTax = min($item->getTaxPercent(), $discountTax);
			}

		}
		
		
		//shipping
		if($order->getShippingAmount() != 0) {
			$shippingTax = round($order->getShippingTaxAmount()/$order->getShippingAmount()*100);
		}
		else {
			$shippingTax = 0;
		}
		// check if amount is removed
		if($order->getShippingAmount() > 0){
		    $sofortPayment->addSofortrechnungItem(1, 1, $order->getShippingDescription(), $this->_getShippingInclTax($order), 1, '', 1, $shippingTax);
		}
		
		//discount
		if($order->getDiscountAmount() != 0) {
			$sofortPayment->addSofortrechnungItem(2, 2, Mage::helper('sales')->__('Discount'), $order->getDiscountAmount(), 2, '', 1, $discountTax);
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
	

	
	/**
	 * check billing country is allowed for the payment method
	 *
	 * @return bool
	 */
	public function canUseForCountry($country)
	{
		//we only support DE right now
		return strtolower($country) == 'de' && parent::canUseForCountry($country);
	}	
	
	/**
	 * we deactivate this payment method if it was aborted before
	 * 
	 * @return bool
	 */
	public function canUseCheckout() {
		$aborted = Mage::getSingleton('checkout/session')->getData('sofort_aborted') == 1;
		
		return !$aborted && parent::canUseCheckout();
	}
	
	 /**
	 * Capture payment
	 *
	 * @param Mage_Sales_Model_Order_Payment $payment
	 * @return Mage_Paypal_Model_Payflowpro
	 */
	public function capture(Varien_Object $payment, $amount)
	{
		$tid = $payment->getAdditionalInformation('sofort_transaction');
		$payment->setTransactionId($tid);
		return $this;
	}	
	
	/**
	 * Refund money
	 *
	 * @param   Varien_Object $invoicePayment
	 * @return  Mage_GoogleCheckout_Model_Payment
	 */
	public function refund(Varien_Object $payment, $amount) {
		
		$tid = $payment->getAdditionalInformation('sofort_transaction');
		$order = $payment->getOrder();
		if(!empty($tid)) {
			$sObj = new SofortLib_ConfirmSr(Mage::getStoreConfig('payment/sofort/configkey'));
			$sObj->cancelInvoice($tid)->setComment('refund')->sendRequest();
			if($sObj->isError()) {
				Mage::throwException($sObj->getError());
			} else {
				$payment->setTransactionId($tid.'-refund')
					->setShouldCloseParentTransaction(true)
					->setIsTransactionClosed(0);		
			
				$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('The invoice has been canceled.'))->setIsVisibleOnFront(true);
				$order->save();
					
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('pnsofortueberweisung')->__('Successfully canceled invoice. Credit memo created: %s', $tid));
				return $this;
			}		
		}
		
		Mage::getSingleton('adminhtml/session')->addError(Mage::helper('pnsofortueberweisung')->__('Could not cancel invoice.'));	  	
		

		return $this;
	}
	
    /**
     * update invoice items to sofortueberweisung
     * 
     * @param Varien_Object $payment object of the order
     * @param array $items of the the invoice
     * @param string $comment to add
     * @return Paymentnetwork_Pnsofortueberweisung_Model_Sofortrechnung
     * @throws Exception
     */
	public function updateInvoice(Varien_Object $payment, $items, $comment) {
	    
	    // load current transaction id
	    $tid = $payment->getAdditionalInformation('sofort_transaction');
		$order = $payment->getOrder();
		
		if(!empty($tid)) {
		    // create connection class
			$sObj = new SofortLib_ConfirmSr(Mage::getStoreConfig('payment/sofort/configkey'));
			$sObj->setTransaction($tid);
			$sObj->setComment($comment);
			
			// add edit items
			foreach($items as $item){
			    $sObj->addItem($item['item_id'], $item['product_number'], $item['product_type'], $item['title'], $item['description'], $item['quantity'], $item['unit_price'], $item['tax']);
			}
			
			// send request
			$sObj->sendRequest();
			
			// add error
			if($sObj->isError()) {
				Mage::throwException($sObj->getError());
			} else {
			    // update history
				$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('The invoice has been edit.')."\n\n\"".$comment.'"');
				$order->save();
					
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('pnsofortueberweisung')->__('Successfully edit invoice.'));
				return $this;
			}		
		}
		// no transaction id exist
		Mage::getSingleton('adminhtml/session')->addError(Mage::helper('pnsofortueberweisung')->__('Could not edit invoice.'));	  	
		
        return $this;
	}
	
	
	/*
	 * workaround for magento < 1.4.1
	 */
	private function _getPriceInclTax($item)
	{
		if ($item->getPriceInclTax()) {
			return $item->getPriceInclTax();
		}
		$qty = ($item->getQty() ? $item->getQty() : ($item->getQtyOrdered() ? $item->getQtyOrdered() : 1));
		$price = (floatval($qty)) ? ($item->getRowTotal() + $item->getTaxAmount())/$qty : 0;
		return Mage::app()->getStore()->roundPrice($price);
	}

	/*
	 * workaround for magento < 1.4.1
	 */
	private function _getShippingInclTax($order) 
	{
		if($order->getShippingInclTax()) {
			return $order->getShippingInclTax();
		}
		
		$price = $order->getShippingTaxAmount()+$order->getShippingAmount();
		return Mage::app()->getStore()->roundPrice($price);
	}
}