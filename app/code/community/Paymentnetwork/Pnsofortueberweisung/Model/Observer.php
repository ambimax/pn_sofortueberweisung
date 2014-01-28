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
 * @version	$Id: Observer.php 3844 2012-04-18 07:37:02Z dehn $
 */
require_once Mage::getModuleDir('', 'Paymentnetwork_Pnsofortueberweisung').'/Helper/library/sofortLib.php';
class Paymentnetwork_Pnsofortueberweisung_Model_Observer extends Mage_Core_Model_Abstract {

	/*
	 * Pay-Event
	 * we will confirm the invoice with this method
	 */
	public function sales_order_invoice_pay($observer) {
		// get invoice
		$invoice = $observer->getEvent()->getInvoice();
		$order = $invoice->getOrder();
		$payment = $order->getPayment();
		$addinfo = $payment->getAdditionalInformation();
		$invoices = $invoice->getOrder()->hasInvoices();
		$method = $payment->getMethod();
		
		if($method != 'sofortrechnung' )
			return $this;

		$tid = $payment->getAdditionalInformation('sofort_transaction');
		if(!empty($tid)) {
			$sObj = new SofortLib_ConfirmSr(Mage::getStoreConfig('payment/sofort/configkey'));
			
			//$itemkeys = array();
			//if(isset($_REQUEST['invoice']['items']))
			//	$itemkeys = array_keys($_REQUEST['invoice']['items']);

			//foreach($invoice->getAllItems() as $item) {
			//	if(in_array($item->getOrderItemId(), $itemkeys))
			//			$items[] = $item;
			//}
			//foreach ($this->getAllItems() as $item) {
			//	$sObj->addItem($item->getSku(), $item->getSku(), $item->getName(), $item->getPriceInclTax(), 0, $item->getDescription(), $item->getQtyOrdered(), $item->getTaxPercent());
			//}	
			//$sObj->addItem(1, 1, $order->getShippingDescription(), $order->getShippingInclTax(), 1, '', 1, round($order->getShippingTaxAmount()/$order->getShippingAmount()*100));
			

			$sObj->confirmInvoice($tid)->sendRequest();
			if($sObj->isError()) {
				Mage::throwException($sObj->getError());
			} else {
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('pnsofortueberweisung')->__('Successfully confirmed invoice: %s', $tid));
				$invoice->setTransactionId($tid);
				$payment->setAdditionalInformation("sofortrechnung_invoice_url", $sObj->getInvoiceUrl());
				$payment->save();
				return $this;
			}		
		}
		
		Mage::getSingleton('adminhtml/session')->addError(Mage::helper('pnsofortueberweisung')->__('Could not confirm invoice.'));
		return $this;
	}
	
	public function sales_order_payment_cancel($observer) {
		//get payment
		$payment = $observer->getEvent()->getPayment();
		$method = $payment->getMethod();
		
		if($method != 'sofortrechnung' )
			return $this;

		$tid = $payment->getAdditionalInformation('sofort_transaction');
		if(!empty($tid)) {
			$transaction = new SofortLib_TransactionData(Mage::getStoreConfig('payment/sofort/configkey'));
			$transaction->setTransaction($tid)->sendRequest();
			if ($transaction->isSofortrechnung() && !$transaction->isLoss()) {
				$cancel = new SofortLib_ConfirmSr(Mage::getStoreConfig('payment/sofort/configkey'));
	
				$cancel->cancelInvoice($tid)->setComment('refund')->sendRequest();
				if($cancel->isError()) {
					Mage::throwException($cancel->getError());
				} else {
					Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('pnsofortueberweisung')->__('Successfully canceled invoice: %s', $tid));
					return;
				}	
			}	
		}
		
		Mage::getSingleton('adminhtml/session')->addError(Mage::helper('pnsofortueberweisung')->__('Could not cancel invoice.'));
		return $this;
				
	}
	
	public function sales_order_payment_refund($observer) {
		$payment = $observer->getEvent()->getPayment();
		$creditmemo = $observer->getEvent()->getCreditmemo();
		$method = $payment->getMethod();
		
		return $this;
	}
}
