<?php

if( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Manage payment product.
 * 
 * @class SUMO_PP_Product_Manager
 * @category Class
 */
class SUMO_PP_Product_Manager {

    protected static $product_props = array(
        'product_id'                     => null ,
        'product_price'                  => null ,
        'product_type'                   => null ,
        'payment_type'                   => null ,
        'apply_global_settings'          => null ,
        'force_deposit'                  => null ,
        'deposit_type'                   => null ,
        'deposit_price_type'             => null ,
        'fixed_deposit_price'            => null ,
        'fixed_deposit_percent'          => null ,
        'user_defined_deposit_type'      => null ,
        'min_user_defined_deposit_price' => null ,
        'max_user_defined_deposit_price' => null ,
        'min_deposit'                    => null ,
        'max_deposit'                    => null ,
        'pay_balance_type'               => null ,
        'pay_balance_after'              => null ,
        'pay_balance_before'             => null ,
        'set_expired_deposit_payment_as' => null ,
        'selected_plans'                 => null ,
            ) ;

    /**
     * The single instance of the class.
     */
    protected static $instance = null ;

    /**
     * Create instance for SUMO_PP_Product_Manager.
     */
    public static function instance() {
        if( is_null( self::$instance ) ) {
            self::$instance = new self() ;
        }
        return self::$instance ;
    }

    /**
     * Construct SUMO_PP_Product_Manager.
     */
    public function __construct() {
        add_filter( 'woocommerce_product_add_to_cart_text' , __CLASS__ . '::alter_add_to_cart_text' , 10 , 2 ) ;
        add_filter( 'woocommerce_loop_add_to_cart_args' , __CLASS__ . '::prevent_ajax_add_to_cart' , 10 , 2 ) ;
        add_filter( 'woocommerce_product_add_to_cart_url' , __CLASS__ . '::redirect_to_single_product' , 10 , 2 ) ;

        add_action( 'woocommerce_before_add_to_cart_button' , __CLASS__ . '::render_payment_type_fields' , 10 ) ;

        add_action( 'aram_one' , __CLASS__ . '::render_payment_type_fields' , 10 ) ;

        add_filter( 'sumopaymentplans_get_single_variation_data_to_display' , __CLASS__ . '::render_variation_payment_type_fields' , 9 , 2 ) ;
        add_action( 'woocommerce_before_variations_form' , __CLASS__ . '::render_variation_payment_type_fields' , 10 ) ;
        add_action( 'woocommerce_before_single_variation' , __CLASS__ . '::render_variation_payment_type_fields' , 10 ) ;
        add_action( 'woocommerce_after_single_variation' , __CLASS__ . '::render_variation_payment_type_fields' , 10 ) ;

        add_filter( 'woocommerce_product_is_in_stock' , __CLASS__ . '::check_product_is_in_stock' , 99 , 2 ) ;
        add_action( 'wp_head' , __CLASS__ . '::add_custom_style' , 99 ) ;




        add_action( 'aram_four' , __CLASS__ . '::render_payment_type_fields' , 10 ) ;
        //add_action( 'aramthings' , __CLASS__ . '::render_variation_payment_type_fields' , 10 ) ;
        //add_action( 'aramthings' , __CLASS__ . '::render_variation_payment_type_fields' , 10 ) ;


    }



    // ARAM ADDED BELOW //
    public static function render_aram() {
        echo "<h1>HELLO</h1>";
    }
    // ARAM ADDED ABOVE //



    public static function get_default_props() {
        return array_map( '__return_null' , self::$product_props ) ;
    }

    public static function get_product_props( $product , $user_id = null ) {
        if( ! _sumo_pp_current_user_can_purchase_payment( array() , $user_id ) ) {
            return self::get_default_props() ;
        }

        $product = wc_get_product( $product ) ;

        if( ! $product || 'yes' !== get_post_meta( $product->get_id() , SUMO_PP_PLUGIN_PREFIX . 'enable_sumopaymentplans' , true ) ) {
            return self::get_default_props() ;
        }

        $product_props                            = array() ;
        $product_props[ 'product_id' ]            = $product->get_id() ;
        $product_props[ 'payment_type' ]          = get_post_meta( $product_props[ 'product_id' ] , SUMO_PP_PLUGIN_PREFIX . 'payment_type' , true ) ;
        $product_props[ 'apply_global_settings' ] = 'yes' === get_post_meta( $product_props[ 'product_id' ] , SUMO_PP_PLUGIN_PREFIX . 'apply_global_settings' , true ) ;

        if( 'pay-in-deposit' === $product_props[ 'payment_type' ] ) {
            $product_props[ 'deposit_type' ] = $product_props[ 'apply_global_settings' ] ? get_option( SUMO_PP_PLUGIN_PREFIX . 'deposit_type' , 'pre-defined' ) : get_post_meta( $product_props[ 'product_id' ] , SUMO_PP_PLUGIN_PREFIX . 'deposit_type' , true ) ;

            if( 'user-defined' === $product_props[ 'deposit_type' ] ) {
                $product_props[ 'user_defined_deposit_type' ] = $product_props[ 'apply_global_settings' ] ? 'percent-of-product-price' : get_post_meta( $product_props[ 'product_id' ] , SUMO_PP_PLUGIN_PREFIX . 'user_defined_deposit_type' , true ) ;

                if( 'fixed-price' === $product_props[ 'user_defined_deposit_type' ] && ! $product_props[ 'apply_global_settings' ] ) {
                    $product_props[ 'min_user_defined_deposit_price' ] = floatval( get_post_meta( $product_props[ 'product_id' ] , SUMO_PP_PLUGIN_PREFIX . 'min_user_defined_deposit_price' , true ) ) ;
                    $product_props[ 'max_user_defined_deposit_price' ] = floatval( get_post_meta( $product_props[ 'product_id' ] , SUMO_PP_PLUGIN_PREFIX . 'max_user_defined_deposit_price' , true ) ) ;
                } else {
                    $product_props[ 'min_deposit' ] = $product_props[ 'apply_global_settings' ] ? floatval( get_option( SUMO_PP_PLUGIN_PREFIX . 'min_deposit' , '0.01' ) ) : floatval( get_post_meta( $product_props[ 'product_id' ] , SUMO_PP_PLUGIN_PREFIX . 'min_deposit' , true ) ) ;
                    $product_props[ 'max_deposit' ] = $product_props[ 'apply_global_settings' ] ? floatval( get_option( SUMO_PP_PLUGIN_PREFIX . 'max_deposit' , '99.99' ) ) : floatval( get_post_meta( $product_props[ 'product_id' ] , SUMO_PP_PLUGIN_PREFIX . 'max_deposit' , true ) ) ;
                }
            } else {
                $product_props[ 'deposit_price_type' ] = $product_props[ 'apply_global_settings' ] ? 'percent-of-product-price' : get_post_meta( $product_props[ 'product_id' ] , SUMO_PP_PLUGIN_PREFIX . 'deposit_price_type' , true ) ;

                if( 'percent-of-product-price' === $product_props[ 'deposit_price_type' ] ) {
                    $product_props[ 'fixed_deposit_percent' ] = $product_props[ 'apply_global_settings' ] ? floatval( get_option( SUMO_PP_PLUGIN_PREFIX . 'fixed_deposit_percent' , '50' ) ) : floatval( get_post_meta( $product_props[ 'product_id' ] , SUMO_PP_PLUGIN_PREFIX . 'fixed_deposit_percent' , true ) ) ;
                } else {
                    $product_props[ 'fixed_deposit_price' ] = $product_props[ 'apply_global_settings' ] ? null : floatval( get_post_meta( $product_props[ 'product_id' ] , SUMO_PP_PLUGIN_PREFIX . 'fixed_deposit_price' , true ) ) ;
                }
            }
            if( $product_props[ 'apply_global_settings' ] ) {
                $product_props[ 'pay_balance_type' ]  = 'after' ;
                $product_props[ 'pay_balance_after' ] = false === get_option( SUMO_PP_PLUGIN_PREFIX . 'balance_payment_due' ) ? absint( get_option( SUMO_PP_PLUGIN_PREFIX . 'pay_balance_after' ) ) : absint( get_option( SUMO_PP_PLUGIN_PREFIX . 'balance_payment_due' ) ) ;
            } else {
                $product_props[ 'pay_balance_type' ] = '' === get_post_meta( $product_props[ 'product_id' ] , SUMO_PP_PLUGIN_PREFIX . 'pay_balance_type' , true ) ? 'after' : get_post_meta( $product_props[ 'product_id' ] , SUMO_PP_PLUGIN_PREFIX . 'pay_balance_type' , true ) ;

                if( 'after' === $product_props[ 'pay_balance_type' ] ) {
                    $product_props[ 'pay_balance_after' ] = '' === get_post_meta( $product_props[ 'product_id' ] , SUMO_PP_PLUGIN_PREFIX . 'balance_payment_due' , true ) ? absint( get_post_meta( $product_props[ 'product_id' ] , SUMO_PP_PLUGIN_PREFIX . 'pay_balance_after' , true ) ) : absint( get_post_meta( $product_props[ 'product_id' ] , SUMO_PP_PLUGIN_PREFIX . 'balance_payment_due' , true ) ) ;
                } else {
                    $product_props[ 'pay_balance_before' ]             = get_post_meta( $product_props[ 'product_id' ] , SUMO_PP_PLUGIN_PREFIX . 'pay_balance_before' , true ) ;
                    $product_props[ 'set_expired_deposit_payment_as' ] = get_post_meta( $product_props[ 'product_id' ] , SUMO_PP_PLUGIN_PREFIX . 'set_expired_deposit_payment_as' , true ) ;
                }
            }
        } else if( 'payment-plans' === $product_props[ 'payment_type' ] ) {
            $product_props[ 'selected_plans' ] = $product_props[ 'apply_global_settings' ] ? get_option( SUMO_PP_PLUGIN_PREFIX . 'selected_plans' , array() ) : get_post_meta( $product_props[ 'product_id' ] , SUMO_PP_PLUGIN_PREFIX . 'selected_plans' , true ) ;
            $product_props[ 'selected_plans' ] = is_array( $product_props[ 'selected_plans' ] ) ? $product_props[ 'selected_plans' ] : array() ;
        }

        if( 'sale-price' === get_option( SUMO_PP_PLUGIN_PREFIX . 'calc_deposits_r_payment_plans_price_based_on' , 'sale-price' ) ) {
            $product_props[ 'product_price' ] = $product->get_price() ;
        } else {
            $product_props[ 'product_price' ] = $product->get_regular_price() ;
        }

        $product_props[ 'product_type' ]          = $product->get_type() ;
        $product_props[ 'force_deposit' ]         = $product_props[ 'apply_global_settings' ] ? ('payment-plans' === $product_props[ 'payment_type' ] ? get_option( SUMO_PP_PLUGIN_PREFIX . 'force_payment_plan' , 'no' ) : get_option( SUMO_PP_PLUGIN_PREFIX . 'force_deposit' , 'no' )) : get_post_meta( $product_props[ 'product_id' ] , SUMO_PP_PLUGIN_PREFIX . 'force_deposit' , true ) ;
        $product_props[ 'apply_global_settings' ] = $product_props[ 'apply_global_settings' ] ? 'yes' : 'no' ;

        return self::$product_props = wp_parse_args( ( array ) apply_filters( 'sumopaymentplans_get_product_props' , $product_props ) , self::get_default_props() ) ;
    }

    public static function get_cached_props() {
        return self::$product_props ;
    }

    public static function get_prop( $context , $args = array() ) {
        $args = wp_parse_args( $args , array(
            'product_props'    => self::$product_props ,
            'plan_props'       => array() ,
            'deposited_amount' => 0 ,
            'qty'              => 1 ,
                ) ) ;

        if( ! is_array( $args[ 'product_props' ] ) ) {
            $args[ 'product_props' ] = self::get_product_props( $args[ 'product_props' ] ) ;
        }

        if( empty( $args[ 'product_props' ][ 'payment_type' ] ) ) {
            return null ;
        }

        switch( $args[ 'product_props' ][ 'payment_type' ] ) {
            case 'payment-plans':
                $prop = SUMO_PP_Payment_Plan_Manager::get_prop( $context , array(
                            'props'         => $args[ 'plan_props' ] ,
                            'product_price' => $args[ 'product_props' ][ 'product_price' ] ,
                            'qty'           => $args[ 'qty' ] ,
                        ) ) ;

                if( is_null( $prop ) ) {
                    if( isset( $args[ 'product_props' ][ $context ] ) ) {
                        return $args[ 'product_props' ][ $context ] ;
                    }
                } else {
                    return $prop ;
                }
                break ;
            case 'pay-in-deposit':
                switch( $context ) {
                    case 'total_payable':
                        return $args[ 'product_props' ][ 'product_price' ] * $args[ 'qty' ] ;
                    case 'balance_payable':
                        $total_payable    = $args[ 'product_props' ][ 'product_price' ] * $args[ 'qty' ] ;
                        $deposited_amount = floatval( $args[ 'deposited_amount' ] ) * $args[ 'qty' ] ;
                        return max( $deposited_amount , $total_payable ) - min( $deposited_amount , $total_payable ) ;
                    case 'next_payment_on':
                        if( 'before' === $args[ 'product_props' ][ 'pay_balance_type' ] ) {
                            return _sumo_pp_get_date( $args[ 'product_props' ][ 'pay_balance_before' ] ) ;
                        } else {
                            $pay_balance_after = $args[ 'product_props' ][ 'pay_balance_after' ] ; //in days
                            return $pay_balance_after > 0 && 'after_admin_approval' !== get_option( SUMO_PP_PLUGIN_PREFIX . 'activate_payments' , 'auto' ) ? _sumo_pp_get_date( "+{$pay_balance_after} days" ) : '' ;
                        }
                    default :
                        if( isset( $args[ 'product_props' ][ $context ] ) ) {
                            return $args[ 'product_props' ][ $context ] ;
                        }
                }
                break ;
        }
        return null ;
    }

    public static function get_fixed_deposit_amount( $props = null ) {
        if( is_null( $props ) ) {
            $props = self::$product_props ;
        }

        if(
                'pay-in-deposit' === $props[ 'payment_type' ] &&
                'pre-defined' === $props[ 'deposit_type' ]
        ) {
            if( 'fixed-price' === $props[ 'deposit_price_type' ] ) {
                return $props[ 'fixed_deposit_price' ] ;
            }
            if( ! is_null( $props[ 'product_price' ] ) ) {
                return ($props[ 'product_price' ] * $props[ 'fixed_deposit_percent' ]) / 100 ;
            }
        }
        return 0 ;
    }

    public static function get_user_defined_deposit_amount_range( $props = null ) {
        if( is_null( $props ) ) {
            $props = self::$product_props ;
        }

        $min_amount = $max_amount = 0 ;
        if(
                'pay-in-deposit' === $props[ 'payment_type' ] &&
                'user-defined' === $props[ 'deposit_type' ]
        ) {
            if( ! is_null( $props[ 'product_price' ] ) ) {
                if( 'fixed-price' === $props[ 'user_defined_deposit_type' ] ) {
                    $min_amount = $props[ 'min_user_defined_deposit_price' ] ;
                    $max_amount = $props[ 'max_user_defined_deposit_price' ] ;
                } else {
                    $min_amount = ($props[ 'product_price' ] * $props[ 'min_deposit' ]) / 100 ;
                    $max_amount = ($props[ 'product_price' ] * $props[ 'max_deposit' ]) / 100 ;
                }
            }
        }
        return array(
            'min' => round( $min_amount , 2 ) ,
            'max' => round( $max_amount , 2 ) ,
                ) ;
    }

    public static function get_formatted_price( $price ) {
        return '<span class="price">' . wc_price( $price ) . '</span>' ;
    }

    public static function get_payment_type_fields( $props = null , $hide_if_variation = false , $class = '' ) {
        if( is_null( $props ) ) {
            $props = self::$product_props ;
        }

        switch( $props[ 'payment_type' ] ) {
            case 'pay-in-deposit':
                if( 'before' === $props[ 'pay_balance_type' ] ) {
                    if( isset( $props[ 'booking_payment_end_date' ] ) ) {
                        if( $props[ 'booking_payment_end_date' ] && $props[ 'booking_payment_end_date' ] <= _sumo_pp_get_timestamp( 0 , 0 , true ) ) {
                            return ;
                        } else {
                            //display payment deposit fields. may be it is SUMO Booking product
                        }
                    } else if( _sumo_pp_get_timestamp( $props[ 'pay_balance_before' ] ) <= _sumo_pp_get_timestamp( 0 , 0 , true ) ) {
                        return ;
                    }
                }

                ob_start() ;
                include 'views/html-pay-in-deposits.php' ;
                return ob_get_clean() ;
                break ;
            case 'payment-plans':
                ob_start() ;
                include 'views/html-payment-plans.php' ;
                return ob_get_clean() ;
                break ;
        }
        return ;
    }

    public static function is_payment_product( $product_props ) {
        if( ! empty( $product_props ) ) {
            $payment_type = self::get_prop( 'payment_type' , array( 'product_props' => $product_props ) ) ;
        } else {
            $payment_type = self::get_prop( 'payment_type' ) ;
        }
        return in_array( $payment_type , array( 'pay-in-deposit' , 'payment-plans' ) ) ;
    }

    public static function alter_add_to_cart_text( $text , $product ) {
        if( self::is_payment_product( $product ) && ! in_array( self::$product_props[ 'product_type' ] , array( 'variable' , 'variation' ) ) ) {
            return get_option( SUMO_PP_PLUGIN_PREFIX . 'add_to_cart_label' ) ;
        }
        return $text ;
    }

    public static function prevent_ajax_add_to_cart( $args , $product ) {
        if( isset( $args[ 'class' ] ) && self::is_payment_product( $product ) ) {
            $args[ 'class' ] = str_replace( 'ajax_add_to_cart' , '' , $args[ 'class' ] ) ;
        }
        return $args ;
    }

    public static function redirect_to_single_product( $add_to_cart_url , $product ) {

        if( is_shop() || is_product_category() ) {
            if( self::is_payment_product( $product ) ) {
                return get_permalink( self::get_prop( 'product_id' ) ) ;
            }
        }
        return $add_to_cart_url ;
    }

    public static function add_custom_style() {
        ob_start() ;
        echo '<style type="text/css">' . get_option( SUMO_PP_PLUGIN_PREFIX . 'custom_css' ) . '</style>' ;
        ob_get_contents() ;
    }

    public static function render_payment_type_fields() {
        global $product ;

        if(
                is_product() &&
                is_callable( array( $product , 'is_type' ) ) &&
                ! $product->is_type( array( 'variable' , 'variation' ) ) &&
                self::is_payment_product( $product )
        ) {
            echo self::get_payment_type_fields() ;
        }
    }

    public static function render_variation_payment_type_fields( $data = array() , $variation = null ) {
        global $product ;

        if( 'sumopaymentplans_get_single_variation_data_to_display' === current_filter() ) {
            if( $variation && $variation->exists() && self::is_payment_product( $variation ) ) {
                $data[ 'payment_type_fields' ] = self::get_payment_type_fields() ;
            }
            return $data ;
        } else if( doing_action( 'woocommerce_before_variations_form' ) ) {
            $children = $product->get_visible_children() ;

            if( ! empty( $children ) ) {
                $variation_data = array() ;

                foreach( $children as $child_id ) {
                    $product_variation = new WC_Product_Variation( $child_id ) ;
                    if( $product_variation->exists() && $product_variation->variation_is_visible() ) {
                        $_variation_data = apply_filters( 'sumopaymentplans_get_single_variation_data_to_display' , array() , $product_variation ) ;

                        if( ! empty( $_variation_data ) ) {
                            $variation_data[ $child_id ] = $_variation_data ;
                        }
                    }
                }

                if( ! empty( $variation_data ) ) {
                    $variations   = wp_json_encode( array_keys( $variation_data ) ) ;
                    $hidden_field = "<input type='hidden' id='" . SUMO_PP_PLUGIN_PREFIX . "single_variations'" ;
                    $hidden_field .= "data-variations='{$variations}'" ;
                    $hidden_field .= "/>" ;
                    $hidden_field .= "<input type='hidden' id='" . SUMO_PP_PLUGIN_PREFIX . "single_variation_data'" ;
                    foreach( $variation_data as $variation_id => $data ) {
                        foreach( $data as $key => $message ) {
                            $message = htmlspecialchars( $message , ENT_QUOTES , 'UTF-8' ) ;
                            $hidden_field .= "data-{$key}_{$variation_id}='{$message}'" ;
                        }
                    }
                    $hidden_field .= "/>" ;
                    echo $hidden_field ;
                }
            }
        } else if( doing_action( 'woocommerce_before_single_variation' ) ) {
            echo '<span id="' . SUMO_PP_PLUGIN_PREFIX . 'before_single_variation"></span>' ;
        } else {
            echo '<span id="' . SUMO_PP_PLUGIN_PREFIX . 'after_single_variation"></span>' ;
        }
    }

    public static function check_product_is_in_stock( $is_in_stock , $product = '' ) {
        if( 'set-as-out-of-stock' === SUMO_PP_Payment_Plan_Manager::when_plans_are_hidden() ) {
            remove_filter( 'sumopaymentplans_get_product_props' , 'SUMO_PP_Payment_Plan_Manager::contains_valid_plan' , 99 ) ;
            self::get_product_props( $product ) ;
            add_filter( 'sumopaymentplans_get_product_props' , 'SUMO_PP_Payment_Plan_Manager::contains_valid_plan' , 99 ) ;

            if( 'payment-plans' === self::get_prop( 'payment_type' ) ) {
                $selected_plans  = self::get_prop( 'selected_plans' ) ;
                $payment_started = $payment_ended   = array() ;

                foreach( $selected_plans as $col => $plans ) {
                    foreach( $plans as $row => $plan_id ) {
                        if( SUMO_PP_Payment_Plan_Manager::when_plans_are_hidden() ) {
                            $payment_started[ $plan_id ] = SUMO_PP_Payment_Plan_Manager::get_prop( 'payment_started' , array( 'props' => $plan_id ) ) ? 'yes' : null ;

                            if( is_null( $payment_started[ $plan_id ] ) ) {
                                break ;
                            }
                        } else if( SUMO_PP_Payment_Plan_Manager::when_prev_ins_pay_with_current_ins() ) {
                            $payment_ended[ $plan_id ] = SUMO_PP_Payment_Plan_Manager::get_prop( 'payment_ended' , array( 'props' => $plan_id ) ) ? 'yes' : null ;

                            if( is_null( $payment_ended[ $plan_id ] ) ) {
                                break ;
                            }
                        }
                    }
                }

                if( ! empty( $payment_started ) && ! in_array( null , $payment_started ) && in_array( 'yes' , $payment_started ) ) {
                    return false ;
                } else if( ! empty( $payment_ended ) && ! in_array( null , $payment_ended ) && in_array( 'yes' , $payment_ended ) ) {
                    return false ;
                }
            }
        }

        if( is_product() ) {
            if( empty( self::$product_props[ 'product_id' ] ) ) {
                self::get_product_props( $product ) ;
            }

            if(
                    'pay-in-deposit' === self::get_prop( 'payment_type' ) &&
                    'before' === self::get_prop( 'pay_balance_type' ) &&
                    'out-of-stock' === self::get_prop( 'set_expired_deposit_payment_as' )
            ) {
                if( isset( self::$product_props[ 'booking_payment_end_date' ] ) ) {
                    //may be it is SUMO Booking product
                    return true ;
                } else if( _sumo_pp_get_timestamp( self::get_prop( 'pay_balance_before' ) ) <= _sumo_pp_get_timestamp( 0 , 0 , true ) ) {
                    return false ;
                }
            }
        }
        return $is_in_stock ;
    }

}
