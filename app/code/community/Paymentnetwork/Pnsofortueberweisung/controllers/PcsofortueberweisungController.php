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
 * @version	$Id: PcsofortueberweisungController.php 3844 2012-04-18 07:37:02Z dehn $
 */
class Paymentnetwork_Pnsofortueberweisung_PaycodeController extends Mage_Core_Controller_Front_Action
{
	
	public function returnAction()
	{
		
		$response = $this->getRequest()->getParams();
		
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($response['orderId']);
		$paymentObj = $order->getPayment()->getMethodInstance();
		$payment = $order->getPayment();
		
		if(!$payment->getSuTransactionId() && md5($payment->getSuSecurity().$paymentObj->getConfigData('project_pswd')) == $response['var1']){
			$status = $this->_checkReturnedData();
			if ($status && $response['orderId']) {
				$order = Mage::getModel('sales/order');
				$order->loadByIncrementId($response['orderId']);
				if($order->getId()) {			
					$order->sendNewOrderEmail();
				} 
				$this->_redirect('checkout/onepage/success');
			} else {  			
				$order = Mage::getModel('sales/order');
				$order->loadByIncrementId($response['orderId']);
				
				$order->cancel();	
				$order->addStatusToHistory($order->getStatus(), Mage::helper('pnsofortueberweisung')->__('Customer cancled payment or payment error'));				
				$order->save();
				
				$this->_redirect('pnsofortueberweisung/pnsofortueberweisung/errornotice');
			}
		}else{
			$session = $this->getCheckout();			
			$session->getQuote()->setIsActive(false)->save();
			
			$this->_redirect('checkout/onepage/success');
		}
	}
	
	public function returnhttpAction()
	{
		if (!$this->getRequest()->isGet()) {
			$this->norouteAction();
			return;
		}
		
		$response = $this->getRequest()->getParams();	
		
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($response['orderId']);
		$paymentObj = $order->getPayment()->getMethodInstance();
		$payment = $order->getPayment();		
		
		if(!$payment->getSuTransactionId() && md5($payment->getSuSecurity().$paymentObj->getConfigData('project_pswd')) == $response['var1']){
			$status = $this->_checkReturnedData();
			if ($status) {				
				$order = Mage::getModel('sales/order');
				$order->loadByIncrementId($response['orderId']);
				if($order->getId()) {			
					$order->sendNewOrderEmail();
				}				
			} else {				
				$order = Mage::getModel('sales/order');
				$order->loadByIncrementId($response['orderId']);
				$order->cancel();	
				$order->addStatusToHistory($order->getStatus(), Mage::helper('pnsofortueberweisung')->__('Customer cancled payment or payment error'));				
				$order->save();			
			}
		}
	}
	
	public function errorAction()
	{
		$session = $this->getCheckout();			
		$session->getQuote()->setIsActive(false)->save();
		
		$order = Mage::getModel('sales/order');
		$order->load($this->getCheckout()->getLastOrderId());		
		$order->cancel();
		$order->addStatusToHistory($order->getStatus(), Mage::helper('pnsofortueberweisung')->__('Customer cancled payment')); 
		$order->save();
			
		$this->loadLayout();
		$this->getLayout()->getBlock('sofortueberweisungnotice');		
		$this->renderLayout();
	}	
	
	/**
	 * Checking Get variables.
	 * 
	 */
	protected function _checkReturnedData()
	{
		
		$status = false;
		if (!$this->getRequest()->isGet()) {
			$this->norouteAction();
			return;
		}
		
		//Get response
		$response = $this->getRequest()->getParams();		
		
		
		
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($response['orderId']);
		$paymentObj = $order->getPayment()->getMethodInstance();
		
		if($response["transId"] && $response['orderId'] && md5($order->getPayment()->getSuSecurity().$paymentObj->getConfigData('project_pswd')) == $response['var1']){
					
			$payment = $order->getPayment();
							
				
			$payment->setStatus(Paymentnetwork_Pnsofortueberweisung_Model_Sofortueberweisung::STATUS_SUCCESS);
			$payment->setStatusDescription(Mage::helper('pnsofortueberweisung')->__('Payment was successful.'));
			
			$order->addStatusToHistory($paymentObj->getConfigData('order_status'), Mage::helper('pnsofortueberweisung')->__('Payment was successful.'));
			
			$payment->setSuTransactionId($response["transId"]);
			$order->setPayment($payment);
			
			if($paymentObj->getConfigData('createinvoice') == 1){				
				if ($this->saveInvoice($order)) {
					$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
				}
			}		
			
			$status = true;
		} else {   
			$payment = $order->getPayment();
			$payment->setStatus(Paymentnetwork_Pnsofortueberweisung_Model_Sofortueberweisung::STATUS_DECLINED);
			
			$order->setPayment($payment);
			$order->cancel();
			$order->addStatusToHistory($order->getStatus(), Mage::helper('pnsofortueberweisung')->__('Payment was not successfull'));		   
			$status = false;
		}
		
		$order->save();
		return $status;
	}
	
	/**
	 *  Save invoice for order
	 *
	 *  @param	Mage_Sales_Model_Order $order
	 *  @return	  boolean Can save invoice or not
	 */
	protected function saveInvoice (Mage_Sales_Model_Order $order)
	{
		if ($order->canInvoice()) {
			$invoice = $order->prepareInvoice();

			$invoice->register();
			Mage::getModel('core/resource_transaction')
			   ->addObject($invoice)
			   ->addObject($invoice->getOrder())
			   ->save();
			
			$invoice->sendEmail(true, '');
			return true;
		}

		return false;
	}
	
	/**
	* Get singleton of Checkout Session Model
	*
	* @return Mage_Checkout_Model_Session
	*/
	public function getCheckout()
	{
		return Mage::getSingleton('checkout/session');
	}
}