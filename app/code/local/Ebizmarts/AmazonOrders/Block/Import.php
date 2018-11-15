<?php

class Ebizmarts_AmazonOrders_Block_Import extends Mage_Core_Block_Template
{

	public function getWebsitesOptionHtml()
    {
        $storeModel = Mage::getSingleton('adminhtml/system_store');
        /* @var $storeModel Mage_Adminhtml_Model_System_Store */
        $websiteCollection = $storeModel->getWebsiteCollection();
        $groupCollection = $storeModel->getGroupCollection();
        $storeCollection = $storeModel->getStoreCollection();

        $html  = '<select name="store" id="store" >';

        foreach ($websiteCollection as $website) {
            $websiteShow = false;
            foreach ($groupCollection as $group) {
                if ($group->getWebsiteId() != $website->getId()) {
                    continue;
                }
                $groupShow = false;
                foreach ($storeCollection as $store) {
                    if ($store->getGroupId() != $group->getId()) {
                        continue;
                    }
                    if (!$websiteShow) {
                        $websiteShow = true;
                        $html .= '<optgroup label="' . $website->getName() . '"></optgroup>';
                    }
                    if (!$groupShow) {
                        $groupShow = true;
                        $html .= '<optgroup label="&nbsp;&nbsp;&nbsp;&nbsp;' . $group->getName() . '">';
                    }
                    $html .= '<option value="' . $store->getId() . '">&nbsp;&nbsp;&nbsp;&nbsp;' . $store->getName() . '</option>';

                }
                if ($groupShow) {
                    $html .= '</optgroup>';
                }
            }
        }
        $html .= '</select>';
        return $html;
    }

}
?>