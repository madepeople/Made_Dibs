<?php

class Made_Dibs_Model_System_Config_Source_Calculationmethod
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'hmac', 'label' => Mage::helper('adminhtml')->__('HMAC')),
            array('value' => 'md6', 'label' => Mage::helper('adminhtml')->__('MD5')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'hmac' => Mage::helper('adminhtml')->__('HMAC'),
            'md5' => Mage::helper('adminhtml')->__('MD5'),
        );
    }
}