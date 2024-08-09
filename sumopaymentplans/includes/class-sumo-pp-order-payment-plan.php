<?php

if( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle Normal products in cart as Order Payment Plan in Checkout.
 * 
 * @class SUMO_PP_Order_Payment_Plan
 * @category Class
 */
class SUMO_PP_Order_Payment_Plan {

    /**
     * Check whether the customer can proceed to deposit/payment plans in their checkout
     * @var bool 
     */
    protected static $can_user_deposit_payment ;

    protected static $order_props = array(
        'product_type'                   => null ,
        'product_price'                  => null ,
        'product_qty'                    => null ,
        'payment_type'                   => null ,
        'down_payment'                   => null ,
        'activate_payment'               => null ,
        'next_payment_date'              => null ,
        'next_installment_amount'        => null ,
        'total_payable_amount'           => null ,
        'remaining_payable_amount'       => null ,
        'apply_global_settings'          => null ,
        'force_deposit'                  => null ,
        'deposit_type'                   => null ,
        'deposit_price_type'             => null ,
        'fixed_deposit_percent'          => null ,
        'user_defined_deposit_type'      => null ,
        'min_user_defined_deposit_price' => null ,
        'max_user_defined_deposit_price' => null ,
        'min_deposit'                    => null ,
        'max_deposit'                    => null ,
        'pay_balance_type'               => null ,
        'pay_balance_after'              => null ,
        'pay_balance_before'             => null ,
        'selected_plans'                 => null ,
        'order_items'                    => null ,
        'payment_plan_props'             => null ,
            ) ;

    protected static $get_options = array(
        'order_payment_plan_enabled'     => null ,
        'product_type'                   => null ,
        'payment_type'                   => null ,
        'apply_global_settings'          => null ,
        'force_deposit'                  => null ,
        'deposit_type'                   => null ,
        'deposit_price_type'             => null ,
        'fixed_deposit_percent'          => null ,
        'user_defined_deposit_type'      => null ,
        'min_user_defined_deposit_price' => null ,
        'max_user_defined_deposit_price' => null ,
        'min_deposit'                    => null ,
        'max_deposit'                    => null ,
        'pay_balance_type'               => null ,
        'pay_balance_after'              => null ,
        'pay_balance_before'             => null ,
        'selected_plans'                 => null ,
        'min_order_total'                => null ,
        'labels'                         => null ,
            ) ;

    /**
     * The single instance of the class.
     */
    protected static $instance = null ;

    /**
     * Form to render Order Payment Plan
     */
    protected static $form = null ;

    /**
     * Create instance for SUMO_PP_Order_Payment_Plan.
     */
    public static function instance() {
        if( is_null( self::$instance ) ) {
            self::$instance = new self() ;
        }
        return self::$instance ;
    }

    /**
     * Get form to render Order Payment Plan
     */
    public static function get_form() {
        if( is_null( self::$form ) ) {
            self::$form = get_option( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_form_position' , 'checkout_order_review' ) ;
        }
        return self::$form ;
    }

    /**
     * Construct SUMO_PP_Order_Payment_Plan.
     */
    public function __construct() {
        if( ! is_admin() ) {
            add_action( 'wp_loaded' , __CLASS__ . '::get_option_props' , 20 ) ;
        }

        add_action( 'woocommerce_' . self::get_form() , __CLASS__ . '::render_plan_selector' ) ;
        add_action( 'wp_loaded' , __CLASS__ . '::get_payment_from_session' , 20 ) ;
        add_action( 'woocommerce_after_calculate_totals' , __CLASS__ . '::get_payment_from_session' , 20 ) ;
        add_action( 'woocommerce_review_order_after_order_total' , __CLASS__ . '::render_payment_info' , 999 ) ;
        add_filter( 'woocommerce_cart_get_total' , __CLASS__ . '::set_cart_total' , 999 , 1 ) ;
        add_filter( 'woocommerce_cart_total' , __CLASS__ . '::set_total_payable_amount' , 999 , 1 ) ;
        add_action( 'woocommerce_after_checkout_validation' , __CLASS__ . '::validate_checkout' , 999 , 2 ) ;
        add_action( 'woocommerce_checkout_update_order_meta' , __CLASS__ . '::add_order_items' , 2 , 2 ) ;
        add_filter( 'woocommerce_order_item_display_meta_key' , __CLASS__ . '::render_order_item_meta_key' , 999 , 3 ) ;
        add_filter( 'woocommerce_get_order_item_totals' , __CLASS__ . '::render_paid_now' , 999 , 3 ) ;
        add_action( 'woocommerce_grant_product_download_permissions' , __CLASS__ . '::grant_product_download_permissions' , 99 ) ;
    }

    public static function get_default_props( $props ) {
        return array_map( '__return_null' , $props ) ;
    }

    public static function get_option_props() {
        if( is_bool( self::$get_options[ 'order_payment_plan_enabled' ] ) ) {
            return self::$get_options ;
        }

        $get_options                                 = array() ;
        $get_options[ 'order_payment_plan_enabled' ] = 'yes' === get_option( SUMO_PP_PLUGIN_PREFIX . 'enable_order_payment_plan' , 'no' ) ? true : false ;

        if(
                ! $get_options[ 'order_payment_plan_enabled' ] ||
                ! _sumo_pp_current_user_can_purchase_payment( array(
                    'limit_by'            => get_option( SUMO_PP_PLUGIN_PREFIX . 'show_order_payment_plan_for' , 'all_users' ) ,
                    'filtered_users'      => ( array ) get_option( SUMO_PP_PLUGIN_PREFIX . 'get_limited_users_of_order_payment_plan' ) ,
                    'filtered_user_roles' => ( array ) get_option( SUMO_PP_PLUGIN_PREFIX . 'get_limited_userroles_of_order_payment_plan' ) ,
                ) )
        ) {
            return ;
        }

        $get_options[ 'product_type' ]          = 'order' ;
        $get_options[ 'payment_type' ]          = get_option( SUMO_PP_PLUGIN_PREFIX . 'order_payment_type' , 'pay-in-deposit' ) ;
        $get_options[ 'apply_global_settings' ] = 'yes' === get_option( SUMO_PP_PLUGIN_PREFIX . 'apply_global_settings_for_order_payment_plan' , 'no' ) ;
        $get_options[ 'force_deposit' ]         = $get_options[ 'apply_global_settings' ] ? ('payment-plans' === $get_options[ 'payment_type' ] ? get_option( SUMO_PP_PLUGIN_PREFIX . 'force_payment_plan' , 'no' ) : get_option( SUMO_PP_PLUGIN_PREFIX . 'force_deposit' , 'no' )) : get_option( SUMO_PP_PLUGIN_PREFIX . 'force_order_payment_plan' , 'no' ) ;

        if( 'pay-in-deposit' === $get_options[ 'payment_type' ] ) {
            $get_options[ 'deposit_type' ] = $get_options[ 'apply_global_settings' ] ? get_option( SUMO_PP_PLUGIN_PREFIX . 'deposit_type' , 'pre-defined' ) : get_option( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_deposit_type' , 'pre-defined' ) ;

            if( 'user-defined' === $get_options[ 'deposit_type' ] ) {
                $get_options[ 'user_defined_deposit_type' ] = $get_options[ 'apply_global_settings' ] ? 'percent-of-product-price' : get_option( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_user_defined_deposit_type' , 'percent-of-product-price' ) ;

                if( 'fixed-price' === $get_options[ 'user_defined_deposit_type' ] && ! $get_options[ 'apply_global_settings' ] ) {
                    $get_options[ 'min_user_defined_deposit_price' ] = floatval( get_option( SUMO_PP_PLUGIN_PREFIX . 'min_order_payment_plan_user_defined_deposit_price' ) ) ;
                    $get_options[ 'max_user_defined_deposit_price' ] = floatval( get_option( SUMO_PP_PLUGIN_PREFIX . 'max_order_payment_plan_user_defined_deposit_price' ) ) ;
                } else {
                    $get_options[ 'min_deposit' ] = $get_options[ 'apply_global_settings' ] ? floatval( get_option( SUMO_PP_PLUGIN_PREFIX . 'min_deposit' , '0.01' ) ) : floatval( get_option( SUMO_PP_PLUGIN_PREFIX . 'min_order_payment_plan_deposit' , '0.01' ) ) ;
                    $get_options[ 'max_deposit' ] = $get_options[ 'apply_global_settings' ] ? floatval( get_option( SUMO_PP_PLUGIN_PREFIX . 'max_deposit' , '99.99' ) ) : floatval( get_option( SUMO_PP_PLUGIN_PREFIX . 'max_order_payment_plan_deposit' , '99.99' ) ) ;
                }
            } else {
                $get_options[ 'deposit_price_type' ]    = 'percent-of-product-price' ;
                $get_options[ 'fixed_deposit_percent' ] = $get_options[ 'apply_global_settings' ] ? floatval( get_option( SUMO_PP_PLUGIN_PREFIX . 'fixed_deposit_percent' , '50' ) ) : floatval( get_option( SUMO_PP_PLUGIN_PREFIX . 'fixed_order_payment_plan_deposit_percent' , '50' ) ) ;
            }
            if( $get_options[ 'apply_global_settings' ] ) {
                $get_options[ 'pay_balance_type' ]  = 'after' ;
                $get_options[ 'pay_balance_after' ] = false === get_option( SUMO_PP_PLUGIN_PREFIX . 'balance_payment_due' ) ? absint( get_option( SUMO_PP_PLUGIN_PREFIX . 'pay_balance_after' ) ) : absint( get_option( SUMO_PP_PLUGIN_PREFIX . 'balance_payment_due' ) ) ;
            } else {
                $get_options[ 'pay_balance_type' ] = get_option( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_pay_balance_type' , 'after' ) ;

                if( 'after' === $get_options[ 'pay_balance_type' ] ) {
                    $get_options[ 'pay_balance_after' ] = absint( get_option( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_pay_balance_after' ) ) ;
                } else {
                    $get_options[ 'pay_balance_before' ] = get_option( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_pay_balance_before' ) ;

                    if( _sumo_pp_get_timestamp( $get_options[ 'pay_balance_before' ] ) <= _sumo_pp_get_timestamp( 0 , 0 , true ) ) {
                        return ;
                    }
                }
            }
        } else if( 'payment-plans' === $get_options[ 'payment_type' ] ) {
            $get_options[ 'selected_plans' ] = $get_options[ 'apply_global_settings' ] ? get_option( SUMO_PP_PLUGIN_PREFIX . 'selected_plans' , array() ) : get_option( SUMO_PP_PLUGIN_PREFIX . 'selected_plans_for_order_payment_plan' , array() ) ;
            $get_options[ 'selected_plans' ] = is_array( $get_options[ 'selected_plans' ] ) ? $get_options[ 'selected_plans' ] : array() ;
        }

        $get_options[ 'apply_global_settings' ] = $get_options[ 'apply_global_settings' ] ? 'yes' : 'no' ;
        $get_options[ 'min_order_total' ]       = get_option( SUMO_PP_PLUGIN_PREFIX . 'min_order_total_to_display_order_payment_plan' ) ;
        $get_options[ 'labels' ]                = array(
            'enable'         => get_option( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_label' ) ,
            'deposit_amount' => get_option( SUMO_PP_PLUGIN_PREFIX . 'pay_a_deposit_amount_label' ) ,
            'payment_plans'  => get_option( SUMO_PP_PLUGIN_PREFIX . 'pay_with_payment_plans_label' ) ,
                ) ;

        self::$get_options = wp_parse_args( ( array ) apply_filters( 'sumopaymentplans_get_order_props' , $get_options ) , self::get_default_props( self::$get_options ) ) ;
    }

    public static function can_user_deposit_payment() {
        if( is_bool( self::$can_user_deposit_payment ) ) {
            return self::$can_user_deposit_payment ;
        }

        if(
                isset( WC()->cart->cart_contents ) &&
                self::$get_options[ 'order_payment_plan_enabled' ] &&
                (
                ! is_numeric( self::$get_options[ 'min_order_total' ] ) ||
                'woocommerce_cart_get_total' === current_filter() ||
                (self::get_total_payable_amount() >= floatval( self::$get_options[ 'min_order_total' ] ) )
                )
        ) {
            self::$can_user_deposit_payment = true ;

            foreach( WC()->cart->cart_contents as $cart_item ) {
                if( empty( $cart_item[ 'product_id' ] ) ) {
                    continue ;
                }
                $product_id = $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ] ;

                if( ! empty( $cart_item[ 'sumopaymentplans' ][ 'payment_product_props' ][ 'payment_type' ] ) ) {
                    return self::$can_user_deposit_payment = false ;
                } else if( class_exists( 'SUMOSubscriptions' ) && function_exists( 'sumo_is_subscription_product' ) && sumo_is_subscription_product( $product_id ) ) {
                    return self::$can_user_deposit_payment = false ;
                } else if( class_exists( 'SUMOMemberships' ) && function_exists( 'sumo_is_membership_product' ) && sumo_is_membership_product( $product_id ) ) {
                    return self::$can_user_deposit_payment = false ;
                }
            }
        } else {
            self::$can_user_deposit_payment = false ;
        }
        return self::$can_user_deposit_payment ;
    }

    public static function is_enabled() {
        if( ! self::can_user_deposit_payment() ) {
            return false ;
        }

        self::get_order_props() ;
        return 'order' === self::$order_props[ 'product_type' ] ;
    }

    public static function get_prop( $context , $props = array() ) {

        if( empty( $props ) ) {
            if( ! empty( WC()->cart->sumopaymentplans[ 'order' ] ) ) {
                $props = WC()->cart->sumopaymentplans[ 'order' ] ;
            }
        }

        if( ! is_array( $props ) || empty( $props[ 'payment_type' ] ) ) {
            return null ;
        }

        $props = wp_parse_args( $props , self::get_default_props( self::$order_props ) ) ;

        switch( $props[ 'payment_type' ] ) {
            case 'payment-plans':
                $prop = SUMO_PP_Payment_Plan_Manager::get_prop( $context , array(
                            'props'         => $props[ 'payment_plan_props' ] ,
                            'product_price' => $props[ 'product_price' ] ,
                        ) ) ;

                if( is_null( $prop ) ) {
                    if( isset( $props[ $context ] ) ) {
                        return $props[ $context ] ;
                    }
                } else {
                    return $prop ;
                }
                break ;
            case 'pay-in-deposit':
                switch( $context ) {
                    case 'total_payable':
                        return $props[ 'product_price' ] ;
                    case 'balance_payable':
                        return max( $props[ 'down_payment' ] , $props[ 'product_price' ] ) - min( $props[ 'down_payment' ] , $props[ 'product_price' ] ) ;
                    case 'next_payment_on':
                        if( 'before' === $props[ 'pay_balance_type' ] ) {
                            return _sumo_pp_get_date( $props[ 'pay_balance_before' ] ) ;
                        } else {
                            $pay_balance_after = $props[ 'pay_balance_after' ] ; //in days
                            return $pay_balance_after > 0 && 'after_admin_approval' !== $props[ 'activate_payment' ] ? _sumo_pp_get_date( "+{$pay_balance_after} days" ) : '' ;
                        }
                    default :
                        if( isset( $props[ $context ] ) ) {
                            return $props[ $context ] ;
                        }
                }
                break ;
        }
        return null ;
    }

    public static function get_order_props( $from_session = true ) {
        $order_props = null ;

        if( $from_session ) {
            if( ! empty( WC()->cart->sumopaymentplans[ 'order' ] ) ) {
                $order_props = WC()->cart->sumopaymentplans[ 'order' ] ;
            }
        } else {
            $order_props = self::$order_props ;
        }

        return self::$order_props = wp_parse_args( is_array( $order_props ) ? $order_props : array() , self::get_default_props( self::$order_props ) ) ;
    }

    public static function get_total_payable_amount( $props = null ) {
        if( isset( $props[ 'product_price' ] ) ) {
            return floatval( $props[ 'product_price' ] ) ;
        }

        remove_filter( 'woocommerce_cart_get_total' , __CLASS__ . '::set_cart_total' , 999 , 1 ) ;
        $cart_total = WC()->cart->get_total( '' ) ;
        add_filter( 'woocommerce_cart_get_total' , __CLASS__ . '::set_cart_total' , 999 , 1 ) ;

        return floatval( $cart_total ) ;
    }

    public static function get_fixed_deposit_amount( $props = null ) {
        if( is_null( $props ) ) {
            $props = self::$get_options ;
        }

        if(
                'pay-in-deposit' === $props[ 'payment_type' ] &&
                'pre-defined' === $props[ 'deposit_type' ]
        ) {
            if( $cart_total = self::get_total_payable_amount( $props ) ) {
                return ($cart_total * floatval( $props[ 'fixed_deposit_percent' ] )) / 100 ;
            }
        }
        return 0 ;
    }

    public static function get_user_defined_deposit_amount_range( $props = null ) {
        if( is_null( $props ) ) {
            $props = self::$get_options ;
        }

        $min_amount = $max_amount = 0 ;
        if(
                'pay-in-deposit' === $props[ 'payment_type' ] &&
                'user-defined' === $props[ 'deposit_type' ]
        ) {
            if( $cart_total = self::get_total_payable_amount() ) {
                if( 'fixed-price' === $props[ 'user_defined_deposit_type' ] ) {
                    $min_amount = $props[ 'min_user_defined_deposit_price' ] ;
                    $max_amount = $props[ 'max_user_defined_deposit_price' ] ;
                } else {
                    $min_amount = ($cart_total * floatval( $props[ 'min_deposit' ] )) / 100 ;
                    $max_amount = ($cart_total * floatval( $props[ 'max_deposit' ] )) / 100 ;
                }
            }
        }
        return array(
            'min' => round( $min_amount , 2 ) ,
            'max' => round( $max_amount , 2 ) ,
                ) ;
    }

    public static function render_plan_selector() {
        if( ! self::can_user_deposit_payment() ) {
            return ;
        }

        if( in_array( self::$get_options[ 'payment_type' ] , array( 'pay-in-deposit' , 'payment-plans' ) ) ) {
            include 'views/html-order-payment-plan-form.php' ;
        }
    }

    public static function get_payment_info_to_display( $order_props , $context = 'default' ) {
        if( ! empty( $order_props[ 'payment_type' ] ) ) {
            $payment_data = $order_props ;
        }

        if( empty( $payment_data ) ) {
            return '' ;
        }

        $shortcodes = _sumo_pp_get_shortcodes_from_cart_r_checkout( $payment_data ) ;

        $info = '' ;
        switch( $context ) {
            case 'balance_payable':
                $info = str_replace( $shortcodes[ 'find' ] , $shortcodes[ 'replace' ] , get_option( SUMO_PP_PLUGIN_PREFIX . 'balance_payable_label' ) ) ;
                break ;
            default :
                if( 'payment-plans' === $payment_data[ 'payment_type' ] ) {
                    $label = get_option( SUMO_PP_PLUGIN_PREFIX . 'payment_plan_label' ) ;

                    if( $label && false === strpos( $label , '[' ) && false === strpos( $label , ']' ) ) {
                        $info .= sprintf( __( '<p><strong>%s</strong> <br>%s</p>' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $label , $shortcodes[ 'content' ][ '[sumo_pp_payment_plan_name]' ] ) ;
                    } else {
                        $info .= str_replace( $shortcodes[ 'find' ] , $shortcodes[ 'replace' ] , $label ) ;
                    }

                    if( $shortcodes[ 'content' ][ '[sumo_pp_payment_plan_desc]' ] ) {
                        $info .= str_replace( $shortcodes[ 'find' ] , $shortcodes[ 'replace' ] , get_option( SUMO_PP_PLUGIN_PREFIX . 'payment_plan_desc_label' ) ) ;
                    }

                    $label = get_option( SUMO_PP_PLUGIN_PREFIX . 'next_payment_date_label' ) ;
                    if( 'enabled' === $payment_data[ 'payment_plan_props' ][ 'sync' ] && $payment_data[ 'down_payment' ] <= 0 ) {
                        $label = get_option( SUMO_PP_PLUGIN_PREFIX . 'first_payment_on_label' ) ;
                    }
                } else {
                    $label = get_option( SUMO_PP_PLUGIN_PREFIX . 'next_payment_date_label' ) ;
                    if( 'before' === $payment_data[ 'pay_balance_type' ] ) {
                        $label = get_option( SUMO_PP_PLUGIN_PREFIX . 'balance_payment_due_date_label' ) ;
                    }
                }

                $info .= str_replace( $shortcodes[ 'find' ] , $shortcodes[ 'replace' ] , get_option( SUMO_PP_PLUGIN_PREFIX . 'total_payable_label' ) ) ;

                if( 'payment-plans' === $payment_data[ 'payment_type' ] ) {
                    $info .= str_replace( $shortcodes[ 'find' ] , $shortcodes[ 'replace' ] , get_option( SUMO_PP_PLUGIN_PREFIX . 'next_installment_amount_label' ) ) ;
                }

                if( $shortcodes[ 'content' ][ '[sumo_pp_next_payment_date]' ] ) {
                    if( $label && false === strpos( $label , '[' ) && false === strpos( $label , ']' ) ) {
                        $info .= sprintf( __( '<br><small style="color:#777;">%s <strong>%s</strong></small>' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $label , $shortcodes[ 'content' ][ '[sumo_pp_next_payment_date]' ] ) ;
                    } else {
                        $info .= str_replace( $shortcodes[ 'find' ] , $shortcodes[ 'replace' ] , $label ) ;
                    }
                }
        }
        return $info ;
    }

    public static function render_payment_info() {
        if( ! self::is_enabled() ) {
            return ;
        }

        include 'views/html-order-payment-plan-info.php' ;
    }

    public static function set_order_props( $args ) {
        self::get_option_props() ;

        if( ! self::$get_options[ 'order_payment_plan_enabled' ] ) {
            return false ;
        }

        $args = wp_parse_args( $args , array(
            'order_items'      => array() ,
            'plan_props'       => null ,
            'down_payment'     => null ,
            'order_total'      => 0 ,
            'deposited_amount' => 0 ,
                ) ) ;

        $order_props                       = array() ;
        $order_props[ 'product_type' ]     = 'order' ;
        $order_props[ 'product_price' ]    = $args[ 'order_total' ] ;
        $order_props[ 'product_qty' ]      = 1 ;
        $order_props[ 'order_items' ]      = $args[ 'order_items' ] ;
        $order_props[ 'activate_payment' ] = get_option( SUMO_PP_PLUGIN_PREFIX . 'activate_payments' , 'auto' ) ;

        foreach( self::$get_options as $option => $option_val ) {
            if( in_array( $option , array( 'order_payment_plan_enabled' , 'labels' , 'min_order_total' ) ) ) {
                continue ;
            }
            $order_props[ $option ] = $option_val ;
        }

        if( is_numeric( $args[ 'plan_props' ] ) ) {
            $order_props[ 'payment_plan_props' ] = SUMO_PP_Payment_Plan_Manager::get_props( $args[ 'plan_props' ] ) ;
        }

        if( is_numeric( $args[ 'down_payment' ] ) ) {
            $order_props[ 'down_payment' ] = $args[ 'down_payment' ] ;
        } else {
            if( 'payment-plans' === $order_props[ 'payment_type' ] ) {
                if( empty( $order_props[ 'payment_plan_props' ][ 'payment_schedules' ] ) ) {
                    return false ;
                }

                $order_props[ 'down_payment' ] = self::get_prop( 'down_payment' , $order_props ) ;
            } else {
                $order_props[ 'down_payment' ] = 'user-defined' === $order_props[ 'deposit_type' ] ? floatval( $args[ 'deposited_amount' ] ) : self::get_fixed_deposit_amount( $order_props ) ;
            }
        }

        $order_props[ 'next_payment_date' ]        = self::get_prop( 'next_payment_on' , $order_props ) ;
        $order_props[ 'next_installment_amount' ]  = self::get_prop( 'next_installment_amount' , $order_props ) ;
        $order_props[ 'total_payable_amount' ]     = self::get_prop( 'total_payable' , $order_props ) ;
        $order_props[ 'remaining_payable_amount' ] = self::get_prop( 'balance_payable' , $order_props ) ;
        self::$order_props                         = wp_parse_args( $order_props , self::get_default_props( self::$order_props ) ) ;
        return true ;
    }

    public static function get_payment_from_session() {
        if( ! did_action( 'woocommerce_loaded' ) || ! isset( WC()->cart ) ) {
            return ;
        }

        if( ! self::can_user_deposit_payment() ) {
            return ;
        }

        WC()->cart->sumopaymentplans = array() ;

        if( 'yes' !== WC()->session->get( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_enabled' ) ) {
            return ;
        }

        $props = array(
            'plan_props'       => WC()->session->get( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_chosen_payment_plan' ) ,
            'deposited_amount' => WC()->session->get( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_deposited_amount' ) ,
            'order_total'      => self::get_total_payable_amount() ,
                ) ;

        foreach( WC()->cart->cart_contents as $item ) {
            if( empty( $item[ 'product_id' ] ) ) {
                continue ;
            }

            $item_id                            = $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ] ;
            $props[ 'order_items' ][ $item_id ] = array(
                'price'             => $item[ 'data' ]->get_price() ,
                'qty'               => $item[ 'quantity' ] ,
                'line_subtotal'     => $item[ 'line_subtotal' ] ,
                'line_subtotal_tax' => $item[ 'line_subtotal_tax' ] ,
                'line_total'        => $item[ 'line_total' ] ,
                'line_tax'          => $item[ 'line_tax' ] ,
                    ) ;
        }

        if( self::set_order_props( $props ) ) {
            WC()->cart->sumopaymentplans[ 'order' ] = self::get_order_props( false ) ;
        }
    }

    public static function set_cart_total( $total ) {
        if( is_checkout() && self::is_enabled() ) {
            $total = self::$order_props[ 'down_payment' ] ;
        }
        return $total ;
    }

    public static function set_total_payable_amount( $total ) {
        if(
                is_checkout() &&
                self::can_user_deposit_payment() &&
                isset( self::$order_props[ 'product_type' ] ) &&
                'order' === self::$order_props[ 'product_type' ]
        ) {
            $total = wc_price( self::$order_props[ 'total_payable_amount' ] ) ;
        }
        return $total ;
    }

    public static function validate_checkout( $data , $errors = '' ) {
        if( empty( $errors ) || ! self::is_enabled() || 'pay-in-deposit' !== self::$order_props [ 'payment_type' ] ) {
            return ;
        }

        if( ! is_numeric( self::$order_props [ 'down_payment' ] ) ) {
            $errors->add( 'required-field' , sprintf( __( '<strong>%s</strong> is a required field.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , self::$get_options[ 'labels' ][ 'deposit_amount' ] ) ) ;
        } else if( 'user-defined' === self::$order_props [ 'deposit_type' ] ) {
            $deposit_amount = self::get_user_defined_deposit_amount_range() ;

            if( $deposit_amount[ 'max' ] ) {
                if( self::$order_props [ 'down_payment' ] < $deposit_amount[ 'min' ] || self::$order_props [ 'down_payment' ] > $deposit_amount[ 'max' ] ) {
                    $errors->add( 'required-field' , sprintf( __( 'Deposited amount should be between <strong>%s</strong> and <strong>%s</strong>.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , wc_price( $deposit_amount[ 'min' ] ) , wc_price( $deposit_amount[ 'max' ] ) ) ) ;
                }
            } else {
                if( self::$order_props [ 'down_payment' ] < $deposit_amount[ 'min' ] ) {
                    $errors->add( 'required-field' , sprintf( __( 'Deposit amount should not be less than <strong>%s</strong>.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , wc_price( $deposit_amount[ 'min' ] ) ) ) ;
                }
            }
        }
    }

    public static function add_order_items( $order_id , $posted ) {
        if( empty( self::$order_props[ 'product_type' ] ) || 'order' !== self::$order_props[ 'product_type' ] ) {
            return ;
        }

        $payment_order = _sumo_pp_get_order( $order_id ) ;
        $payment_order->order->remove_order_items( 'line_item' ) ;

        $order_item_data = array() ;
        foreach( WC()->cart->get_cart() as $item ) {
            if( empty( $item[ 'data' ] ) ) {
                continue ;
            }

            $order_item_data[] = array( 'product' => $item[ 'data' ] , 'order_item' => $item ) ;
        }

        if( empty( $order_item_data ) ) {
            return ;
        }

        $item_data = current( $order_item_data ) ;

        self::add_items_to_order( $payment_order , $item_data[ 'product' ] , array(
            'order_item_data' => $order_item_data ,
        ) ) ;
    }

    public static function add_items_to_order( $payment_order , $product , $args = array() ) {
        $args = wp_parse_args( $args , array(
            'order_props'      => self::$order_props ,
            'line_total'       => $payment_order->order->get_total() ,
            'order_item_data'  => array() ,
            'add_payment_meta' => true ,
                ) ) ;

        $item_id = $payment_order->order->add_product( false , 1 , array(
            'name'      => get_option( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_label' ) ,
            'variation' => array() ,
            'subtotal'  => wc_get_price_excluding_tax( $product , array(
                'qty'   => 1 ,
                'price' => wc_format_decimal( $args[ 'line_total' ] ) ,
            ) ) ,
            'total'     => wc_get_price_excluding_tax( $product , array(
                'qty'   => 1 ,
                'price' => wc_format_decimal( $args[ 'line_total' ] ) ,
            ) ) ,
                ) ) ;

        if( ! $item_id || is_wp_error( $item_id ) ) {
            return 0 ;
        }

        if( ! empty( $args[ 'order_item_data' ] ) ) {
            foreach( $args[ 'order_item_data' ] as $item_data ) {
                if( $item_data[ 'product' ]->is_visible() ) {
                    $product_name = sprintf( '<a href="%s">%s</a>' , esc_url( $item_data[ 'product' ]->get_permalink() ) , $item_data[ 'product' ]->get_name() ) ;
                } else {
                    $product_name = $item_data[ 'product' ]->get_name() ;
                }

                wc_add_order_item_meta( $item_id , $product_name , '&nbsp;x' . (is_array( $item_data[ 'order_item' ] ) ? $item_data[ 'order_item' ][ 'quantity' ] : $item_data[ 'order_item' ]->get_quantity() ) ) ;

                if( $item_data[ 'product' ]->is_type( 'variation' ) ) {
                    foreach( $item_data[ 'product' ]->get_attributes() as $key => $value ) {
                        wc_add_order_item_meta( $item_id , str_repeat( '&nbsp;' , 7 ) . str_replace( 'attribute_' , '' , $key ) , $value ) ;
                    }
                }
            }
        }

        if( $args[ 'add_payment_meta' ] ) {
            SUMO_PP_Order_Item_Manager::add_order_item_payment_meta( $item_id , $args[ 'order_props' ] ) ;
        }
        return $item_id ;
    }

    public static function render_order_item_meta_key( $display_key , $meta , $order_item ) {
        $maybe_payment_order = _sumo_pp_get_order( $order_item->get_order_id() ) ;

        if( $maybe_payment_order && $maybe_payment_order->contains_product_type( 'order' ) ) {
            return $meta->key ;
        }
        return $display_key ;
    }

    public static function render_paid_now( $total_rows , $order , $tax_display = '' ) {
        $maybe_payment_order = _sumo_pp_get_order( $order ) ;

        if( ! $maybe_payment_order || ! $maybe_payment_order->is_parent() ) {
            return $total_rows ;
        }

        $down_payment         = null ;
        $total_payable_amount = null ;

        if( $payment_data = $maybe_payment_order->contains_product_type( 'order' ) ) {
            $down_payment         = $payment_data[ 'down_payment' ] ;
            $total_payable_amount = $payment_data[ 'total_payable_amount' ] ;
        } else if( $maybe_payment_order->is_payment_order() ) {
            //BKWD CMPT < 3.1
            $payment = $maybe_payment_order->has_payment_product() ;

            if( $payment && 'order' === $payment->get_product_type() ) {
                if( 'payment-plans' === $payment->get_payment_type() ) {
                    $down_payment = (floatval( $payment->get_prop( 'initial_payment' ) ) * $payment->get_total_payable_amount()) / 100 ;
                } else {
                    $down_payment = $payment->get_down_payment( false ) ;
                }
                $total_payable_amount = $payment->get_total_payable_amount() ;
            }
        }

        if( is_numeric( $down_payment ) ) {
            $total_rows[ 'order_total' ][ 'value' ]                      = wc_price( $total_payable_amount , array( 'currency' => $maybe_payment_order->get_currency() ) ) ;
            $total_rows[ SUMO_PP_PLUGIN_PREFIX . 'paid_now' ][ 'label' ] = __( 'Paid Now' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ;
            $total_rows[ SUMO_PP_PLUGIN_PREFIX . 'paid_now' ][ 'value' ] = wc_price( $down_payment , array( 'currency' => $maybe_payment_order->get_currency() ) ) ;
        }
        return $total_rows ;
    }

    public static function grant_product_download_permissions( $order_id ) {
        $maybe_payment_order = _sumo_pp_get_order( $order_id ) ;

        if( ! $maybe_payment_order || 1 !== count( $maybe_payment_order->order->get_items() ) ) {
            return ;
        }

        if( ! $payment_data = $maybe_payment_order->contains_product_type( 'order' ) ) {
            return ;
        }

        if( empty( $payment_data[ 'order_items' ] ) ) {
            return ;
        }

        foreach( $payment_data[ 'order_items' ] as $product_id => $data ) {
            if( ! $product = wc_get_product( $product_id ) ) {
                continue ;
            }

            if( $product && $product->exists() && $product->is_downloadable() ) {
                $downloads = $product->get_downloads() ;

                foreach( array_keys( $downloads ) as $download_id ) {
                    wc_downloadable_file_permission( $download_id , $product , $maybe_payment_order->order , $data[ 'qty' ] ) ;
                }
            }
        }
    }

}
