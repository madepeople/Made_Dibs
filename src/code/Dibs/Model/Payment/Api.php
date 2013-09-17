<?php
/**
 * Implementation of the Payment Window API solution (no gateway) that allows
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
}