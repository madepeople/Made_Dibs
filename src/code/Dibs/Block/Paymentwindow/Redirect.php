<?php

/**
 * Block that renders an auto-submitting DIBS form which redirects the
 * customer to the DIBS gateway where the purchase happens
 *
 * @author jonathan@madepeople.se
 */
class Made_Dibs_Block_Paymentwindow_Redirect extends Mage_Core_Block_Abstract
{

    protected function _toHtml()
    {
        $dibsPaymentWindow = Mage::getModel('made_dibs/payment_paymentwindow');

        $form = new Varien_Data_Form();
        $form->setAction(Made_Dibs_Model_Payment_Paymentwindow::PAYMENTWINDOW_URL)
                ->setId('made_dibs_paymentwindow')
                ->setName('made_dibs_paymentwindow')
                ->setMethod('POST')
                ->setUseContainer(true);

        foreach ($dibsPaymentWindow->getCheckoutFormFields()->toArray() as $field => $value) {
            $form->addField($field, 'hidden', array(
                'name' => $field,
                'value' => $value
            ));
        }

        $idSuffix = Mage::helper('core')->uniqHash();
        $submitButton = new Varien_Data_Form_Element_Submit(array(
            'value' => $this->__('Click here if you are not redirected within 10 seconds...'),
        ));
        $id = "submit_to_dibs_button_{$idSuffix}";
        $submitButton->setId($id);
        $form->addElement($submitButton);
        $html = '<html><body>';
        $html .= $this->__('You will be redirected to the DIBS website in a few seconds.');
        $html .= $form->toHtml();
        $html .= '<script type="text/javascript">document.getElementById("made_dibs_paymentwindow").submit();</script>';
        $html .= '</body></html>';

        return $html;
    }
}
