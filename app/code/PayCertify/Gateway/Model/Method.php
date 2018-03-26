<?php
/**
 * PayCertify's extension for Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   PayCertify
 * @package    PayCertify_Gateway
 * @copyright  Copyright (c) 2018 PayCertify (https://www.paycertify.com/)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @author     Valentin Sushkov <me@vsushkov.com>
 */

namespace PayCertify\Gateway\Model;

/**
 * PayCertify Gateway payment method
 *
 * @category   PayCertify
 * @package    PayCertify_Gateway
 * @author     Valentin Sushkov <me@vsushkov.com>
 */
class Method extends \Magento\Payment\Model\Method\Cc
{
    const CODE = 'paycertify_gateway';
    protected $_code = self::CODE;

    protected $_isGateway                  = true;
    protected $_canSaveCc                  = false;
    protected $_canOrder                   = false;
    protected $_canAuthorize               = false;
    protected $_canCapture                 = true;
    protected $_canCapturePartial          = false;
    protected $_canRefund                  = true;
    protected $_canRefundInvoicePartial    = true;
    protected $_canVoid                    = false;
    protected $_canUseInternal             = false;
    protected $_canUseCheckout             = true;
    protected $_canUseForMultishipping     = true;
    protected $_isInitializeNeeded         = false;
    protected $_canFetchTransactionInfo    = false;
    protected $_canReviewPayment           = false;
    protected $_canCreateBillingAgreement  = false;
    protected $_canManageRecurringProfiles = false;
    protected $_debugReplacePrivateDataKeys = ['number', 'exp_month', 'exp_year', 'cvc'];

    protected $_countryFactory;
    protected $_transactionFactory;

