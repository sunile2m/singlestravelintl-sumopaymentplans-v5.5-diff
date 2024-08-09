<?php

/**
 * Advanced Tab.
 * 
 * @class SUMO_PP_Advanced_Settings
 * @category Class
 */
class SUMO_PP_Advanced_Settings extends SUMO_PP_Abstract_Settings {

    /**
     * SUMO_PP_Advanced_Settings constructor.
     */
    public function __construct() {

        $this->id       = 'advanced' ;
        $this->label    = __( 'Advanced' , $this->text_domain ) ;
        $this->settings = $this->get_settings() ;
        $this->init() ;
    }

    /**
     * Get settings array.
     * @return array
     */
    public function get_settings() {
        global $current_section ;

        return apply_filters( 'sumopaymentplans_get_' . $this->id . '_settings' , array (
            array (
                'name' => __( 'Advanced Settings' , $this->text_domain ) ,
                'type' => 'title' ,
                'id'   => $this->prefix . 'advanced_settings' ,
            ) ,
            array (
                'name'     => __( 'Disable WooCommerce Emails for Payment Plan Orders' , $this->text_domain ) ,
                'id'       => $this->prefix . 'disabled_wc_order_emails' ,
                'newids'   => $this->prefix . 'disabled_wc_order_emails' ,
                'type'     => 'multiselect' ,
                'options'  => array (
                    'processing' => __( 'Processing order' , $this->text_domain ) ,
                    'completed'  => __( 'Completed order' , $this->text_domain ) ,
                ) ,
                'std'      => array () ,
                'default'  => array () ,
                'desc'     => __( 'This option will be applicable only for balance payable orders' , $this->text_domain ) ,
                'desc_tip' => true ,
            ) ,
            array (
                'name'     => __( 'Calculate Price for Deposits/Payment Plans based on' , $this->text_domain ) ,
                'id'       => $this->prefix . 'calc_deposits_r_payment_plans_price_based_on' ,
                'newids'   => $this->prefix . 'calc_deposits_r_payment_plans_price_based_on' ,
                'type'     => 'select' ,
                'options'  => array (
                    'regular-price' => __( 'Regular Price' , $this->text_domain ) ,
                    'sale-price'    => __( 'Sale Price' , $this->text_domain ) ,
                ) ,
                'std'      => 'sale-price' ,
                'default'  => 'sale-price' ,
                'desc_tip' => true ,
            ) ,
            array (
                'name'     => __( 'Balance Payment Activation for Deposits/Payment Plans will be decided' , $this->text_domain ) ,
                'id'       => $this->prefix . 'activate_payments' ,
                'newids'   => $this->prefix . 'activate_payments' ,
                'type'     => 'select' ,
                'std'      => 'auto' ,
                'default'  => 'auto' ,
                'options'  => array (
                    'auto'                 => __( 'Automatically' , $this->text_domain ) ,
                    'after_admin_approval' => __( 'After Admin Approval' , $this->text_domain ) ,
                ) ,
                'desc_tip' => __( 'If "After Admin Approval" option is chosen, admin needs to activate Payment Plans/Deposits in edit payment page.' , $this->text_domain )
            ) ,
            array (
                'name'    => __( 'Cancel Payments after Balance Payment Due Date' , $this->text_domain ) ,
                'id'      => $this->prefix . 'cancel_payments_after_balance_payment_due_date' ,
                'newids'  => $this->prefix . 'cancel_payments_after_balance_payment_due_date' ,
                'type'    => 'select' ,
                'std'     => 'after_admin_approval' ,
                'default' => 'after_admin_approval' ,
                'options' => array (
                    'auto'                 => __( 'Automatically' , $this->text_domain ) ,
                    'after_admin_approval' => __( 'After Admin Approval' , $this->text_domain ) ,
                ) ,
            ) ,
            array (
                'name'              => __( 'Payment Identification Number Prefix' , $this->text_domain ) ,
                'id'                => $this->prefix . 'payment_number_prefix' ,
                'newids'            => $this->prefix . 'payment_number_prefix' ,
                'type'              => 'text' ,
                'std'               => '' ,
                'default'           => '' ,
                'custom_attributes' => array (
                    'maxlength' => 30
                ) ,
                'desc'              => __( 'Prefix can be alpha-numeric' , $this->text_domain ) ,
            ) ,
            array (
                'name'     => __( 'Hide Product Price in Single Product Page when User Selects the Payment Plans' , $this->text_domain ) ,
                'id'       => $this->prefix . 'hide_product_price_for_payment_plans' ,
                'newids'   => $this->prefix . 'hide_product_price_for_payment_plans' ,
                'type'     => 'checkbox' ,
                'std'      => 'no' ,
                'default'  => 'no' ,
                'desc_tip' => true ,
            ) ,
            array(
                'name'    => __( 'Date and Time Format' , $this->text_domain ) ,
                'id'      => $this->prefix . 'set_date_time_format_as' ,
                'newids'  => $this->prefix . 'set_date_time_format_as' ,
                'type'    => 'select' ,
                'std'     => 'default' ,
                'default' => 'default' ,
                'options' => array(
                    'default'   => __( 'Default' , $this->text_domain ) ,
                    'wordpress' => __( 'WordPress Format' , $this->text_domain ) ,
                ) ,
            ) ,
            array (
                'name'     => __( 'Display Time' , $this->text_domain ) ,
                'id'       => $this->prefix . 'show_time_in_frontend' ,
                'newids'   => $this->prefix . 'show_time_in_frontend' ,
                'type'     => 'select' ,
                'std'      => 'enable' ,
                'default'  => 'enable' ,
                'options'  => array (
                    'disable' => __( 'Disable' , $this->text_domain ) ,
                    'enable'  => __( 'Enable' , $this->text_domain ) ,
                ) ,
                'desc_tip' => __( 'If enabled, time will be displayed in single product page, cart page, checkout page and my account page.' , 'sumosubscriptions' ) ,
            ) ,
            array (
                'name'    => __( 'Custom CSS' , $this->text_domain ) ,
                'id'      => $this->prefix . 'custom_css' ,
                'newids'  => $this->prefix . 'custom_css' ,
                'type'    => 'textarea' ,
                'css'     => 'height:200px;' ,
                'std'     => '' ,
                'default' => '' ,
            ) ,
            array ( 'type' => 'sectionend' , 'id' => $this->prefix . 'advanced_settings' ) ,
            array (
                'name' => __( 'Payment Plan Specific Date Settings' , $this->text_domain ) ,
                'type' => 'title' ,
                'id'   => $this->prefix . 'payment_plan_specific_date_settings' ,
            ) ,
            array (
                'name'    => __( 'Payment Plan Behavior After Initial Payment Date' , $this->text_domain ) ,
                'id'      => $this->prefix . 'payment_plan_behaviour_after_initial_payment_date' ,
                'newids'  => $this->prefix . 'payment_plan_behaviour_after_initial_payment_date' ,
                'type'    => 'select' ,
                'std'     => 'sum-up-with-previous-installments' ,
                'default' => 'sum-up-with-previous-installments' ,
                'options' => array (
                    'sum-up-with-previous-installments' => __( 'Add All the Previous Installment Amount and Charge with Current Installment Amount' , $this->text_domain ) ,
                    'hide-payment-plan'                 => __( 'Hide the Payment Plan' , $this->text_domain ) ,
                ) ,
            ) ,
            array (
                'name'    => __( 'If No Valid Payment Plans are Available to Display' , $this->text_domain ) ,
                'id'      => $this->prefix . 'when_no_payment_plans_are_available' ,
                'newids'  => $this->prefix . 'when_no_payment_plans_are_available' ,
                'type'    => 'select' ,
                'std'     => 'disable-payment-plan' ,
                'default' => 'disable-payment-plan' ,
                'options' => array (
                    'disable-payment-plan' => __( 'Disable Payment Plan for that Product' , $this->text_domain ) ,
                    'set-as-out-of-stock'  => __( 'Make the Product as Out of Stock' , $this->text_domain ) ,
                ) ,
            ) ,
            array ( 'type' => 'sectionend' , 'id' => $this->prefix . 'payment_plan_specific_date_settings' ) ,
            array (
                'name' => __( 'Experimental Settings' , $this->text_domain ) ,
                'type' => 'title' ,
                'id'   => $this->prefix . 'experimental_settings' ,
            ) ,
            array (
                'name'     => __( 'Display Payment Plans as Hyperlink in Single Product Page' , $this->text_domain ) ,
                'id'       => $this->prefix . 'payment_plan_add_to_cart_via_href' ,
                'newids'   => $this->prefix . 'payment_plan_add_to_cart_via_href' ,
                'type'     => 'checkbox' ,
                'std'      => 'no' ,
                'default'  => 'no' ,
                'desc'     => __( 'If enabled, payment plans will be displayed as hyperlink which when clicked, the payment plan will be directly added to cart' , $this->text_domain ) ,
                'desc_tip' => true ,
            ) ,
            array (
                'name'     => __( 'When the Hyperlink is Clicked' , $this->text_domain ) ,
                'id'       => $this->prefix . 'after_hyperlink_clicked_redirect_to' ,
                'newids'   => $this->prefix . 'after_hyperlink_clicked_redirect_to' ,
                'type'     => 'select' ,
                'options'  => array (
                    'product'  => __( 'Stay on Product Page' , $this->text_domain ) ,
                    'cart'     => __( 'Redirect to Cart Page' , $this->text_domain ) ,
                    'checkout' => __( 'Redirect to Checkout Page' , $this->text_domain ) ,
                ) ,
                'std'      => 'product' ,
                'default'  => 'product' ,
                'desc_tip' => true ,
            ) ,
            array ( 'type' => 'sectionend' , 'id' => $this->prefix . 'experimental_settings' ) ,
                ) ) ;
    }

}

return new SUMO_PP_Advanced_Settings() ;
