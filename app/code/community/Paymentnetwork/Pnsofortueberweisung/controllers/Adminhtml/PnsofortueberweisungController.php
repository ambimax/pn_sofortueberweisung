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
 * @copyright  Copyright (c) 2008 [m]zentrale GbR, 2010 Payment Network AG, 2012 initOS GmbH & Co. KG
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version	$Id: PnsofortueberweisungController.php 3848 2012-04-18 07:59:25Z dehn $
 */
class Paymentnetwork_Pnsofortueberweisung_Adminhtml_PnsofortueberweisungController extends Mage_Adminhtml_Controller_Action
{

    /**
     * basic setup
     * 
     */
	protected function _initAction() {
		$this->loadLayout()
			->_setActiveMenu('pnsofortueberweisung/items')
			->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));
		
		return $this;
	}
 
	/**
	 * basic grid view to show order with sofort rechnung
	 * 
	 */
	public function indexAction() {
		$this->_initAction();
		$this->renderLayout();
	}
	
	/**
	 * view to edit items of order
	 * 
	 */
	public function editAction() {
	    $this->_initAction();
	    $order = $this->_initOrder();
	    
	    $sofortRechnung = Mage::getModel('pnsofortueberweisung/sofortrechnung');
	    // create object which was send to remote service
	    $invoice = $sofortRechnung->createPaymentFromOrder($order);
	    
	    // assign to block
	    $block = $this->getLayout()->getBlock('pnsofortueberweisung_edit');
	    $block->assign('order', $order);
	    $block->assign('invoice', $invoice);
	    
	    $this->renderLayout();
	}
	
	/**
	 * handle request to update invoice items and send request to remote service
	 * 
	 */
	public function postAction() {
	    $order = $this->_initOrder();
	    $params = $this->getRequest()->getParams();
		$session = Mage::getSingleton('adminhtml/session');
		
		// check current request
		if(!empty($params)){
		    $sofortRechnung = Mage::getModel('pnsofortueberweisung/sofortrechnung');
		    // generate old send parameters
	        $invoice = $sofortRechnung->createPaymentFromOrder($order);
	        // get old items
	        $items = $invoice->getSofortrechnungItems();
	        // new item array
	        $newItems = array();
	        // mark change
	        $change = false;
	        // check all old items
	        foreach ($items['item'] as $line) {
	            // increase quantity not allowed
	            if($line['quantity'] < $params['line'][$line['item_id']]) {
	                $this->_getSession()->addError($this->__('quantity increase is not allowed'));
	                $this->_redirect('*/*/edit',array('order_id'=> $order->getId()));
                    return false;
	            }
	            
	            // only quantity change
	            if($line['quantity'] != $params['line'][$line['item_id']]) {
	                $change = true;
	                $line['quantity'] = $params['line'][$line['item_id']];
	            }
	            
	            // if quantity is greater as null
	            if( !empty($line['quantity'])) {
	                $newItems[] = $line;
	            }
	            
	        }
	        
	        // if something change we send the request
	        if($change){
	            try{
	                $sofortRechnung->updateInvoice( $order->getPayment(), $newItems, $params['comment']);
	            }catch (Exception $e){
	                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
	                $this->_redirect('*/*/edit',array('order_id' => $order->getId()));
	                return false;
	            }
	        }
	        
	        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('pnsofortueberweisung')->__('Item was successfully saved'));
	        
		}
	    
	    $this->_redirect('*/*');
	    return false;
	    
	}
	
	/**
	 * initialise current order from request parameter
	 * 
	 * @return Mage_Sales_Model_Order
	 */
    protected function _initOrder()
    {
        $id = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($id);

        if (!$order->getId()) {
            $this->_getSession()->addError($this->__('This order no longer exists.'));
            $this->_redirect('*/*/');
            return false;
        }
        Mage::register('sales_order', $order);
        Mage::register('current_order', $order);
        return $order;
    }
	
	public function saveConfigAction() {
		$params = $this->getRequest()->getParams();
		$session = Mage::getSingleton('adminhtml/session');
		if($this->getRequest()->getParams()){
			$groups = Array();
			$groups['pnsofortueberweisung']['fields']['customer']['value'] = $params["user_id"];
			$groups['pnsofortueberweisung']['fields']['project']['value']  = $params["project_id"];
			$groups['pnsofortueberweisung']['fields']['check_input_yesno']['value'] = 1;
			$groups['pnsofortueberweisung']['fields']['active']['value'] = 1;
			$groups['pnsofortueberweisung']['fields']['project_pswd']['value'] = $session->getData('projectssetting_project_password');
			$session->unsetData('projectssetting_project_password');
			$groups['pnsofortueberweisung']['fields']['notification_pswd']['value'] = $session->getData('project_notification_password');
			$session->unsetData('project_notification_password');
			
		
			try {
				Mage::getModel('adminhtml/config_data')
					->setSection('payment')
					->setWebsite($this->getRequest()->getParam('website'))
					->setStore($this->getRequest()->getParam('store'))
					->setGroups($groups)
					->save();
			}catch (Mage_Core_Exception $e) {
				foreach(split("\n", $e->getMessage()) as $message) {
					$session->addError($message);
				}
			}
			catch (Exception $e) {
				$session->addException($e, Mage::helper('adminhtml')->__('Error while saving this configuration: '.$e->getMessage()));
			}
			
			Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('pnsofortueberweisung')->__('Item was successfully saved'));
			Mage::getSingleton('adminhtml/session')->setFormData(false);
		}

		
		$this->_redirect('adminhtml/system_config/edit', array('section'=>'payment'));
		return;
	}
	
	public function saveConfigPcAction() {

		$params = $this->getRequest()->getParams();
		$session = Mage::getSingleton('adminhtml/session');
		if($this->getRequest()->getParams()){
			$groups = Array();
			$groups['paycode']['fields']['customer']['value'] = $params["user_id"];
			$groups['paycode']['fields']['project']['value']  = $params["project_id"];
			$groups['paycode']['fields']['check_input_yesno']['value'] = 1;
			
			#echo "<pre>";
			#print_r($groups);
			#echo "</pre>";
			
			try {
				Mage::getModel('adminhtml/config_data')
					->setSection('payment')
					->setWebsite($this->getRequest()->getParam('website'))
					->setStore($this->getRequest()->getParam('store'))
					->setGroups($groups)
					->save();
			}catch (Mage_Core_Exception $e) {
				foreach(split("\n", $e->getMessage()) as $message) {
					$session->addError($message);
				}
			}
			catch (Exception $e) {
				$session->addException($e, Mage::helper('adminhtml')->__('Error while saving this configuration: '.$e->getMessage()));
			}
			
			Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('pnsofortueberweisung')->__('Item was successfully saved'));
			Mage::getSingleton('adminhtml/session')->setFormData(false);
		}
		#echo "aha";
		#exit;
		$this->_redirectUrl('/index.php/admin/system_config/edit/section/payment');
		return;
	}
}