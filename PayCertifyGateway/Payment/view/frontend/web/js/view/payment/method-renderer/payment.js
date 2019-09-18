define([
  'Magento_Payment/js/view/payment/cc-form',
  'jquery',
  'Magento_Payment/js/model/credit-card-validation/validator'
 ], function (Component, $) {
     'use strict';
        return Component.extend({
             defaults: {
                template: 'PayCertify_Gateway/payment/payment'
             },
            getCode: function() {  	           return 'paycertifygateway';
	           },
 	       isActive: function() {
 	          return true;
 	       },
 	       validate: function() { var $form = $('#' + this.getCode() + '-form');
 		     return $form.validation() && $form.validation('isValid');
             }
       });
});