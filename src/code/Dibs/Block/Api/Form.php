<?php
/**
 * Form used to input credit card information, extends the built-in form
 * because things are already solved there
 *
 * @author jonathan@madepeople.se
 */
class Made_Dibs_Block_Api_Form extends Mage_Payment_Block_Form_Cc
{
    /**
     * Retrive has verification configuration
     *
     * @return boolean
     */
    public function hasVerification()
    {
        return true;
    }
}