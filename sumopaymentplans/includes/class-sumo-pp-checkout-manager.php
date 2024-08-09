<?php

if( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Manage payment products in checkout.
 * 
 * @class SUMO_PP_Checkout_Manager
 * @category Class
 */
class SUMO_PP_Checkout_Manager {

    protected static $checkout_contains_payments ;

    /**
     * The single instance of the class.
     */
    protected static $instance = null ;

    /**
     * Create instance for SUMO_PP_Checkout_Manager.
     */
    public static function instance() {
        if( is_null( self::$instance ) ) {
            self::$instance = new self() ;
        }
        return self::$instance ;
    }

    /**
     * Construct SUMO_PP_Checkout_Manager.
     */
    public function __construct() {
        add_action( 'woocommerce_before_checkout_form' , __CLASS__ . '::force_guest_signup_on_checkout' , 999 , 1 ) ;
        add_action( 'woocommerce_checkout_process' , __CLASS__ . '::force_create_account_for_guest' , 999 ) ;
        add_filter( 'woocommerce_available_payment_gateways' , __CLASS__ . '::set_payment_gateways' , 999 ) ;
    }

    public static function checkout_contains_payments() {
        if( is_bool( self::$checkout_contains_payments ) ) {
            return self::$checkout_contains_payments ;
        }

        if( SUMO_PP_Cart_Manager::cart_contains_payment() || SUMO_PP_Order_Payment_Plan::is_enabled() ) {
            self::$checkout_contains_payments = true ;
        } else {
            self::$checkout_contains_payments = false ;
        }
        return self::$checkout_contains_payments ;
    }

    /**
     * Force Display Signup on Checkout for Guest. 
     * Since Guest don't have the permission to buy Deposit Payments.
     */
    public static function force_guest_signup_on_checkout( $checkout ) {
        if( is_user_logged_in() || $checkout->is_registration_required() ) {
            return ;
        }

        if( ! $checkout->is_registration_enabled() && SUMO_PP_Order_Payment_Plan::can_user_deposit_payment() ) {
            add_filter( 'woocommerce_checkout_registration_enabled' , '__return_true' , 99 ) ;
            add_filter( 'woocommerce_checkout_registration_required' , '__return_true' , 99 ) ;
        } else if( self::checkout_contains_payments() ) {
            $checkout->enable_signup         = true ;
            $checkout->enable_guest_checkout = false ;
        }
    }

    /**
     * To Create account for Guest.
     */
    public static function force_create_account_for_guest() {
        if( ! is_user_logged_in() && self::checkout_contains_payments() ) {
            $_POST[ 'createaccount' ] = 1 ;
        }
    }

    /**
     * Handle payment gateways in checkout
     * @param array $_available_gateways
     * @return array
     */
    public static function set_payment_gateways( $_available_gateways ) {
        $disabled_payment_gateways = get_option( SUMO_PP_PLUGIN_PREFIX . 'disabled_payment_gateways' ) ;

        if( empty( $disabled_payment_gateways ) || ! self::checkout_contains_payments() ) {
            return $_available_gateways ;
        }

        foreach( $_available_gateways as $gateway_name => $gateway ) {
            if( ! isset( $gateway->id ) ) {
                continue ;
            }

            if( in_array( $gateway->id , ( array ) $disabled_payment_gateways ) ) {
                unset( $_available_gateways[ $gateway_name ] ) ;
            }
        }
        return $_available_gateways ;
    }

}
