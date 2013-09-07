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
    protected $_formBlockType = 'payment/form_cc';

    protected $_canAuthorize = true;
    protected $_canManageRecurringProfiles = false;
}