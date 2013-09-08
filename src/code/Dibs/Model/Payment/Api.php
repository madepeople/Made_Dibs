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