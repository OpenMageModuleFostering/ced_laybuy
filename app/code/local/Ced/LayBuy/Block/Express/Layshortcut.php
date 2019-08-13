<?php 
class Ced_LayBuy_Block_Express_Layshortcut extends Mage_Core_Block_Template
{
	const POSITION_BEFORE = 'before';
	const POSITION_AFTER = 'after';
	/**
	 * Check is "OR" label position before shortcut
	 *
	 * @return bool
	 */
	public function isOrPositionBefore()
	{
		return ($this->getIsInCatalogProduct() && !$this->getShowOrPosition())
		|| ($this->getShowOrPosition() && $this->getShowOrPosition() == self::POSITION_BEFORE);
	
	}
	
	/**
	 * Check is "OR" label position after shortcut
	 *
	 * @return bool
	 */
	public function isOrPositionAfter()
	{
		return (!$this->getIsInCatalogProduct() && !$this->getShowOrPosition())
		|| ($this->getShowOrPosition() && $this->getShowOrPosition() == self::POSITION_AFTER);
	}
}