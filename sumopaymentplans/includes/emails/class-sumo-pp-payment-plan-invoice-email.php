<?php

/**
 * Invoice Order - Email.
 * 
 * @class SUMO_PP_Payment_Plan_Invoice_Email
 * @category Class
 */
class SUMO_PP_Payment_Plan_Invoice_Email extends SUMO_PP_Abstract_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id             = $this->prefix . 'payment_plan_invoice' ;
        $this->name           = 'payment_plan_invoice' ;
        //$this->customer_email = true ;
        $this->customer_email = false ;
        $this->title          = __( 'Payment Invoice – Payment Plan' , $this->text_domain ) ;
        $this->description    = addslashes( sprintf( __( 'Payment Invoice – Payment Plan will be sent to the customers when their installment payment is due for their payment plan.' , $this->text_domain ) ) ) ;

        $this->template_html  = 'emails/sumo-pp-payment-plan-invoice.php' ;
        $this->template_plain = 'emails/plain/sumo-pp-payment-plan-invoice.php' ;

        $this->subject = __( '[{site_title}] - Invoice for {product_with_installment_no}' , $this->text_domain ) ;
        $this->heading = __( 'Invoice for {product_with_installment_no}' , $this->text_domain ) ;

        $this->subject_paid = $this->subject ;
        $this->heading_paid = $this->heading ;

        // Call parent constructor
        parent::__construct() ;
    }

}

return new SUMO_PP_Payment_Plan_Invoice_Email() ;
