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
 * @version	$Id: Grid.php 3844 2012-04-18 07:37:02Z dehn $
 */
class Paymentnetwork_Pnsofortueberweisung_Block_Adminhtml_Pnsofortueberweisung_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
  
  public function __construct()
  {
	  parent::__construct();
	  $this->setId('pnsofortueberweisungGrid');
	  $this->setDefaultSort('real_order_id');
	  $this->setDefaultDir('DESC');
	  $this->setSaveParametersInSession(true);
  }

  protected function _prepareCollection()
  {
      $collection = Mage::getResourceModel('sales/order_grid_collection');
      $resource = Mage::getSingleton('core/resource');

      $collection->addFilter('method','sofortrechnung');
	  $collection->getSelect()->join(
            array(
                'flat_payment' => $resource->getTableName('sales/order_payment')),
                'parent_id=main_table.entity_id',
                array('payment_method' => 'method', 'additional_information' => 'additional_information'
            )
        );
      
      $this->setCollection($collection);
	  
	  return parent::_prepareCollection();
  }

  protected function _prepareColumns()
  {
       $this->addColumn('real_order_id', array(
            'header'=> Mage::helper('sales')->__('Order #'),
            'width' => '80px',
            'type'  => 'text',
            'index' => 'increment_id',
        ));
        
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'    => Mage::helper('sales')->__('Purchased From (Store)'),
                'index'     => 'store_id',
                'type'      => 'store',
                'store_view'=> true,
                'display_deleted' => true,
            ));
        }

        $this->addColumn('status', array(
            'header' => Mage::helper('sales')->__('Status'),
            'index' => 'status',
            'type'  => 'options',
            'width' => '120px',
            'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
        ));
        
        $this->addColumn('created_at', array(
            'header' => Mage::helper('sales')->__('Purchased On'),
            'index' => 'created_at',
            'type' => 'datetime',
            'width' => '100px',
        ));

        $this->addColumn('billing_name', array(
            'header' => Mage::helper('sales')->__('Bill to Name'),
            'index' => 'billing_name',
        ));

        $this->addColumn('additional_information', array(
            'header' => Mage::helper('pnsofortueberweisung')->__('Transaction Id'),
            'index'  => 'additional_information',
            'renderer' => 'Paymentnetwork_Pnsofortueberweisung_Block_Adminhtml_Pnsofortueberweisung_Renderer_Transaction',
        ));    

        $this->addColumn('grand_total', array(
            'header' => Mage::helper('sales')->__('G.T. (Purchased)'),
            'index' => 'grand_total',
            'type'  => 'currency',
            'currency' => 'order_currency_code',
        ));

        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
		    $this->addColumn('action',
                array(
                    'header'    => Mage::helper('sales')->__('Action'),
                    'width'     => '90px',
                    'type'      => 'action',
                    'getter'     => 'getId',
                    'actions'   => array(
                        array(
                            'caption' => Mage::helper('sales')->__('View'),
                            'url'     => array('base'=>'adminhtml/sales_order/view'),
                            'field'   => 'order_id'
                        ),
                        array(
                            'caption' => Mage::helper('pnsofortueberweisung')->__('Edit Cart'),
                            'url'     => array('base'=>'*/*/edit'),
                            'field'   => 'order_id'
                        ),
                    ),
                    'filter'    => false,
                    'sortable'  => false,
                    'index'     => 'stores',
                    'is_system' => true,
            ));
        }
		
  
	  return parent::_prepareColumns();
  }

  public function getRowUrl($row)
  {
      if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
          return $this->getUrl('adminhtml/sales_order/view', array('order_id' => $row->getId()));
      }
	  return false;
  }

}