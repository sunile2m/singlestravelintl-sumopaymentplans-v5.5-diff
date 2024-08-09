<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle WooCommerce Bookings Compatibility.
 * 
 * @class SUMO_PP_WC_Bookings
 * @category Class
 */
class SUMO_PP_WC_Bookings {

    /**
     * Init SUMO_PP_WC_Bookings.
     */
    public static function init() {
        add_action( 'woocommerce_before_add_to_cart_form' , __CLASS__ . '::remove_default_payment_fields' , 999 ) ;
        add_action( 'woocommerce_after_add_to_cart_button' , __CLASS__ . '::add_payment_fields' , 999 ) ;
        add_filter( 'booking_form_calculated_booking_cost' , __CLASS__ . '::get_booking_cost' , 999 , 3 ) ;
        add_filter( 'sumopaymentplans_get_product_props' , __CLASS__ . '::set_booking_cost' , 999 , 1 ) ;
    }

    public static function can_add_booking_cost( $product_props ) {

        if ( 'booking' === $product_props[ 'product_type' ] && 'pay-in-deposit' === $product_props[ 'payment_type' ] ) {
            if ( 'pre-defined' === $product_props[ 'deposit_type' ] ) {
                if ( 'percent-of-product-price' === $product_props[ 'deposit_price_type' ] ) {
                    return true ;
                }
            } else {
                return true ;
            }
        }
        return false ;
    }

    public static function get_booking_cost_from_session( $product_id = null ) {
        $booking_cost = WC()->session->get( SUMO_PP_PLUGIN_PREFIX . 'wc_booking_cost' ) ;

        if ( isset( $booking_cost[ $product_id ] ) ) {
            return $booking_cost[ $product_id ] ;
        }
        return is_array( $booking_cost ) ? $booking_cost : array () ;
    }

    public static function maybe_clear_booking_session( $product_id ) {

        if ( ! is_callable( array ( WC()->session , 'set' ) ) ) {
            return ;
        }

        $booking_cost_from_session = self::get_booking_cost_from_session() ;

        unset( $booking_cost_from_session[ $product_id ] ) ;

        WC()->session->set( SUMO_PP_PLUGIN_PREFIX . 'wc_booking_cost' , $booking_cost_from_session ) ;
    }

    public static function remove_default_payment_fields() {
        global $post ;

        if ( isset( $post->ID ) ) {
            self::maybe_clear_booking_session( $post->ID ) ;

            $product_props = SUMO_PP_Product_Manager::get_product_props( $post->ID ) ;

            if ( self::can_add_booking_cost( $product_props ) ) {
                remove_action( 'woocommerce_before_add_to_cart_button' , 'SUMO_PP_Product_Manager::render_payment_type_fields' , 10 ) ;
            }
        }
    }

    public static function add_payment_fields() {
        global $post ;

        if ( isset( $post->ID ) ) {
            $product_props = SUMO_PP_Product_Manager::get_product_props( $post->ID ) ;

            if ( self::can_add_booking_cost( $product_props ) ) {
                echo '<span id="' . SUMO_PP_PLUGIN_PREFIX . 'wc_booking_deposit_fields"></span>' ;
                echo SUMO_PP_Product_Manager::get_payment_type_fields( null , false , SUMO_PP_PLUGIN_PREFIX . 'wc_booking_deposit_fields' ) ;
            }
        }
    }

    public static function get_booking_cost( $booking_cost , $booking_form , $posted ) {

        if ( ! is_callable( array ( WC()->session , 'set' ) ) ) {
            return $booking_cost ;
        }

        if ( isset( $booking_form->product ) ) {
            $product_props = SUMO_PP_Product_Manager::get_product_props( $booking_form->product ) ;

            self::maybe_clear_booking_session( $product_props[ 'product_id' ] ) ;

            if ( self::can_add_booking_cost( $product_props ) ) {
                if ( is_numeric( $booking_cost ) ) {
                    WC()->session->set( SUMO_PP_PLUGIN_PREFIX . 'wc_booking_cost' , array (
                        $product_props[ 'product_id' ] => $booking_cost
                    ) ) ;
                } else {
                    self::maybe_clear_booking_session( $product_props[ 'product_id' ] ) ;
                }
            }
        }
        return $booking_cost ;
    }

    public static function set_booking_cost( $product_props ) {

        if ( ! is_callable( array ( WC()->session , 'set' ) ) ) {
            return $product_props ;
        }

        $booking_cost = self::get_booking_cost_from_session( $product_props[ 'product_id' ] ) ;

        if ( self::can_add_booking_cost( $product_props ) && is_numeric( $booking_cost ) ) {
            $product_props[ 'product_price' ] = $booking_cost ;
        } else {
            self::maybe_clear_booking_session( $product_props[ 'product_id' ] ) ;
        }
        return $product_props ;
    }

}

SUMO_PP_WC_Bookings::init() ;
