<?php
/**
 * Lay-Buys
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
 * @category    Ced
 * @package     Ced_LayBuy
 * @author 		Asheesh Singh<asheeshsingh@cedcoss.com>
 * @copyright   Copyright LAY-BUYS (2011). (http://lay-buys.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * LayBuy module observer
 *
 * @author 		Asheesh Singh<asheeshsingh@cedcoss.com>
 */

class Ced_LayBuy_Model_Observer
{
	
	/**
     * Add transaction data to info block
     *
     * @param Varien_Object $observer
     * @return Mage_Centinel_Model_Observer
     */
    public function paymentInfoBlockPrepareSpecificInformation($observer)
    {
        /* if ($observer->getEvent()->getBlock()->getIsSecureMode()) {
            return;
        } */

        $transaction = $observer->getEvent()->getTransaction();
        $transport = $observer->getEvent()->getTransport();
		
		foreach($transaction as $txn){
			$transport->setData($txn->getFieldLabel('Instalment Plan')."-".$txn->getId(),$txn->getReport());
		}
		
        return $this;
    }
	
	/**
     * Cancel the asscociated
     *
     * @param Varien_Object $observer
     * @return Mage_Centinel_Model_Observer
     */
	public function orderCancelAfter($observer){
		$order = $observer->getEvent()->getOrder();
		try{
			$model = Mage::getModel('laybuy/report')->loadByOrderId($order->getIncrementId());
			if($model && $model->getId() && Mage::app()->getFrontController()->getRequest()->getModuleName()!='laybuy'){
				$newStrReport = preg_replace('/Pending/i', 'Canceled', $model->getReport());
				/* Mage::helper('laybuy')->cancelTransaction($model); */
				Mage::log('Cancel observer called',null,'laybuy_success.log');
				if(Mage::helper('laybuy')->cancelPaypalProfile($model->getPaypalProfileId(),$model->getStoreId())){
						$model->setReport($newStrReport);
						$model->setStatus(-1);
						$model->save();
				}
				Mage::log('Success on orderCancelAfter!!{{'.$model->getId().'Date:'.date('Y-m-d h:i:s',time()).' }}',null,'laybuy_success.log');
			}
		}catch(Exception $e){
			Mage::log('Failure on orderCancelAfter!!{{Exception: '.$e->getMessage().'Date:'.date('Y-m-d h:i:s',time()).' }}',null,'laybuy_failure.log');
            Mage::logException($e);
		}
	}
	
	/**
     * Goes to http://lay-buys.com/report/ and fetches Instalment reports.
     * @return Mage_LayBuy_Model_Observer
     */
    public function fetchUpdates()
    {
        try {
            $reports = Mage::getModel('laybuy/report');
            /* @var $reports Mage_LayBuy_Model_Report */
            $credentials = $reports->getApiCredentials(true);
            foreach ($credentials as $config) {
                try {
                    $fetched = $reports->fetchAndSave($config);
					Mage::log('Success!! Cron response {{Total '.$fetched.' rows fetched at date:'.date('Y-m-d h:i:s',time()).' }}',null,'laybuy_cron.log');
                } catch (Exception $e) {
					Mage::log('Failure1!! Cron response {{Exception: '.$e->getMessage().'Date:'.date('Y-m-d h:i:s',time()).' }}',null,'laybuy_cron.log');
                    Mage::logException($e);
                }
            }
        } catch (Exception $e) {
			Mage::log('Failure2!! Cron response {{Exception: '.$e->getMessage().'Date:'.date('Y-m-d h:i:s',time()).' }}',null,'laybuy_cron.log');
            Mage::logException($e);
        }
    }
	
	/**
     * Chcek cron setup is available at server or not.
     */
	public function checkCron(){
		if(!Mage::getStoreConfig('paymnet/laybuy/cronenabled')){
			$config = Mage::getConfig();
			$config->setNode("paymnet/laybuy/cronenabled", 1);
		}
	}
}
