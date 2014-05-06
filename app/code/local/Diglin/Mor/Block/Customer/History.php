<?php
class Diglin_Mor_Block_Customer_History extends Mage_Core_Block_Template
{
	/**
	 * Must be defined before to the method _prepareLayout
	 * 
	 * @param $list
	 */
	public function setCallHistoryItems($list)
	{
		if ($list) {
			$list = new Varien_Object ( $list );
			if (count($list) > 0) {
				$callsCollection = new Varien_Data_Collection ();
				foreach ( $list->getCalls () as $call ) {
					
					$call = new Varien_Object ( $call );
					$call->setIdFieldName ( 'callid' );
					
					$callsCollection->addItem ( $call );
				}
				
				$this->setCollection ( $callsCollection );
			}
		}
		return $this;
	}
    
	/**
	 * Prepare the size of items to display per page
	 *
	 * @return Mage_Downloadable_Block_Customer_Products_List
	 */
	protected function _beforeToHtml()
	{
		
		$callsCollection = $this->getCollection ();
		if ($callsCollection) {
			$pager = $this->getLayout ()->createBlock ( 'page/html_pager', 'mor.customer.history.pager' )
			    ->setCollection ( $callsCollection );
			$this->setChild ( 'pager', $pager );
			
			$_items = $callsCollection->getItems ();
			
			$pageSize = $callsCollection->getPageSize ();
			$curPage = $callsCollection->getCurPage ();
			
			$range_end = ($curPage * $pageSize) - 1;
			$range_start = ($curPage * $pageSize) - $pageSize;
			$_items = array_slice ( $_items, $range_start, $range_end );
			
			$callsCollection->clear ();
			foreach ( $_items as $item ) {
				$callsCollection->addItem ( $item );
			}
			$this->setCollection ( $callsCollection );
		}
		return $this;
	}
	
	/**
     * @return string
     */
    public function getBackUrl()
    {
        if ($this->getRefererUrl()) {
            return $this->getRefererUrl();
        }
        return $this->getUrl('customer/account/');
    }
}