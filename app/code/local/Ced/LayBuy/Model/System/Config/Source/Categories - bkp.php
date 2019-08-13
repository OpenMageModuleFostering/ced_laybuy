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
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Config category source
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Ced_LayBuy_Model_System_Config_Source_Categories
{
    // public function toOptionArray($addEmpty = true)
    // {
        // $tree = Mage::getResourceModel('catalog/category_tree');

        // $collection = Mage::getResourceModel('catalog/category_collection');

        // $collection->addAttributeToSelect('name')
            // /* ->addRootLevelFilter() */
            // ->load();

        // $options = array();

        // /* if ($addEmpty) {
            // $options[] = array(
                // 'label' => Mage::helper('adminhtml')->__('-- Please Select a Category --'),
                // 'value' => ''
            // );
        // } */
        // foreach ($collection as $category) {
            // $options []= array(
               // 'label' => $category->getName(),
               // 'value' => $category->getId()
            // );
        // }

        // return $options;
    // }
	
	public function toOptionArray($addEmpty = true)
    {
        $options = array();
        foreach ($this->load_tree() as $category) {
          /*   $options[$category['value']] =  $category['label']; */
			$options[] = array(
               'label' => $category['label'],
               'value' => $category['value']
            );
        }

        return $options;
    }  
    
    
    
    public function buildCategoriesMultiselectValues(Varien_Data_Tree_Node $node, $values, $level = 0)
    {
    	$level++;
		if($node->getId() != '1')
		{
			$values[$node->getId()]['value'] =  $node->getId();
			if($node->getLevel() < '2'){
				$values[$node->getId()]['label'] = $node->getName();
			}else{
				$values[$node->getId()]['label'] = str_repeat(" - ", (($node->getLevel())-1)) . $node->getName();
			}
		}
    
    	foreach ($node->getChildren() as $child)
    	{
    		$values = $this->buildCategoriesMultiselectValues($child, $values, $level);
    	}
    
    	return $values;
    }
    
    public function load_tree()
    {
    	$store = Mage::app()->getFrontController()->getRequest()->getParam('store', 0);
    	$parentId = $store ? Mage::app()->getStore($store)->getRootCategoryId() : 1;  // Current store root category
    	
    	$tree = Mage::getResourceSingleton('catalog/category_tree')->load();
    
    	$root = $tree->getNodeById($parentId);
    
    	if($root && $root->getId() == 1)
    	{
    		$root->setName(Mage::helper('catalog')->__('Root'));
    	}
    
    	$collection = Mage::getModel('catalog/category')->getCollection()
    	->setStoreId($store)
    	->addAttributeToSelect('name')
    	->addAttributeToSelect('is_active');
    
    	$tree->addCollectionData($collection, true);
    
    	return $this->buildCategoriesMultiselectValues($root, array());
    }
}
