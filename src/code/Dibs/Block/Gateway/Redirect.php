<?php

/**
 * Block that renders an auto-submitting DIBS form which redirects the
 * customer to the DIBS gateway where the purchase happens
 *
 * @author jonathan@madepeople.se
 */
class Made_Dibs_Block_Gateway_Redirect extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $gateway = Mage::getModel('made_dibs/payment_gateway');

        $form = new Varien_Data_Form();
        $form->setAction(Made_Dibs_Model_Payment_Gateway::PAYMENTWINDOW_URL)
            ->setId('made_dibs_gateway')
            ->setName('made_dibs_gateway')
            ->setMethod('POST')
            ->setUseContainer(true);

        foreach ($gateway->getCheckoutFormFields()->toArray() as $field => $value) {
            $form->addField($field, 'hidden', array(
                'name' => $field,
                'value' => $value
            ));
        }

        $idSuffix = Mage::helper('core')->uniqHash();
        $submitButton = new Varien_Data_Form_Element_Submit(array(
            'value' => $this->__('Click here if you are not redirected within 10 seconds.'),
        ));
        $id = "submit_to_dibs_button_{$idSuffix}";
        $submitButton->setId($id);
        $form->addElement($submitButton);
        $html = '<html><body>';
        $html .= $this->__('You will be redirected to the DIBS website in a few seconds.');
        $html .= $form->toHtml();
        $html .= '<script type="text/javascript">document.getElementById("made_dibs_gateway").submit();</script>';
        $html .= '</body></html>';

        return $html;
    }
}
