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
 * @category Paymentnetwork
 * @package Paymentnetwork_Sofortueberweisung
 * @copyright Copyright (c) 2011 Payment Network AG
 * @author Payment Network AG http://www.payment-network.com (integration@payment-network.com)
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version $Id: PnsofortueberweisungController.php 3866 2012-04-18 11:52:31Z dehn $
 */
require_once Mage::getModuleDir('', 'Paymentnetwork_Pnsofortueberweisung').'/Helper/library/sofortLib.php';

class Paymentnetwork_Pnsofortueberweisung_PnsofortueberweisungController extends Mage_Core_Controller_Front_Action
{
	
	protected $_redirectBlockType = 'pnsofortueberweisung/pnsofortueberweisung';
	protected $mailAlreadySent = false;
		
	/**
	 * when customer select payment method
	 */
	public function redirectAction() {
		$session = $this->getCheckout();
		$session->setSofortQuoteId($session->getQuoteId());
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($session->getLastRealOrderId());
		
		$paymentObj = $order->getPayment()->getMethodInstance();
		$payment = $order->getPayment();
		
		switch($payment->getMethod()) {
			case 'sofortrechnung': 
				$order->addStatusToHistory($order->getStatus(), Mage::helper('pnsofortueberweisung')->__('Rechnung by sofort payment loaded.'));
			break;
			case 'sofortlastschrift': 
				$order->addStatusToHistory($order->getStatus(), Mage::helper('pnsofortueberweisung')->__('sofortlastschrift payment loaded.'));
			break;
			case 'lastschriftsofort': 
				$order->addStatusToHistory($order->getStatus(), Mage::helper('pnsofortueberweisung')->__('Lastschrift by sofort payment loaded.'));
			break;
			case 'sofortvorkasse': 
				$order->addStatusToHistory($order->getStatus(), Mage::helper('pnsofortueberweisung')->__('Vorkasse by sofort payment loaded.'));
			break;
			case 'pnsofort': 
				$order->addStatusToHistory($order->getStatus(), Mage::helper('pnsofortueberweisung')->__('Sofortueberweisung payment loaded.'));
			break;
		}
		
		$order->save();

		$url = $paymentObj->getUrl();
		$this->getResponse()->setRedirect($url);
		
		$session->unsQuoteId();
	}
	
	
	public function returnAction() {
		if (!$this->getRequest()->isGet()) {
			$this->norouteAction();
			return;
		}
		
		$response = $this->getRequest()->getParams();
		$session = $this->getCheckout();	
		$session->setQuoteId($session->getSofortQuoteId(true));
		$session->getQuote()->setIsActive(false)->save();
		
		if(!$response['orderId']) {
			$this->_redirect('pnsofortueberweisung/pnsofortueberweisung/errornotice');
		} else {
			$this->_redirect('checkout/onepage/success', array('_secure'=>true));
		}
		
	}
	
	
	public function returnhttpAction() {
		if (!$this->getRequest()->isPost()) {
			$this->norouteAction();
			return;
		}
		
		$response = $this->getRequest()->getParams();
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($response['orderId']);
		$paymentObj = $order->getPayment()->getMethodInstance();
		$payment = $order->getPayment();
		
		if($payment->getAdditionalInformation('sofort_lastchanged') >= 1) {
			Mage::log('Notification invalid: '.__CLASS__ . ' ' . __LINE__);
			return;
		}
		$order->getPayment()->setAdditionalInformation('sofort_lastchanged', 1);
		$order->save();
		//if(!$payment->getSuTransactionId() && md5($payment->getSuSecurity().$paymentObj->getConfigData('project_pswd')) == $response['var1']){
		$status = $this->_checkReturnedData();
		
		if ($status) {
			$order = Mage::getModel('sales/order');
			$order->loadByIncrementId($response['orderId']);
			if($order->getId()) {  
				$order->save();
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
	
	
	public function errorAction() {
		$session = $this->getCheckout();	
		$session->setQuoteId($session->getSofortQuoteId(true));
		$session->getQuote()->setIsActive(true)->save();
		$order = Mage::getModel('sales/order');
		$order->load($this->getCheckout()->getLastOrderId());
		$order->cancel();
		$order->addStatusToHistory($order->getStatus(), Mage::helper('pnsofortueberweisung')->__('Cancelation of payment')); 
		$order->save();
		Mage::getSingleton('checkout/session')->setData('sofort_aborted', 1);
		Mage::getSingleton('checkout/session')->addNotice(Mage::helper('pnsofortueberweisung')->__('Cancelation of payment'));
		$this->_redirect('checkout/cart');
		return;	
	}
	
	
	/**
	 * Checking Get variables.
	 * 
	 */
	protected function _checkReturnedData() {
		$status = false;
		
		if (!$this->getRequest()->isPost()) {
			$this->norouteAction();
			return;
		}
		
		//Get response
		$response = $this->getRequest()->getParams();
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($response['orderId']);
		$paymentObj = $order->getPayment()->getMethodInstance();
		$data = $this->getNotification($paymentObj->getConfigData('notification_pswd'));
		
		if(!is_array($data)) {
			$order->addStatusToHistory($order->getStatus(), Mage::helper('pnsofortueberweisung')->__($data)); 
			$order->save();
			$this->norouteAction();
			return;
		}
		
		$orderId = $data['user_variable_0'];
		//if($response["transId"] && $response['orderId'] && md5($order->getPayment()->getSuSecurity().$paymentObj->getConfigData('project_pswd')) == $response['var1']){					
		if($data['transaction'] &&  $response['orderId'] == $orderId){
			$payment = $order->getPayment();
			$payment->setStatus(Paymentnetwork_Pnsofortueberweisung_Model_Pnsofortueberweisung::STATUS_SUCCESS);
			$payment->setStatusDescription(Mage::helper('pnsofortueberweisung')->__('Payment was successful.',$data['transaction']));
			$payment->setAdditionalInformation('sofort_transaction', $data['transaction']);
			$payment->setTransactionId($data['transaction'])
					->setIsTransactionClosed(1);
			if(method_exists($payment, 'addTransaction')) {
				$payment->addTransaction('authorization'); //transaction overview in magento > 1.5
			}
			
			$order->setPayment($payment);
			$order->addStatusToHistory($paymentObj->getConfigData('order_status'), 
										Mage::helper('pnsofortueberweisung')->__('Payment was successful.',$data['transaction']),
										true);
			$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING);
			
			if($paymentObj->getConfigData('createinvoice') == 1){
				if ($this->saveInvoice($order)) {
					$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
				}
			}
			
			$status = true;
		} else {
			
			$payment = $order->getPayment();
			$payment->setStatus(Paymentnetwork_Pnsofortueberweisung_Model_Pnsofortueberweisung::STATUS_DECLINED);
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
	protected function saveInvoice (Mage_Sales_Model_Order $order) {
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
	public function getCheckout() {
		return Mage::getSingleton('checkout/session');
	}
	
	
	/**
	 * 	checks server response and gets parameters  
	 *  @return $data array|string response parameters or ERROR_WRONG_HASH|ERROR_NO_ORDER_DETAILS if error
	 * 
	 */
	public function getNotification($pwd) {
		$pnSu =  Mage::helper('pnsofortueberweisung');
		$pnSu->classPnSofortueberweisung($pwd);
		return $pnSu->getNotification();
	}
	
}