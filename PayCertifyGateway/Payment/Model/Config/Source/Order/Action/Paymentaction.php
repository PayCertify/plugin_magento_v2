<?php
namespace PayCertifyGateway\Payment\Model\Config\Source\Order\Action;
 /** * Order Status source model */
class Paymentaction 
{
    /**
     * @var string[] 
     */      public function toOptionArray(){
       return [ ['value' => 'authorize', 'label' => __('Authorize Only')], ['value' => 'authorize_capture', 'label' => __('Authorize and Capture')], ];
     }
}