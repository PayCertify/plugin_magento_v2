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

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'PayCertify_Gateway',
    __DIR__
);
