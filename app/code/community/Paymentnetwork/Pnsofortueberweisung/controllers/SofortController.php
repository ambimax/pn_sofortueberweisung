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
 * @version	$Id: SofortController.php 3844 2012-04-18 07:37:02Z dehn $
 */
require_once Mage::getModuleDir('', 'Paymentnetwork_Pnsofortueberweisung').'/Helper/library/sofortLib.php';

class Paymentnetwork_Pnsofortueberweisung_SofortController extends Mage_Core_Controller_Front_Action
{
	
	protected $_redirectBlockType = 'pnsofortueberweisung/pnsofortueberweisung';
	protected $mailAlreadySent = false;
		
	/**
	 * when customer selects payment method
	 */
	public function redirectAction()
	{
		$session = $this->getCheckout();
		Mage::log($session); 
		$session->setSofortQuoteId($session->getQuoteId());
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($session->getLastRealOrderId());
		$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Sofortueberweisung payment loaded'))->setIsVisibleOnFront(false);
		$order->save();

		$payment = $order->getPayment()->getMethodInstance();
		$url = $payment->getUrl();
		$this->getResponse()->setRedirect($url);
		
		$session->unsQuoteId();
	}
	
	/**
	 * when customer returns after transaction
	 */
	public function returnAction()
	{
		if (!$this->getRequest()->isGet()) {
			$this->norouteAction();
			return;
		}
		$response = $this->getRequest()->getParams();	

		$session = $this->getCheckout();	
		$session->setQuoteId($session->getSofortQuoteId(true));
		$session->getQuote()->setIsActive(false)->save();
		$session->setData('sofort_aborted', 0);
		
		if(!$response['orderId']) {
			$this->_redirect('pnsofortueberweisung/pnsofortueberweisung/errornotice');
		} else {
			$this->_redirect('checkout/onepage/success', array('_secure'=>true));
		}
	}
	
