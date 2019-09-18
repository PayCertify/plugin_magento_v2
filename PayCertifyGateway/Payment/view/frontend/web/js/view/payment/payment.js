define([
'uiComponent',
'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) { 
  'use strict';
   rendererList.push({ type: 'paycertifygateway', component: 'PayCertify_Gateway/js/view/payment/method-renderer/payment' });
     return Component.extend({});
});