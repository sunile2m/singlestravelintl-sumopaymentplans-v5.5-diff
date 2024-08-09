<?php

if( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Manage payment products in cart
 * 
 * @class SUMO_PP_Cart_Manager
 * @category Class
 */
class SUMO_PP_Cart_Manager {

    protected static $allow_only_single_payment_product ;

    protected static $allow_multiple_payment_products ;

    protected static $add_to_cart_transient ;

    const CANNOT_ADD_NORMAL_PRODUCTS_WHILE_PAYMENTS_IN_CART = 501 ;

    const CANNOT_ADD_PAYMENTS_WHILE_NORMAL_PRODUCTS_IN_CART = 502 ;

    const CANNOT_ADD_MULTIPLE_PAYMENTS_IN_CART = 503 ;

    const INVALID_PAYMENTS_REMOVED_FROM_CART = 504 ;

    const INVALID_DEPOSIT_AMOUNT_IS_ENTERED = 505 ;

    /**
     * The single instance of the class.
     */
    protected static $instance = null ;

    /**
     * Create instance for SUMO_PP_Cart_Manager.
     */
    public static function instance() {
        if( is_null( self::$instance ) ) {
            self::$instance = new self() ;
        }
        return self::$instance ;
    }

    /**
     * Construct SUMO_PP_Cart_Manager.
     */
    public function __construct() {
        add_filter( 'woocommerce_cart_item_name' , __CLASS__ . '::render_payment_plan_name' , 10 , 3 ) ;
        add_filter( 'woocommerce_checkout_cart_item_quantity' , __CLASS__ . '::render_payment_plan_name' , 10 , 3 ) ;
        add_filter( 'woocommerce_cart_item_price' , __CLASS__ . '::render_payment_info' , 10 , 3 ) ;
        add_filter( 'woocommerce_checkout_cart_item_quantity' , __CLASS__ . '::render_payment_info' , 10 , 3 ) ;
        add_filter( 'woocommerce_cart_item_subtotal' , __CLASS__ . '::render_balance_payable' , 10 , 3 ) ;
        add_action( 'woocommerce_cart_totals_after_order_total' , __CLASS__ . '::render_cart_balance_payable' , 999 ) ;
        add_action( 'woocommerce_review_order_after_order_total' , __CLASS__ . '::render_cart_balance_payable' , 999 ) ;
        add_filter( 'woocommerce_cart_totals_order_total_html' , __CLASS__ . '::render_payable_now' , 10 ) ;

        add_filter( 'woocommerce_add_to_cart_validation' , __CLASS__ . '::validate_add_to_cart' , 999 , 6 ) ;
        add_action( 'woocommerce_cart_loaded_from_session' , __CLASS__ . '::validate_cart_session' , 999 ) ;
        add_filter( 'woocommerce_add_cart_item_data' , __CLASS__ . '::add_payment_item_data' , 99 , 4 ) ;
        add_action( 'woocommerce_before_calculate_totals' , __CLASS__ . '::refresh_cart' , 998 ) ;
        add_filter( 'woocommerce_product_get_price' , __CLASS__ . '::get_initial_amount' , 999 , 2 ) ;
        add_filter( 'woocommerce_product_variation_get_price' , __CLASS__ . '::get_initial_amount' , 999 , 2 ) ;
        add_filter( 'woocommerce_calculated_total' , __CLASS__ . '::prevent_shipping_charges_in_initial_order' , 99 , 2 ) ;
//        add_filter( 'woocommerce_calculate_item_totals_taxes' , __CLASS__ . '::charge_tax_initially' , 99 , 2 ) ;
    }

    public static function allow_only_single_payment_product() {
        if( ! is_bool( self::$allow_only_single_payment_product ) ) {
            self::$allow_only_single_payment_product = 'single-payment' === get_option( SUMO_PP_PLUGIN_PREFIX . 'products_that_can_be_placed_in_an_order' , 'any' ) ? true : false ;
        }
        return self::$allow_only_single_payment_product ;
    }

    public static function allow_multiple_payment_products() {
        if( ! is_bool( self::$allow_multiple_payment_products ) ) {
            self::$allow_multiple_payment_products = 'multiple-payments' === get_option( SUMO_PP_PLUGIN_PREFIX . 'products_that_can_be_placed_in_an_order' , 'any' ) ? true : false ;
        }
        return self::$allow_multiple_payment_products ;
    }

    public static function is_payment_item( $item ) {

        if( is_array( $item ) ) {
            if( ! empty( $item[ 'sumopaymentplans' ][ 'payment_product_props' ] ) ) {
                return $item[ 'sumopaymentplans' ] ;
            }
        } else if( is_string( $item ) ) {
            if( ! empty( WC()->cart->cart_contents[ $item ][ 'sumopaymentplans' ][ 'payment_product_props' ] ) ) {
                return WC()->cart->cart_contents[ $item ][ 'sumopaymentplans' ] ;
            }
        } else {
            $product_id = false ;
            if( is_callable( array( $item , 'get_id' ) ) ) {
                $product_id = $item->get_id() ;
            } else if( is_numeric( $item ) ) {
                $product_id = $item ;
            }

            if( $product_id && ! empty( WC()->cart->cart_contents ) ) {
                foreach( WC()->cart->cart_contents as $item_key => $cart_item ) {
                    if(
                            ! empty( $cart_item[ 'sumopaymentplans' ][ 'payment_product_props' ] ) &&
                            $product_id == $cart_item[ 'sumopaymentplans' ][ 'product_id' ]
                    ) {
                        return $cart_item[ 'sumopaymentplans' ] ;
                    }
                }
            }
        }
        return false ;
    }

    public static function cart_contains_payment() {
        if( ! empty( WC()->cart->cart_contents ) ) {
            foreach( WC()->cart->cart_contents as $item_key => $cart_item ) {
                if( self::is_payment_item( $cart_item ) ) {
                    return true ;
                }
            }
        }
        return false ;
    }

    public static function cart_contains_payment_of( $context , $value ) {
        if( ! empty( WC()->cart->cart_contents ) ) {
            foreach( WC()->cart->cart_contents as $item_key => $cart_item ) {
                if( $payment = self::is_payment_item( $cart_item ) ) {
                    if( isset( $payment[ 'payment_product_props' ][ $context ] ) && in_array( $payment[ 'payment_product_props' ][ $context ] , ( array ) $value ) ) {
                        return $item_key ;
                    }
                }
            }
        }
        return false ;
    }

    public static function maybe_get_duplicate_products_in_cart( $product_id ) {
        $duplicate_products = array() ;

        if( ! empty( WC()->cart->cart_contents ) ) {
            foreach( WC()->cart->cart_contents as $item_key => $cart_item ) {
                if( ! empty( $cart_item[ 'product_id' ] ) && $product_id == ($cart_item[ 'variation_id' ] ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ]) ) {
                    $duplicate_products[] = $item_key ;
                }
            }
        }
        return $duplicate_products ;
    }

    public static function charge_shipping_during_final_payment() {
        return WC()->cart->needs_shipping() && 1 === sizeof( WC()->cart->cart_contents ) && (WC()->cart->get_shipping_total() + WC()->cart->get_shipping_tax()) > 0 && 'final-payment' === get_option( SUMO_PP_PLUGIN_PREFIX . 'charge_shipping_during' , 'initial-payment' ) ;
    }

    public static function get_payments_from_cart( $context = null , $value = null ) {
        $payments = array() ;

        if( ! empty( WC()->cart->cart_contents ) ) {
            foreach( WC()->cart->cart_contents as $item_key => $cart_item ) {
                if( $payment = self::is_payment_item( $cart_item ) ) {
                    if( ! is_null( $context ) ) {
                        if( isset( $payment[ 'payment_product_props' ][ $context ] ) && in_array( $payment[ 'payment_product_props' ][ $context ] , ( array ) $value ) ) {
                            $payments[ $item_key ] = $payment ;
                        }
                    } else {
                        $payments[ $item_key ] = $payment ;
                    }
                }
            }
        }
        return $payments ;
    }

    public static function get_cart_balance_payable_amount() {
        $remaining_payable_amount = 0 ;

        if( ! empty( WC()->cart->cart_contents ) ) {
            foreach( WC()->cart->cart_contents as $item_key => $cart_item ) {
                if( $payment = self::is_payment_item( $cart_item ) ) {
                    $remaining_payable_amount += $payment[ 'remaining_payable_amount' ] ;
                }
            }
        }
        return $remaining_payable_amount ;
    }

    public static function get_payment_info_to_display( $cart_item , $context = 'default' ) {
        if( ! empty( $cart_item[ 'sumopaymentplans' ][ 'payment_product_props' ][ 'payment_type' ] ) ) {
            $payment_data = $cart_item[ 'sumopaymentplans' ] ;
        }

        if( empty( $payment_data[ 'payment_product_props' ][ 'payment_type' ] ) ) {
            return '' ;
        }

        $shortcodes = _sumo_pp_get_shortcodes_from_cart_r_checkout( $payment_data ) ;

        $info = '' ;
        switch( $context ) {
            case 'plan_name':
                if( 'payment-plans' !== $payment_data[ 'payment_product_props' ][ 'payment_type' ] ) {
                    break ;
                }

                $label = get_option( SUMO_PP_PLUGIN_PREFIX . 'payment_plan_label' ) ;

                if( $label && false === strpos( $label , '[' ) && false === strpos( $label , ']' ) ) {
                    $info = sprintf( __( '<p><strong>%s</strong> <br>%s</p>' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $label , $shortcodes[ 'content' ][ '[sumo_pp_payment_plan_name]' ] ) ;
                } else {
                    $info = str_replace( $shortcodes[ 'find' ] , $shortcodes[ 'replace' ] , $label ) ;
                }
                break ;
            case 'balance_payable':
                $info  = str_replace( $shortcodes[ 'find' ] , $shortcodes[ 'replace' ] , get_option( SUMO_PP_PLUGIN_PREFIX . 'balance_payable_label' ) ) ;
                break ;
            default :
                $label = get_option( SUMO_PP_PLUGIN_PREFIX . 'next_payment_date_label' ) ;
                $info  = '<p>' ;
                if( 'payment-plans' === $payment_data[ 'payment_product_props' ][ 'payment_type' ] ) {
                    if( $shortcodes[ 'content' ][ '[sumo_pp_payment_plan_desc]' ] ) {
                        $info .= str_replace( $shortcodes[ 'find' ] , $shortcodes[ 'replace' ] , get_option( SUMO_PP_PLUGIN_PREFIX . 'payment_plan_desc_label' ) ) ;
                    }

                    if( 'enabled' === $payment_data[ 'payment_plan_props' ][ 'sync' ] && $payment_data[ 'down_payment' ] <= 0 ) {
                        $label = get_option( SUMO_PP_PLUGIN_PREFIX . 'first_payment_on_label' ) ;
                    }
                } else {
                    if( 'before' === $payment_data[ 'payment_product_props' ][ 'pay_balance_type' ] ) {
                        $label = get_option( SUMO_PP_PLUGIN_PREFIX . 'balance_payment_due_date_label' ) ;
                    }
                }

                $info .= str_replace( $shortcodes[ 'find' ] , $shortcodes[ 'replace' ] , get_option( SUMO_PP_PLUGIN_PREFIX . 'total_payable_label' ) ) ;

                if( 'payment-plans' === $payment_data[ 'payment_product_props' ][ 'payment_type' ] ) {
                    $info .= str_replace( $shortcodes[ 'find' ] , $shortcodes[ 'replace' ] , get_option( SUMO_PP_PLUGIN_PREFIX . 'next_installment_amount_label' ) ) ;
                }

                if( $shortcodes[ 'content' ][ '[sumo_pp_next_payment_date]' ] ) {
                    if( $label && false === strpos( $label , '[' ) && false === strpos( $label , ']' ) ) {
                        $info .= sprintf( __( '<br><small style="color:#777;">%s <strong>%s</strong></small>' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $label , $shortcodes[ 'content' ][ '[sumo_pp_next_payment_date]' ] ) ;
                    } else {
                        $info .= str_replace( $shortcodes[ 'find' ] , $shortcodes[ 'replace' ] , $label ) ;
                    }
                }
                $info .= '</p>' ;
        }
        return $info ;
    }

    public static function add_cart_notice( $code ) {
        switch( $code ) {
            case 501:
                return wc_add_notice( __( 'You can\'t add this product to Cart as a Product with Payment Plan is in Cart.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , 'error' ) ;
            case 502:
                return wc_add_notice( __( 'You can\'t add this product to Cart as normal products is in Cart.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , 'error' ) ;
            case 503:
                return wc_add_notice( __( 'You can\'t add this product to Cart as Product(s) with Payment Plan is in Cart.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , 'error' ) ;
            case 504:
                return wc_add_notice( __( 'Some of the Product(s) are removed from the Cart as Product with Payment Plan can\'t be bought together with other product(s).' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , 'error' ) ;
            case 505:
                return wc_add_notice( __( 'Enter the deposit amount and try again!!' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , 'error' ) ;
        }
    }

    public static function render_payment_plan_name( $return , $cart_item , $item_key ) {
        if( (is_checkout() && 'woocommerce_cart_item_name' === current_filter()) || ! self::is_payment_item( $cart_item ) ) {
            return $return ;
        }

        $return .= self::get_payment_info_to_display( $cart_item , 'plan_name' ) ;
        return apply_filters( 'sumopaymentplans_payment_plan_name_html' , $return , $cart_item ) ;
    }

    public static function render_payment_info( $price , $cart_item , $item_key ) {
        if( ! $payment = self::is_payment_item( $cart_item ) ) {
            return $price ;
        }

        $return = '' ;
        if(
                'pay-in-deposit' === $payment[ 'payment_product_props' ][ 'payment_type' ] ||
                ('payment-plans' === $payment[ 'payment_product_props' ][ 'payment_type' ] && 'no' === get_option( SUMO_PP_PLUGIN_PREFIX . 'hide_product_price_for_payment_plans' , 'no' ))
        ) {
            if( is_cart() ) {
                $return .= wc_price( floatval( $payment[ 'payment_product_props' ][ 'product_price' ] ) ) ;
            } else if( is_checkout() ) {
                $return .= $price ;
            }
        }
        $return .= self::get_payment_info_to_display( $cart_item ) ;
        return $return ;
    }

    public static function render_balance_payable( $product_subtotal , $cart_item , $item_key ) {
        if( self::is_payment_item( $cart_item ) ) {
            $product_subtotal .= self::get_payment_info_to_display( $cart_item , 'balance_payable' ) ;
        }
        return $product_subtotal ;
    }

    public static function render_cart_balance_payable() {
        $remaining_payable_amount = self::get_cart_balance_payable_amount() ;

        if( $remaining_payable_amount > 0 ) {
            echo '<tr class="' . SUMO_PP_PLUGIN_PREFIX . 'balance_payable_amount">'
            . '<th>' . get_option( SUMO_PP_PLUGIN_PREFIX . 'balance_payable_amount_label' ) . '</th>'
            . '<td data-title="' . get_option( SUMO_PP_PLUGIN_PREFIX . 'balance_payable_amount_label' ) . '">' . wc_price( $remaining_payable_amount ) . '</td>'
            . '</tr>' ;
        }
    }

    public static function render_payable_now( $total ) {
        if( self::cart_contains_payment() ) {
            $total .= sprintf( __( '<p class="%spayable_now"><small style="color:#777;">Payable now</small></p>' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , SUMO_PP_PLUGIN_PREFIX ) ;

            if( self::charge_shipping_during_final_payment() ) {
                $total .= '<div>' ;
                $total .= '<small style="color:#777;font-size:smaller;">' ;
                $total .= sprintf( __( '(Shipping amount <strong>%s%s</strong> will be calculated during final payment)' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , get_woocommerce_currency_symbol() , WC()->cart->get_shipping_total() + WC()->cart->get_shipping_tax() ) ;
                $total .= '</small>' ;
                $total .= '</div>' ;
            }
        }
        return $total ;
    }

    public static function get_request() {
        $payment_type        = null ;
        $deposited_amount    = null ;
        $calc_deposit        = false ;
        $chosen_payment_plan = null ;

        if( isset( $_REQUEST[ SUMO_PP_PLUGIN_PREFIX . 'payment_type' ] ) ) {
            $payment_type = wc_clean( $_REQUEST[ SUMO_PP_PLUGIN_PREFIX . 'payment_type' ] ) ;

            switch( $payment_type ) {
                case 'pay-in-deposit':
                    if( isset( $_REQUEST[ SUMO_PP_PLUGIN_PREFIX . 'deposited_amount' ] ) ) {
                        $deposited_amount = $_REQUEST[ SUMO_PP_PLUGIN_PREFIX . 'deposited_amount' ] ;
                    } else if( isset( $_REQUEST[ SUMO_PP_PLUGIN_PREFIX . 'calc_deposit' ] ) ) {
                        $calc_deposit = ( bool ) $_REQUEST[ SUMO_PP_PLUGIN_PREFIX . 'calc_deposit' ] ;
                    }
                    break ;
                case 'payment-plans':
                    if( isset( $_REQUEST[ SUMO_PP_PLUGIN_PREFIX . 'chosen_payment_plan' ] ) ) {
                        $chosen_payment_plan = $_REQUEST[ SUMO_PP_PLUGIN_PREFIX . 'chosen_payment_plan' ] ;
                    }
                    break ;
            }
        }

        return array(
            'payment_type'        => $payment_type ,
            'deposited_amount'    => $deposited_amount ,
            'calc_deposit'        => $calc_deposit ,
            'chosen_payment_plan' => $chosen_payment_plan ,
                ) ;
    }

    public static function validate_add_to_cart( $bool , $product_id , $quantity , $variation_id = null , $variations = null , $cart_item_data = null ) {
        $add_to_cart_product = $variation_id ? $variation_id : $product_id ;
        $requested           = self::get_request() ;

        if( ! in_array( $requested[ 'payment_type' ] , array( 'pay-in-deposit' , 'payment-plans' ) ) ) {
            if( self::cart_contains_payment() ) {
                if( self::allow_only_single_payment_product() || self::allow_multiple_payment_products() ) {
                    self::add_cart_notice( self::CANNOT_ADD_NORMAL_PRODUCTS_WHILE_PAYMENTS_IN_CART ) ;
                    return false ;
                }
            }
            return $bool ;
        }

        if( 'pay-in-deposit' === $requested[ 'payment_type' ] && ! $requested[ 'calc_deposit' ] && ! is_numeric( $requested[ 'deposited_amount' ] ) ) {
            self::add_cart_notice( self::INVALID_DEPOSIT_AMOUNT_IS_ENTERED ) ;
            return false ;
        }

        self::$add_to_cart_transient = SUMO_PP_Product_Manager::get_product_props( $add_to_cart_product ) ;

        if( $duplicate_products = self::maybe_get_duplicate_products_in_cart( $add_to_cart_product ) ) {
            array_map( array( WC()->cart , 'remove_cart_item' ) , $duplicate_products ) ;
        }

        if( empty( WC()->cart->cart_contents ) ) {
            return $bool ;
        }

        remove_action( 'woocommerce_cart_loaded_from_session' , __CLASS__ . '::validate_cart_session' , 999 ) ;

        if( SUMO_PP_Product_Manager::is_payment_product( self::$add_to_cart_transient ) ) {
            if( self::allow_only_single_payment_product() ) {
                if( self::cart_contains_payment() ) {
                    self::add_cart_notice( self::CANNOT_ADD_MULTIPLE_PAYMENTS_IN_CART ) ;
                    return false ;
                } else {
                    self::add_cart_notice( self::CANNOT_ADD_PAYMENTS_WHILE_NORMAL_PRODUCTS_IN_CART ) ;
                    return false ;
                }
            } else if( self::allow_multiple_payment_products() ) {
                if( ! self::cart_contains_payment() ) {
                    self::add_cart_notice( self::CANNOT_ADD_PAYMENTS_WHILE_NORMAL_PRODUCTS_IN_CART ) ;
                    return false ;
                }
            }
        } else {
            if( self::allow_only_single_payment_product() || self::allow_multiple_payment_products() ) {
                if( self::cart_contains_payment() ) {
                    self::add_cart_notice( self::CANNOT_ADD_NORMAL_PRODUCTS_WHILE_PAYMENTS_IN_CART ) ;
                    return false ;
                }
            }
        }

        add_action( 'woocommerce_cart_loaded_from_session' , __CLASS__ . '::validate_cart_session' , 999 ) ;
        return $bool ;
    }

    public static function validate_cart_session( $cart ) {

        if( empty( WC()->cart->cart_contents ) ) {
            return ;
        }

        if( self::allow_only_single_payment_product() ) {
            $payments = array_keys( self::get_payments_from_cart() ) ;

            if( ! empty( $payments ) && sizeof( WC()->cart->cart_contents ) > 1 ) {
                if( sizeof( WC()->cart->cart_contents ) > sizeof( $payments ) || sizeof( WC()->cart->cart_contents ) === sizeof( $payments ) ) {
                    self::add_cart_notice( self::INVALID_PAYMENTS_REMOVED_FROM_CART ) ;
                    array_map( array( WC()->cart , 'remove_cart_item' ) , $payments ) ;
                }
            }
        } else if( self::allow_multiple_payment_products() ) {
            $payments = array_keys( self::get_payments_from_cart() ) ;

            if( ! empty( $payments ) ) {
                if( sizeof( WC()->cart->cart_contents ) > sizeof( $payments ) ) {
                    self::add_cart_notice( self::INVALID_PAYMENTS_REMOVED_FROM_CART ) ;
                    array_map( array( WC()->cart , 'remove_cart_item' ) , $payments ) ;
                }
            }
        }
    }

    public static function add_payment_item_data( $cart_item_data , $product_id , $variation_id , $quantity ) {
        $add_to_cart_product = $variation_id ? $variation_id : $product_id ;

        if(
                self::$add_to_cart_transient &&
                SUMO_PP_Product_Manager::is_payment_product( self::$add_to_cart_transient ) &&
                $add_to_cart_product === SUMO_PP_Product_Manager::get_prop( 'product_id' , array( 'product_props' => self::$add_to_cart_transient ) )
        ) {
            $requested = self::get_request() ;

            if( is_null( $requested[ 'payment_type' ] ) ) {
                return $cart_item_data ;
            }

            $cart_item_data[ 'sumopaymentplans' ] = apply_filters( 'sumopaymentplans_add_cart_item_data' , SUMO_PP_Data_Manager::get_payment_data( array(
                        'product_props'    => self::$add_to_cart_transient ,
                        'plan_props'       => $requested[ 'chosen_payment_plan' ] ,
                        'deposited_amount' => $requested[ 'deposited_amount' ] ,
                        'calc_deposit'     => $requested[ 'calc_deposit' ] ,
                        'qty'              => absint( $quantity ) ,
                        'item_meta'        => $cart_item_data ,
                    ) ) , $cart_item_data , $product_id , $variation_id , $quantity ) ;
        }
        return $cart_item_data ;
    }

    public static function refresh_cart() {

        foreach( WC()->cart->get_cart() as $item_key => $cart_item ) {
            if( ! empty( $cart_item[ 'sumopaymentplans' ] ) ) {
                if( ! isset( WC()->cart->cart_contents[ $item_key ][ 'sumopaymentplans' ][ 'down_payment' ] ) ) {
                    WC()->cart->cart_contents[ $item_key ][ 'sumopaymentplans' ] = array() ;
                    continue ;
                }

                remove_filter( 'woocommerce_product_get_price' , __CLASS__ . '::get_initial_amount' , 999 , 2 ) ;
                remove_filter( 'woocommerce_product_variation_get_price' , __CLASS__ . '::get_initial_amount' , 999 , 2 ) ;

                $payment_data = SUMO_PP_Data_Manager::get_payment_data( array(
                            'product_props'    => $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ] ,
                            'plan_props'       => WC()->cart->cart_contents[ $item_key ][ 'sumopaymentplans' ][ 'payment_plan_props' ][ 'plan_id' ] ,
                            'deposited_amount' => WC()->cart->cart_contents[ $item_key ][ 'sumopaymentplans' ][ 'down_payment' ] ,
                            'base_price'       => isset( WC()->cart->cart_contents[ $item_key ][ 'sumopaymentplans' ][ 'base_price' ] ) ? WC()->cart->cart_contents[ $item_key ][ 'sumopaymentplans' ][ 'base_price' ] : null ,
                            'qty'              => $cart_item[ 'quantity' ] ,
                            'item_meta'        => $cart_item ,
                        ) ) ;

                add_filter( 'woocommerce_product_get_price' , __CLASS__ . '::get_initial_amount' , 999 , 2 ) ;
                add_filter( 'woocommerce_product_variation_get_price' , __CLASS__ . '::get_initial_amount' , 999 , 2 ) ;

                if( empty( $payment_data[ 'payment_product_props' ][ 'payment_type' ] ) ) {
                    WC()->cart->cart_contents[ $item_key ][ 'sumopaymentplans' ] = array() ;
                    continue ;
                }

                switch( $payment_data[ 'payment_product_props' ][ 'payment_type' ] ) {
                    case 'payment-plans':
                        if(
                                empty( $payment_data[ 'payment_plan_props' ][ 'payment_schedules' ] ) ||
                                empty( $payment_data[ 'payment_product_props' ][ 'selected_plans' ] )
                        ) {
                            WC()->cart->cart_contents[ $item_key ][ 'sumopaymentplans' ] = array() ;
                            continue 2 ;
                        }

                        $plans_col_1 = ! empty( $payment_data[ 'payment_product_props' ][ 'selected_plans' ][ 'col_1' ] ) ? $payment_data[ 'payment_product_props' ][ 'selected_plans' ][ 'col_1' ] : array() ;
                        $plans_col_2 = ! empty( $payment_data[ 'payment_product_props' ][ 'selected_plans' ][ 'col_2' ] ) ? $payment_data[ 'payment_product_props' ][ 'selected_plans' ][ 'col_2' ] : array() ;

                        if( ! in_array( $payment_data[ 'payment_plan_props' ][ 'plan_id' ] , $plans_col_1 ) && ! in_array( $payment_data[ 'payment_plan_props' ][ 'plan_id' ] , $plans_col_2 ) ) {
                            WC()->cart->cart_contents[ $item_key ][ 'sumopaymentplans' ] = array() ;
                            continue 2 ;
                        }
                        break ;
                    case 'pay-in-deposit':
                        break ;
                }
                WC()->cart->cart_contents[ $item_key ][ 'sumopaymentplans' ] = $payment_data ;
            }
        }
    }

    public static function get_initial_amount( $price , $product ) {
        if( is_shop() || is_product() ) {
            return $price ;
        }

        if( $payment = self::is_payment_item( $product ) ) {
            if( SUMO_PP_Product_Manager::is_payment_product( $payment[ 'payment_product_props' ] ) ) {
                $price = $payment[ 'down_payment' ] ;
            }
        }
        return $price ;
    }

    public static function prevent_shipping_charges_in_initial_order( $total , $cart = '' ) {
        if( self::charge_shipping_during_final_payment() && self::cart_contains_payment() ) {
            $shipping_total = WC()->cart->get_shipping_total() + WC()->cart->get_shipping_tax() ;
            $total          = max( $total , $shipping_total ) - min( $total , $shipping_total ) ;
        }
        return $total ;
    }

    public static function charge_tax_initially( $taxes , $item ) {
        if(
                ! empty( $item->product ) &&
                ! $item->price_includes_tax &&
                'initial-payment' === get_option( SUMO_PP_PLUGIN_PREFIX . 'charge_tax_during' , 'initial-payment' ) &&
                ($payment = self::is_payment_item( $item->product ))
        ) {
            $item_total = wc_add_number_precision_deep( $payment[ 'total_payable_amount' ] ) ;
            $taxes      = WC_Tax::calc_tax( $item_total , $item->tax_rates , $item->price_includes_tax ) ;
        }
        return $taxes ;
    }

}
