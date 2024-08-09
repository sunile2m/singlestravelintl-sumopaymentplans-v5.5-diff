<?php

/**
 * Abstract Payment Email
 * 
 * @abstract SUMO_PP_Abstract_Email
 */
abstract class SUMO_PP_Abstract_Email extends WC_Email {

    /**
     * @var array Supports
     */
    public $supports = array ( 'mail_to_admin' ) ;

    /**
     * @var object Payment post 
     */
    public $payment ;

    /**
     * @var string Get plugin prefix
     */
    public $prefix = SUMO_PP_PLUGIN_PREFIX ;

    /**
     * @var string Get plugin text domain.
     */
    public $text_domain = SUMO_PP_PLUGIN_TEXT_DOMAIN ;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->template_base = SUMO_PP_PLUGIN_TEMPLATE_PATH ;
        $this->mail_to_admin = 'yes' === $this->get_option( 'mail_to_admin' ) ;

        // Call WC_Email constuctor
        parent::__construct() ;
    }

    /**
     * Populate the Email
     * 
     * @param int $order_id
     * @param object $payment
     * @param string $to
     */
    protected function populate( $order_id , $payment , $to ) {
        $this->payment  = $payment ;
        $this->order_id = absint( $order_id ) ;
        $this->object   = wc_get_order( $this->order_id ) ;

        if ( ! $this->supports( 'recipient' ) ) {
            $this->recipient = ! empty( $to ) ? $to : $this->object->get_billing_email() ;

            if ( $this->supports( 'mail_to_admin' ) && $this->mail_to_admin ) {
                $this->recipient = $this->recipient . ',' . get_option( 'admin_email' ) ;
            }
        }
    }

    /**
     * Check this Email supported feature.
     * @param string $type
     * @return boolean
     * 
     */
    public function supports( $type = '' ) {
        return in_array( $type , $this->supports ) ;
    }

    /**
     * Trigger.
     * 
     * @return bool on Success
     */
    public function trigger( $order_id , $payment , $to = '' ) {
        $this->populate( $order_id , $payment , $to ) ;

        $payment_count = sizeof( $this->payment->get_balance_paid_orders() ) ;
        if ( in_array( $this->id , array ( $this->prefix . 'payment_plan_invoice' , $this->prefix . 'payment_plan_overdue' ) ) ) {
            $payment_count += 1 ;
        }

        $this->find[ 'payment-no' ]                  = '{payment_no}' ;
        $this->find[ 'product-name' ]                = '{product_name}' ;
        $this->find[ 'product-with-installment-no' ] = '{product_with_installment_no}' ;

        $this->replace[ 'payment-no' ]                  = $this->payment->get_payment_number() ;
        $this->replace[ 'product-name' ]                = $this->payment->get_formatted_product_name( array (
            'tips'           => false ,
            'maybe_variable' => false ,
            'qty'            => false ,
                ) ) ;
        $this->replace[ 'product-with-installment-no' ] = sprintf( __( 'Installment #%s of %s' , $this->text_domain ) , $payment_count , $this->payment->get_formatted_product_name( array (
                    'tips'           => false ,
                    'maybe_variable' => false ,
                    'qty'            => false ,
                ) ) ) ;

        if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
            return false ;
        }

        return $this->send( $this->get_recipient() , $this->get_subject() , $this->get_content() , $this->get_headers() , $this->get_attachments() ) ;
    }

    /**
     * get_type function.
     *
     * @return string
     */
    public function get_email_type() {
        return class_exists( 'DOMDocument' ) ? 'html' : '' ;
    }

    /**
     * Format date to display.
     * @param int|string $date
     * @return string
     */
    public function format_date( $date = '' ) {
        return _sumo_pp_get_date_to_display( $date ) ;
    }

    /**
     * Get content HTMl.
     *
     * @return string
     */
    public function get_content_html() {
        $supports = array () ;

        if ( $this->supports( 'pay_link' ) ) {
            $supports = array_merge( array (
                'payment_link' => $this->get_option( 'enable_pay_link' )
                    ) , $supports ) ;
        }

        ob_start() ;

        _sumo_pp_get_template( $this->template_html , array_merge( array (
            'order'         => _sumo_pp_get_order( $this->object ) ,
            'payment_id'    => $this->payment->id ,
            'payment'       => $this->payment ,
            'email_heading' => $this->get_heading() ,
            'sent_to_admin' => true ,
            'plain_text'    => false ,
            'email'         => $this ,
                        ) , $supports ) ) ;

        return ob_get_clean() ;
    }

    /**
     * Get content plain.
     *
     * @return string
     */
    public function get_content_plain() {
        return '' ;
    }

    /**
     * Display form fields
     */
    public function init_form_fields() {
        $this->form_fields = array (
            'enabled' => array (
                'title'   => __( 'Enable/Disable' , $this->text_domain ) ,
                'type'    => 'checkbox' ,
                'label'   => __( 'Enable this email notification' , $this->text_domain ) ,
                'default' => 'yes'
            ) ) ;

        if ( $this->supports( 'recipient' ) ) {
            $this->form_fields = array_merge( $this->form_fields , array (
                'recipient' => array (
                    'title'       => __( 'Recipient(s)' , $this->text_domain ) ,
                    'type'        => 'text' ,
                    'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.' , $this->text_domain ) , '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ) ,
                    'placeholder' => '' ,
                    'default'     => '' ,
                    'desc_tip'    => true ,
                ) ) ) ;
        }

        $this->form_fields = array_merge( $this->form_fields , array (
            'subject' => array (
                'title'       => __( 'Email Subject' , $this->text_domain ) ,
                'type'        => 'text' ,
                'description' => sprintf( __( 'Defaults to <code>%s</code>' , $this->text_domain ) , $this->subject ) ,
                'placeholder' => '' ,
                'default'     => ''
            ) ,
            'heading' => array (
                'title'       => __( 'Email Heading' , $this->text_domain ) ,
                'type'        => 'text' ,
                'description' => sprintf( __( 'Defaults to <code>%s</code>' , $this->text_domain ) , $this->heading ) ,
                'placeholder' => '' ,
                'default'     => ''
            ) ) ) ;

        if ( $this->supports( 'paid_order' ) ) {
            $this->form_fields = array_merge( $this->form_fields , array (
                'subject_paid' => array (
                    'title'       => __( 'Email Subject (paid)' , $this->text_domain ) ,
                    'type'        => 'text' ,
                    'description' => sprintf( __( 'Defaults to <code>%s</code>' , $this->text_domain ) , $this->subject_paid ) ,
                    'placeholder' => '' ,
                    'default'     => ''
                ) ,
                'heading_paid' => array (
                    'title'       => __( 'Email Heading (paid)' , $this->text_domain ) ,
                    'type'        => 'text' ,
                    'description' => sprintf( __( 'Defaults to <code>%s</code>' , $this->text_domain ) , $this->heading_paid ) ,
                    'placeholder' => '' ,
                    'default'     => ''
                ) ) ) ;
        }

        if ( $this->supports( 'pay_link' ) ) {
            $this->form_fields = array_merge( $this->form_fields , array (
                'enable_pay_link' => array (
                    'title'   => __( 'Enable Payment Link in Mail' , $this->text_domain ) ,
                    'type'    => 'checkbox' ,
                    'default' => 'yes'
                ) ) ) ;
        }

        if ( $this->supports( 'mail_to_admin' ) ) {
            $this->form_fields = array_merge( $this->form_fields , array (
                'mail_to_admin' => array (
                    'title'   => __( 'Send Email to Admin' , $this->text_domain ) ,
                    'type'    => 'checkbox' ,
                    'default' => 'no'
                ) ) ) ;
        }
    }

}