    protected $_minAmount = null;
    protected $_maxAmount = null;
    protected $_supportedCurrencyCodes = array('USD');

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory,
        array $data = array()
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            null,
            null,
            $data
        );

        $this->_countryFactory = $countryFactory;
        $this->_transactionFactory = $transactionFactory;

        $this->_minAmount = $this->getConfigData('min_order_total');
        $this->_maxAmount = $this->getConfigData('max_order_total');
    }

    /**
     * Check whether payment method can be used
     *
     * It cannot be used if base currency is not USD
     *
     * @param \Magento\Quote\Model\Quote|null $quote
     *
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($quote
            && (
                $quote->getBaseGrandTotal() < $this->_minAmount
                || ($this->_maxAmount && $quote->getBaseGrandTotal() > $this->_maxAmount)
            )
        ) {
            return false;
        }

        if (!$this->getConfigData('api_token')) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * Availability for currency
     *
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }
        return true;
    }

    /**
     * Capture payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float                                $amount
     *
     * @return \PayCertify\Gateway\Model\Method
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canCapture()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Capture action is not available.'));
        }

        if ($amount <= 0) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid amount for capture.'));
        }

        $this->_captureOnGateway($payment, $amount);

        return $this;
    }

    /**
     * Refund the amount with transaction id
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float         $amount
     *
     * @return \PayCertify\Gateway\Model\Method
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if ($amount <= 0) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid amount for refund.'));
        }

        if (!$transactionId = $payment->getParentTransactionId()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid transaction ID.'));
        }

        $transactionRawData = $this->_transactionFactory->create()->load($transactionId, 'txn_id')
            ->getAdditionalInformation(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS)
        ;

        if (!isset($transactionRawData['transaction.id'])
            || !$transactionId = $transactionRawData['transaction.id']
        ) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid transaction ID.'));
        }

        $client = $this->_getClient($this->getConfigData('gateway_refund_url') . "/$transactionId/refund");

        $params = array('amount' => $amount);
        $client->setParameterPost($params);

        $response = $client->send();
        $responseData = \Zend\Json\Json::decode($response->getBody(), \Zend\Json\Json::TYPE_ARRAY);

        $payment->setTransactionAdditionalInfo(
            \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $this->_formatTransactionInfo($responseData)
        );

        $lastEvent = $responseData['transaction']['events'][0];

        if (!$lastEvent['success']) {
            $this->debugData(['request' => $params, 'response' => $responseData]);
            $this->_logger->error(__('Payment refunding error.'));
            throw new \Magento\Framework\Exception\LocalizedException($lastEvent['processor_message']);
        }

        $payment->setTransactionId($lastEvent['id']);

        return $this;
    }

    private function _captureOnGateway($payment, $amount)
    {
        $params = $this->_getCaptureParams($payment, $amount);

        $client = $this->_getClient($this->getConfigData('gateway_sale_url'));
        $client->setParameterPost($params);

        $response = $client->send();
        $responseData = \Zend\Json\Json::decode($response->getBody(), \Zend\Json\Json::TYPE_ARRAY);

        $payment->setTransactionAdditionalInfo(
            \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $this->_formatTransactionInfo($responseData)
        );

        if (isset($responseData['error'])) {
            $this->debugData(['request' => $params, 'response' => $responseData]);
            $this->_logger->error(__('Payment capturing error.'));
            throw new \Magento\Framework\Exception\LocalizedException(__(
                "We weren't able to process this card. Please contact your bank for more information."
            ));
        }

        if (!isset($responseData['transaction']['events'][0])) {
            return;
        }

        $lastEvent = $responseData['transaction']['events'][0];
        if (!$lastEvent['success']) {
            $this->debugData(['request' => $params, 'response' => $responseData]);
            $this->_logger->error(__('Payment capturing error.'));
            throw new \Magento\Framework\Exception\LocalizedException(__(
                "We weren't able to process this card. Please contact your bank for more information."
            ));
        }

        $payment->setTransactionId($lastEvent['id']);
    }

    private function _getCaptureParams($payment, $amount)
    {
        $billing = $payment->getOrder()->getBillingAddress();
        $shipping = $payment->getOrder()->getShippingAddress();

        $params = array(
            'amount'                    => $amount,
            'card_number'               => $payment->getCcNumber(),
            'card_expiry_month'         => str_pad($payment->getCcExpMonth(), 2, '0', STR_PAD_LEFT),
            'card_expiry_year'          => $payment->getCcExpYear(),
            'card_cvv'                  => $payment->getCcCid(),
            'merchant_transaction_id'   => $payment->getOrder()->getIncrementId(),
            'first_name'                => $billing->getFirstname(),
            'last_name'                 => $billing->getLastname(),
            'email'                     => $billing->getEmail(),
            'street_address_1'          => $billing->getStreetLine(1),
            'street_address_2'          => $billing->getStreetLine(2),
            'city'                      => $billing->getCity(),
            'state'                     => $this->_getState($billing),
            'country'                   => $this->_countryFactory->create()->loadByCode($billing->getCountryId())->getData('iso2_code'),
            'zip'                       => $billing->getPostcode(),
            'shipping_street_address_1' => $shipping->getStreetLine(1),
            'shipping_street_address_2' => $shipping->getStreetLine(2),
            'shipping_city'             => $shipping->getCity(),
            'shipping_state'            => $this->_getState($shipping),
            'shipping_country'          => $this->_countryFactory->create()->loadByCode($shipping->getCountryId())->getData('iso2_code'),
            'shipping_zip'              => $shipping->getPostcode(),
        );

        if ($this->getConfigData('use_avs')) {
            $params['avs_enabled'] = true;
        }

        if ($this->getConfigData('dynamic_descriptor')) {
            $params['dynamic_descriptor'] = $this->getConfigData('dynamic_descriptor');
        }

        return $params;
    }

    private function _getClient($url)
    {
        $client = new \Zend\Http\Client($url);
        $client->setMethod(\Zend\Http\Request::METHOD_POST)
            ->setEncType(\Zend\Http\Client::ENC_FORMDATA)
            ->setHeaders(array('Authorization' => 'Bearer ' . $this->getConfigData('api_token')))
        ;
        return $client;
    }

    private function _getState($address)
    {
        $code = $this->_countryFactory->create()->loadByCode($address->getCountryId())->getData('iso2_code');
        if ($code == 'US') {
            return $address->getRegionCode();
        } else {
            return '';
        }
    }

    protected function _formatTransactionInfo($data, $prefix = '')
    {
        $result = array();
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result = $result + $this->_formatTransactionInfo($value, $prefix . $key . '.');
            } else {
                $result[$prefix . $key] = $value;
            }
        }
        return $result;
    }

}
