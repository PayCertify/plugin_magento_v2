<?php
namespace PayCertifyGateway\Payment\Model;
class Payment extends \Magento\Payment\Model\Method\Cc 
{
    const CODE = 'paycertifygateway';
    protected $_code = self::CODE;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_isGateway = true;
    protected $_countryFactory;
    protected $cart = null;
    public function __construct( \Magento\Framework\Model\Context $context,
    \Magento\Framework\Registry $registry, 
    \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
    \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
    \Magento\Payment\Helper\Data $paymentData, 
    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, 
    \Magento\Payment\Model\Method\Logger $logger, 
    \Magento\Framework\Module\ModuleListInterface $moduleList,
    \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
    \Magento\Directory\Model\CountryFactory $countryFactory,
    \Magento\Checkout\Model\Cart $cart,
    array $data = array() 
   ) {
      parent::__construct( $context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $moduleList, $localeDate, null, null, $data );
        $this->cart = $cart; $this->_countryFactory = $countryFactory;
   }
   public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount) {
      try { 
       //check if payment has not been authorized then authorize it      if(is_null($payment->getParentTransactionId()))
	     { 
	        $this->authorize($payment, $amount);
	     }
	        //build array of all necessary details to pass to your Payment Gateway.. 
	     $request = [ 'CardCVV2' => $payment->getCcCid(), ‘CardNumber’ => $payment->getCcNumber(), ‘CardExpiryDate’ => $this->getCardExpiryDate($payment), ‘Amount’ => $amount, ‘Currency’ => $this->cart->getQuote()->getBaseCurrencyCode(), ];
	       //make API request to credit card processor. $response = $this->captureRequest($request); 
	       //Handle Response accordingly. //transaction is completed.
	       $payment->setTransactionId($response['tid']) ->setIsTransactionClosed(0);
	     } catch (\Exception $e) {
	       $this->debug($payment->getData(), $e->getMessage());
	     }   return $this;
		  } 
	public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount) { 
	  try { 
	  //build array of all necessary details to pass to your Payment Gateway..
	 $request = [ 'CardCVV2' => $payment->getCcCid(), ‘CardNumber’ => $payment->getCcNumber(), ‘CardExpiryDate’ => $this->getCardExpiryDate($payment), ‘Amount’ => $amount, ‘Currency’ => $this->cart->getQuote()->getBaseCurrencyCode(), ];
	 //check if payment has been authorized
	   $response = $this->authRequest($request);
	 } catch (\Exception $e) {
	  $this->debug($payment->getData(), $e->getMessage());
	 }
	 if(isset($response['tid']))
	 { // Successful auth request.
	  // Set the transaction id on the payment so the capture request knows auth has happened.
	      $payment->setTransactionId($response['tid']);
	      $payment->setParentTransactionId($response['tid']);
	 } 
	   //processing is not done yet.
	      $payment->setIsTransactionClosed(0);
	        return $this;
	}
	    /*This function is defined to set the Payment Action Type that is - - Authorize - Authorize and Capture Whatever has been set under Configuration of this Payment Method in Admin Panel, that will be fetched and set for this Payment Method by passing that into getConfigPaymentAction() function. */
	public function getConfigPaymentAction() {
	   return $this->getConfigData('payment_action');
	}
	public function authRequest($request) {
	   //Process Request and receive the response from Payment Gateway---
	  $response = ['tid' => rand(100000, 99999999)];
	   //Here, check response and process accordingly---
	   if(!$response)
	   {
	     throw new \Magento\Framework\Exception\LocalizedException(__('Failed authorize request.'));
	  }    return $response;
	 } 
	   /**
	    * Test method to handle an API call for capture request. 
	    *
	    * @param $request 
	    * @return array 
	    * @throws \Magento\Framework\Exception\LocalizedException
	    */
	    public function captureRequest($request) {
	       //Process Request and receive the response from Payment Gateway---                    $response = ['tid' => rand(100000, 99999999)];
	        //Here, check response and process accordingly---
	       if(!$response)
	       {
	         throw new \Magento\Framework\Exception\LocalizedException(__('Failed capture request.'));
	       }
	         return $response;
	    }
	  }