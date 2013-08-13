<?php
/**
 * @author jonathan@madepeople.se
 */
class Made_Dibs_Model_Observer
{
    /**
     * We use an observer to set the order to a payment pending state, making
     * sure that we follow the Magento payment flow
     *
     * @param Varien_Event_Observer $observer
     */
    public function setTransactionToPendingGateway(Varien_Event_Observer $observer)
    {
        $payment = $observer->getEvent()
                ->getOrder()
                ->getPayment();

        if (!($payment->getMethodInstance() instanceof Made_Dibs_Model_Payment_Abstract)) {
            return;
        }

        $payment->setIsTransactionPending(true);
    }
}