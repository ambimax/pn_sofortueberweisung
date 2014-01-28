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
 * @version	$Id: Form.php 3844 2012-04-18 07:37:02Z dehn $
 */
class Paymentnetwork_Pnsofortueberweisung_Block_Adminhtml_Pnsofortueberweisung_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
	  $form = new Varien_Data_Form();
	  $this->setForm($form);
	  $fieldset = $form->addFieldset('pnsofortueberweisung_form', array('legend'=>Mage::helper('pnsofortueberweisung')->__('Item information')));
	 
	  $fieldset->addField('title', 'text', array(
		  'label'	 => Mage::helper('pnsofortueberweisung')->__('Title'),
		  'class'	 => 'required-entry',
		  'required'  => true,
		  'name'	  => 'title',
	  ));

	  $fieldset->addField('status', 'select', array(
		  'label'	 => Mage::helper('pnsofortueberweisung')->__('Status'),
		  'name'	  => 'status',
		  'values'	=> array(
			  array(
				  'value'	 => 1,
				  'label'	 => Mage::helper('pnsofortueberweisung')->__('Enabled'),
			  ),

			  array(
				  'value'	 => 2,
				  'label'	 => Mage::helper('pnsofortueberweisung')->__('Disabled'),
			  ),
		  ),
	  ));
	 
	  $fieldset->addField('content', 'editor', array(
		  'name'	  => 'content',
		  'label'	 => Mage::helper('pnsofortueberweisung')->__('Content'),
		  'title'	 => Mage::helper('pnsofortueberweisung')->__('Content'),
		  'style'	 => 'width:700px; height:500px;',
		  'wysiwyg'   => false,
		  'required'  => true,
	  ));
	 
	  if ( Mage::getSingleton('adminhtml/session')->getSofortueberweisungData() )
	  {
		  $form->setValues(Mage::getSingleton('adminhtml/session')->getSofortueberweisungData());
		  Mage::getSingleton('adminhtml/session')->setSofortueberweisungData(null);
	  } elseif ( Mage::registry('pnsofortueberweisung_data') ) {
		  $form->setValues(Mage::registry('pnsofortueberweisung_data')->getData());
	  }
	  return parent::_prepareForm();
  }
}