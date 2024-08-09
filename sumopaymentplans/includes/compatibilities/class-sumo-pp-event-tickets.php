<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle Payments with Event Tickets powered by Modern Tribe, Inc.
 * 
 * @class SUMO_PP_Event_Tickets
 * @category Class
 */
class SUMO_PP_Event_Tickets {

    /**
     * Init SUMO_PP_Event_Tickets.
     */
    public static function init() {
        add_action( 'wootickets_tickets_after_quantity_input' , __CLASS__ . '::render_payment_type_fields' , 10 , 2 ) ;
        add_filter( 'sumopaymentplans_enqueue_payment_type_selector' , __CLASS__ . '::enqueue_payment_type_selector' ) ;
    }

    public static function render_payment_type_fields( $ticket , $product ) {
        if ( SUMO_PP_Product_Manager::is_payment_product( $product ) ) {
            echo '<br>' ;
            echo SUMO_PP_Product_Manager::get_payment_type_fields() ;
        }
    }

    public static function enqueue_payment_type_selector( $bool ) {
        if ( function_exists( 'tribe_tickets_post_type_enabled' ) && tribe_tickets_post_type_enabled( get_post_type() ) ) {
            return true ;
        }
        return $bool ;
    }

}

SUMO_PP_Event_Tickets::init() ;
