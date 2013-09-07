<?php
/**
 * Implementation of the Payment Window API solution (on gateway) that allows
 * for direct input of credit card information by customers
 *
 * @author jonathan@madepeople.se
 */
class Made_Dibs_Model_Payment_Api extends Made_Dibs_Model_Payment_Abstract
{
    protected $_code = 'made_dibs_api';
    protected $_formBlockType = 'made_dibs/api_form';

    protected $_canAuthorize = true;
    protected $_canManageRecurringProfiles = false;

    /**
     * Assign data to info model instance, which basically happens on
     * savePayment and before an order is placed, from the payment importData
     * thingy. If we don't do this, we won't have any values when the actual
     * order needs to be placed
     *
     * @see \Mage_Payment_Model_Method_Cc
     * @param mixed $data
     * @return Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info = $this->getInfoInstance();
        $info->setCcOwner($data->getCcOwner())
            ->setCcNumber($data->getCcNumber())
            ->setCcCid($data->getCcCid())
            ->setCcExpMonth($data->getCcExpMonth())
            ->setCcExpYear($data->getCcExpYear())
            ->setCcSsIssue($data->getCcSsIssue())
            ->setCcSsStartMonth($data->getCcSsStartMonth())
            ->setCcSsStartYear($data->getCcSsStartYear())
            ;
        return $this;
    }

    /**
     * Get config payment action url
     * Used to universalize payment actions when processing payment place
     *
     * @return string
     */
    public function getConfigPaymentAction()
    {
        return $this->getConfigData('payment_action');
    }
}