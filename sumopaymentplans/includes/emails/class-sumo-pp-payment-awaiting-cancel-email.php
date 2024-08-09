<?php

/**
 * Payment Awaiting Cancel - Email.
 * 
 * @class SUMO_PP_Payment_Awaiting_Cancel_Email
 * @category Class
 */
class SUMO_PP_Payment_Awaiting_Cancel_Email extends SUMO_PP_Abstract_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id          = $this->prefix . 'payment_awaiting_cancel' ;
        $this->name        = 'payment_awaiting_cancel' ;
        $this->title       = __( 'Payment Awaiting Cancel' , $this->text_domain ) ;
        $this->description = addslashes( sprintf( __( 'Payment Awaiting Cancel Email notification will be sent to the admin when the user has not paid their balance payments within the due date.' , $this->text_domain ) ) ) ;

        $this->template_html  = 'emails/sumo-pp-payment-awaiting-cancel.php' ;
        $this->template_plain = 'emails/plain/sumo-pp-payment-awaiting-cancel.php' ;

        $this->subject = __( '[{site_title}] - Payment Awaiting Cancel for Payment #{payment_no}' , $this->text_domain ) ;
        $this->heading = __( 'Payment Awaiting Cancel for Payment #{payment_no}' , $this->text_domain ) ;

        $this->subject_paid = $this->subject ;
        $this->heading_paid = $this->heading ;
        $this->supports     = array ( 'recipient' ) ;

        // Call parent constructor
        parent::__construct() ;

        $this->recipient = $this->get_option( 'recipient' , get_option( 'admin_email' ) ) ;
    }

}

return new SUMO_PP_Payment_Awaiting_Cancel_Email() ;
