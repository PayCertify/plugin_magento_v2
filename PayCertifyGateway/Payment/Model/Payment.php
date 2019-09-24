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
 * @author     Daviwp <davi.wordpress@gmail.com>
 */

namespace PayCertifyGateway\Payment\Model;

/**
 * PayCertify Gateway payment method
 *
 * @category   PayCertify
 * @package    PayCertify_Gateway
 * @author     Daviwp <davi.wordpress@gmail.com>
 */

class Payment extends \Magento\Payment\Model\Method\Cc 
{
    const CODE = 'paycertifygateway';
    protected $_code = self::CODE;
    protected $_canAuthorize = false;
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
        $this->cart = $cart; 
        $this->_countryFactory = $countryFactory;
   	}



}