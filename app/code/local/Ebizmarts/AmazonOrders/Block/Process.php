<?php
/*
 * Module upgraded by Synapse India
 * Developer : puneet puneetk00@gmail.com
 */
class Ebizmarts_AmazonOrders_Block_Process extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $html = $this->getResult() . "|" .
        		$this->getExistentOrder() . "|" .
        		$this->getCustomerCreated() . "|";

        switch ($this->getResult()) {
        	case 'ERROR':
        		$html .= '<ul class="messages"><li class="error-msg">' . $this->getMessage() .
        					'</li></ul>';
        		break;
        	case 'SUCCESS':
        		$html .= '<ul class="messages"><li class="success-msg">' . $this->getMessage() .
        					'</li></ul>';
        		break;
        	case 'WARNING':
        		$html .= '<ul class="messages"><li class="warning-msg">' . $this->getMessage() .
        					'</li></ul>';
        		break;

        }
        return $html;
    }
}