	/**
	 * Customer returns after sofortvorkasse transaction
	 */
	public function returnSofortvorkasseAction() 
	{
		$response = $this->getRequest()->getParams();
		$order = Mage::getModel('sales/order')->loadByIncrementId($response['orderId']);
		//$order->sendNewOrderEmail();
		$session = $this->getCheckout();	
		$session->setQuoteId($session->getSofortQuoteId(true));
		$session->getQuote()->setIsActive(false)->save();

		$this->loadLayout();
		$this->getLayout()->getBlock('sofortvorkassesuccess');		
		Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($order->getId())));
		$this->renderLayout();		
	}
	
	/**
	 * 
	 * customer canceled payment
	 */
	public function errorAction()
	{
		$session = $this->getCheckout();	
		$session->setQuoteId($session->getSofortQuoteId(true));
		$session->getQuote()->setIsActive(true)->save();
		
		$order = Mage::getModel('sales/order');
		$order->load($this->getCheckout()->getLastOrderId());		
		$order->cancel();
		$order->addStatusToHistory($order->getStatus(), Mage::helper('pnsofortueberweisung')->__('Cancelation of payment')); 
		$order->save();

		if(!($session->getData('sofort_aborted') == 1))
			$session->setData('sofort_aborted', 0);
			
		$session->addNotice(Mage::helper('pnsofortueberweisung')->__('Cancelation of payment'));
		$this->_redirect('checkout/cart');		
		return;	
	}	
	
	/**
	 * notification about status change
	 */
	public function notificationAction()
	{
		$response = $this->getRequest()->getParams();	
		$orderId = $response['orderId'];
		$secret = $response['secret'];

		$sofort = new SofortLib_Notification();
		$transaction = $sofort->getNotification(); 

		//no valid parameters/xml
		if(empty($orderId) || empty($transaction) || $sofort->isError()) {
			return;
		}

		$s = new SofortLib_TransactionData(Mage::getStoreConfig('payment/sofort/configkey'));
		$s->setTransaction($transaction)->sendRequest();
		
		if($s->isError()) {
			Mage::log('Notification invalid: '.__CLASS__ . ' ' . __LINE__ . $s->getError());
			return;
		}
		
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($orderId);
		$paymentObj = $order->getPayment()->getMethodInstance();		
		$payment = $order->getPayment();
		
		//data of transaction doesn't match order
		if($payment->getAdditionalInformation('sofort_transaction') != $transaction 
		|| $payment->getAdditionalInformation('sofort_secret') != $secret ) {
			Mage::log('Notification invalid: '.__CLASS__ . ' ' . __LINE__ );
			return;
		}

		// check if order was edit
		$this->_beEdit($s, $order);
		
		// check if something other change
		if( $payment->getAdditionalInformation('sofort_lastchanged') === $this->_getLastChanged($s) ) {
		    return;
		}

		$payment->setAdditionalInformation('sofort_lastchanged', $this->_getLastChanged($s))->save();

		
		if($s->isLoss())
			$this->_transactionLoss($s, $order);
		elseif($s->isPending() && $s->isSofortvorkasse())
			$this->_transactionUnconfirmed($s, $order);	
		elseif($s->isPending() && $s->isSofortrechnung() && $s->getStatusReason() == 'confirm_invoice')
			$this->_transactionUnconfirmed($s, $order);	
		elseif($s->isPending()) 
			$this->_transactionConfirmed($s, $order);
		elseif($s->isReceived() && $s->isSofortvorkasse()) 
			$this->_transactionConfirmed($s, $order);
		elseif($s->isReceived())
			$this->_transactionReceived($s, $order);
		elseif($s->isRefunded())
			$this->_transactionRefunded($s, $order);
		else //uups
			$order->addStatusToHistory($order->getStatus(), " " . $s->getStatus() . " " . $s->getStatusReason());

		$order->save();
	}
	
	/**
	 * notification about status change for iDEAL
	 */
	public function notificationIdealAction()
	{
	    // get response
	    $response = $this->getRequest()->getParams();	
		$orderId = $response['orderId'];
		
		// get order
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($orderId);
		$paymentObj = $order->getPayment()->getMethodInstance();		
		$payment = $order->getPayment();
		
		// get post
		$post = $this->getRequest()->getPost();
		list($userid, $projectid) = explode(':', Mage::getStoreConfig('payment/sofort_ideal/configkey'));
		// load transaction Data
		$transData = new SofortLib_ClassicNotification($userid, $projectid, Mage::getStoreConfig('payment/sofort_ideal/notification_password'));
	    $transData->getNotification($post);
	    
	    // hash not matched
	    if($transData->isError()){
	        Mage::log('Notification invalid: '.__CLASS__ . ' ' . __LINE__ );
	        return;
	    }
	    if($payment->getAdditionalInformation('sofort_transaction')){
	        // wrong transaction id
	        if($payment->getAdditionalInformation('sofort_transaction') != $transData->getTransaction()){
	            Mage::log('Notification invalid: '.__CLASS__ . ' ' . __LINE__ );
	            return;
	        } 	        
	    }else{
	        // store transaction
	        $payment->setAdditionalInformation('sofort_transaction',$transData->getTransaction());
	        $payment->save();
	    }
	    
	    // check if something change
		if( $payment->getAdditionalInformation('sofort_lastchanged') === $this->_getLastChanged($transData) ) {
		    return;
		}

		$payment->setAdditionalInformation('sofort_lastchanged', $this->_getLastChanged($transData))->save();
		
		/*
		 * payment was receiced
		 * - mark as pay
		 * - update order status
		 * - make visible frontend
		 * - send customer email
		 */
		if($transData->getStatus() =='received'){
		    $payment->setStatus(Paymentnetwork_Pnsofortueberweisung_Model_Pnsofortueberweisung::STATUS_SUCCESS);
		    $payment->save();
		    	    
    		$order->setPayment($payment);
    		$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING);			
    		$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Payment was successful.', $transData->getTransaction()), $paymentObj->getConfigData('order_status'))
    					->setIsVisibleOnFront(true);
    		
            // send email to customer if not send already					
    		if(!$order->getEmailSent()) {
    			$order->save();
    			$order->sendNewOrderEmail();
    			$order->setIsCustomerNotified(true);
    		}
			$order->save();
			
		}
		/*
		 * pending payment
		 * - just save transaction id before
		 */
	    if($transData->getStatus() =='pending'){
		    $order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Waiting for money'));
			$order->save();
		}
		/*
		 * transaction is loss to various reasons
		 * - cancel order 
		 * - make visible frontend
		 */
	    if($transData->getStatus() =='loss'){
		    $order->cancel();
			$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Customer canceled payment'))->setIsVisibleOnFront(true);
			$order->save();
		}
	    
	}
	
	/**
	 * edit order if something changed
	 * 
	 * @param SofortLib_TransactionData $s
	 * @param Mage_Sales_Model_Order $order
	 * @return boolean
	 */
	private function _beEdit($s, $order) {
	    
	    // total amount
	    $amount		= number_format($order->getGrandTotal(),2,'.','');
	    
	    // if amount still the same, there was nothing edit
	    if($amount == $s->getAmount()){
	        return false;
	    }
	    
	    // update total
	    $order->setGrandTotal($s->getAmount());
	    $order->setBaseGrandTotal($s->getAmount());
	    $subTotal = 0;
	    $taxAmount = array();
	    
	    // store items get from remote
	    $checkItems = array();
	    foreach($s->getItems() as $item){
	        $checkItems[$item['item_id']] = $item;
	    }
	    
	    // check all items in the current order
	    foreach($order->getAllVisibleItems() as $item) {
	        $uid = md5($item->getSku()."-".$item->getItemId());
	        
	        // if not exist it should removed
	        if(empty($checkItems[$uid])){
	            // item was cancel
	            $item->delete();

	            unset($checkItems[$uid]);
	            continue;
	        }
	        // quantity change, new row values will be calculated
	        if($checkItems[$uid]['quantity'] != $item->getQtyOrdered()){
	            $item->setQtyCanceled($item->getQtyOrdered() - $checkItems[$uid]['quantity']);	            
	             
                $item->setRowTotalInclTax( $checkItems[$uid]['quantity'] * $checkItems[$uid]['unit_price'] );
                $item->setBaseRowTotalInclTax( $checkItems[$uid]['quantity'] * $checkItems[$uid]['unit_price'] );
                
                $item->setRowTotal( $item->getPrice() * $checkItems[$uid]['quantity'] );
	            $item->setBaseRowTotal( $item->getPrice() * $checkItems[$uid]['quantity'] );
	            
	            $item->setTaxAmount( $item->getRowTotalInclTax() - $item->getRowTotal() );
	            $item->setBaseTaxAmount( $item->getBaseRowTotalInclTax() - $item->getBaseRowTotal() );
	        }
	        $subTotal += $checkItems[$uid]['quantity'] * $checkItems[$uid]['unit_price'];
	        // appent to tax group
	        if(empty($taxAmount[$checkItems[$uid]['tax']])) {
	            $taxAmount[$checkItems[$uid]['tax']] = 0;
	        } 
	        $taxAmount[$checkItems[$uid]['tax']] += $item->getRowTotalInclTax();
	        
	        unset($checkItems[$uid]);
	    }
	    
	    // edit shipment amount if it was removed
	    if(empty($checkItems[1]) && $order->getShippingAmount()){
	        $order->setShippingAmount(0);
	        $order->setBaseShippingAmount(0);
	        $order->setShippingTaxAmount(0);
	        $order->setBaseShippingTaxAmount(0);
	        $order->setShippingInclTax(0);
	        $order->setBaseShippingInclTax(0);
	    }
	    
	    // fix tax from discount and shipping
	    foreach($checkItems as $item) {
	        if(empty($taxAmount[$item['tax']])) {
	            $taxAmount[$item['tax']] = 0;
	        } 
	        $taxAmount[$item['tax']] += ($item['unit_price'] * $item['quantity']);
	    }
	    
	    // update subtotal
	    $order->setBaseSubtotalInclTax($subTotal);
	    $order->setSubtotalInclTax($subTotal);
	    
	    // sum for all tax amount
	    $totalTaxAmount = 0;
	    
	    // update all tax rate items
	    $rates = Mage::getModel('tax/sales_order_tax')->getCollection()->loadByOrder($order);
	    foreach($rates as $rate){
	        // format rate
	        $tRate = sprintf("%01.2f", $rate->getPercent());
	        if(!empty($taxAmount[$tRate])){
	            // calc new tax value
	            $tAmount = $taxAmount[$tRate] - ($taxAmount[$tRate] * (100 / ($tRate+100)) );
	            $totalTaxAmount += $tAmount;
	            $rate->setAmount($tAmount);
	            $rate->setBaseAmount($tAmount);
	            $rate->setBaseRealAmount($tAmount);
	            $rate->save();
	        }
	    }
	    
	    // update total tax amount
	    $order->setTaxAmount($totalTaxAmount);
	    $order->setBaseTaxAmount($totalTaxAmount);
	    
	    // update subtotal without tax
	    $order->setBaseSubtotal( $subTotal - $totalTaxAmount + $order->getShippingTaxAmount() );
	    $order->setSubtotal($subTotal - $totalTaxAmount + $order->getShippingTaxAmount() );
	    
	    $order->save();
	    
	    return true;
	}
	
	/**
	 * execute if transaction was loss
	 * 
	 * @param SofortLib_TransactionData $s
	 * @param Mage_Sales_Model_Order $order
	 * @return void
	 */
	private function _transactionLoss($s, $order) {
		$payment = $order->getPayment();
		
		if($s->isSofortlastschrift() || $s->isLastschrift()) {
			//$order->cancel();
			$payment->setParentTransactionId($s->getTransaction())
				->setShouldCloseParentTransaction(true)
				->setIsTransactionClosed(0)
				->registerRefundNotification($s->getAmount());			

			$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Customer returned payment'))->setIsVisibleOnFront(true);
			$order->save();
		} elseif($s->isSofortrechnung()) {
			$order->cancel();
			$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Successfully canceled invoice: %s', $s->getTransaction()))->setIsVisibleOnFront(true);
		} else {
			$order->cancel();
			$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Customer canceled payment'))->setIsVisibleOnFront(true);
		}
		$order->save();
	}
	
	/**
	 * unconfirmed transaction
	 * 
	 * @param SofortLib_TransactionData $s
	 * @param Mage_Sales_Model_Order $order
	 * @return void
	 */
	private function _transactionUnconfirmed($s, $order) {
		$payment = $order->getPayment();
		$transaction = $s->getTransaction();
		$statusReason = $s->getStatusReason();
		
		if ($s->isPending() && $s->isSofortvorkasse() ) {
			$order->setState('sofort');
			$order->addStatusToHistory($order->getStatus(), Mage::helper('pnsofortueberweisung')->__('Waiting for money'), true);
			$order->sendNewOrderEmail();
		} elseif ($s->isPending() && $s->isSofortrechnung() && $statusReason == 'confirm_invoice') {
			$order->setState('sofort');

			//customer may have changed the address during payment process
			$address = $s->getInvoiceAddress();
			$order->getBillingAddress()
				->setStreet($address['street'] . ' ' . $address['street_number'])
				->setFirstname($address['firstname'])
				->setLastname($address['lastname'])
				->setPostcode($address['zipcode'])
				->setCity($address['city'])
				->setCountryId($address['country_code']);

			$address = $s->getShippingAddress();
			$order->getShippingAddress()
				->setStreet($address['street'] . ' ' . $address['street_number'])
				->setFirstname($address['firstname'])
				->setLastname($address['lastname'])
				->setPostcode($address['zipcode'])
				->setCity($address['city'])
				->setCountryId($address['country_code']);

			$order->save();
			
			$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Payment successfull. Invoice needs to be confirmed.', $transaction))
					->setIsVisibleOnFront(true)
					->setIsCustomerNotified(true);
					
			$order->sendNewOrderEmail();
		}
		$order->save();	
	}
	
	/**
	 * execute if transaction was confirmed
	 * 
	 * @param SofortLib_TransactionData $s
	 * @param Mage_Sales_Model_Order $order
	 * @return void
	 */
	private function _transactionConfirmed($s, $order) {
		$payment = $order->getPayment();
		$paymentObj = $order->getPayment()->getMethodInstance();
		$amount = $s->getAmount();
		$currency = $s->getCurrency();
		$statusReason = $s->getStatusReason();
		$transaction = $s->getTransaction();
		
		if($s->isReceived() && $s->isSofortvorkasse()) {
			$notifyCustomer = false;
		} elseif($s->isSofortrechnung() && $statusReason == 'not_credited_yet' && $s->getInvoiceStatus() == 'pending') { 
			$notifyCustomer = false;
			$invoice = array(
							'number' => $s->getInvoiceNumber(),
							'bank_holder' => $s->getInvoiceBankHolder(),
							'bank_account_number' => $s->getInvoiceBankAccountNumber(),
							'bank_code' => $s->getInvoiceBankCode(),
							'bank_name' => $s->getInvoiceBankName(),
							'reason' => $s->getInvoiceReason(1). ' '.$s->getInvoiceReason(2),
							'date' => $s->getInvoiceDate(),
							'due_date' => $s->getInvoiceDueDate(),
							'debitor_text' => $s->getInvoiceDebitorText()
			);
			$order->getPayment()->setAdditionalInformation('sofort_invoice', serialize($invoice));
		} elseif($s->isSofortrechnung()) {
			return;		
		} else { 
			$notifyCustomer = true;
		}
		
			
		$payment->setStatus(Paymentnetwork_Pnsofortueberweisung_Model_Pnsofortueberweisung::STATUS_SUCCESS);
		$payment->setStatusDescription(Mage::helper('pnsofortueberweisung')->__('Payment was successful.', $transaction));
		$order->setPayment($payment);

		
		if($order->getPayment()->canCapture() && $order->canInvoice()) {
			$payment->setTransactionId($transaction)
					->setIsTransactionClosed(0)
					->registerCaptureNotification($amount);
		} elseif(method_exists($payment, 'addTransaction')) {  //transaction overview in magento > 1.5
			$payment->setTransactionId($transaction)
					->setIsTransactionClosed(0)
					->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE); 
		}

		$order->setPayment($payment);
		$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING);			
		$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Payment was successful.', $transaction), $paymentObj->getConfigData('order_status'))
					->setIsVisibleOnFront(true)
					->setIsCustomerNotified($notifyCustomer);
		
        // FIX BUG to send multible mails to customer					
		if($notifyCustomer && !$order->getEmailSent()) {
			$order->save();
			$order->sendNewOrderEmail();
		}
		
		$order->save();
	}
	
	/**
	 * execute if transaction was received
	 * 
	 * @param SofortLib_TransactionData $s
	 * @param Mage_Sales_Model_Order $order
	 * @return void
	 */
	private function _transactionReceived($s, $order) {
		$payment = $order->getPayment();
		if($s->isReceived() && ($s->isSofortrechnung() || $s->isLastschrift() || $s->isSofortlastschrift()) ) {
			//don't do anything
			//$order->addStatusToHistory($order->getStatus(), Mage::helper('pnsofortueberweisung')->__('Money received.'));
		} elseif($s->isReceived()) { // su,sl,ls
			$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('Money received.'))->setIsVisibleOnFront(false);
		}
		
		$order->save();
	}
	
	/**
	 * execute if transaction was refunded
	 * 
	 * @param SofortLib_TransactionData $s
	 * @param Mage_Sales_Model_Order $order
	 * @return void
	 */
	private function _transactionRefunded($s, $order) {
		$payment = $order->getPayment();

		if(!$payment->getTransaction($s->getTransaction().'-refund')) {
			$payment->setParentTransactionId($s->getTransaction())
				->setShouldCloseParentTransaction(true)
				->setIsTransactionClosed(0)
				->registerRefundNotification($s->getAmountRefunded());

			$order->addStatusHistoryComment(Mage::helper('pnsofortueberweisung')->__('The invoice has been canceled.'))->setIsVisibleOnFront(true);
			$order->save();
		}
	}
	
	/**
	 * generates hash of status
	 * 
	 * @param SofortLib_TransactionData $s
	 */
	private function _getLastChanged($s) {
		return sha1($s->getStatus() . $s->getStatusReason());
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

	public function indexAction(){
	    echo "Hello World";
	}
	
}