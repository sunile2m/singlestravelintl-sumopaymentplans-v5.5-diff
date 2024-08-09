<?php

/**
 * Invoice Order - Email.
 * 
 * @class SUMO_PP_Payment_Plan_Overdue_Email
 * @category Class
 */
class SUMO_PP_Payment_Plan_Overdue_Email extends SUMO_PP_Abstract_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id             = $this->prefix . 'payment_plan_overdue' ;
        $this->name           = 'payment_plan_overdue' ;
        $this->customer_email = true ;
        $this->title          = __( 'Payment Overdue – Payment Plan' , $this->text_domain ) ;
        $this->description    = addslashes( sprintf( __( 'Payment Overdue – Payment Plan will be sent to the customers when their installment for their Payment Plan is currently Overdue.' , $this->text_domain ) ) ) ;

        $this->template_html  = 'emails/sumo-pp-payment-plan-overdue.php' ;
        $this->template_plain = 'emails/plain/sumo-pp-payment-plan-overdue.php' ;

        $this->subject = __( '[{site_title}] - Payment Overdue for {product_with_installment_no}' , $this->text_domain ) ;
        $this->heading = __( 'Payment Overdue for {product_with_installment_no}' , $this->text_domain ) ;

        $this->subject_paid = $this->subject ;
        $this->heading_paid = $this->heading ;

        // Call parent constructor
        parent::__construct() ;
    }

}

return new SUMO_PP_Payment_Plan_Overdue_Email() ;
