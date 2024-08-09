<?php

if( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Manage payment order item
 * 
 * @class SUMO_PP_Order_Item_Manager
 * @category Class
 */
class SUMO_PP_Order_Item_Manager {

    /**
     * The single instance of the class.
     */
    protected static $instance = null ;

    /**
     * Create instance for SUMO_PP_Order_Item_Manager.
     */
    public static function instance() {
        if( is_null( self::$instance ) ) {
            self::$instance = new self() ;
        }
        return self::$instance ;
    }

    /**
     * Construct SUMO_PP_Order_Item_Manager.
     */
    public function __construct() {
        add_action( 'woocommerce_checkout_create_order_line_item' , __CLASS__ . '::add_order_item_meta' , 10 , 4 ) ;
        add_filter( 'woocommerce_order_formatted_line_subtotal' , __CLASS__ . '::render_order_item_balance_payable' , 99 , 3 ) ;
        add_action( 'woocommerce_admin_order_totals_after_total' , __CLASS__ . '::render_balance_payable_amount' , 99 ) ;
        add_action( 'woocommerce_before_save_order_item' , __CLASS__ . '::calculate_deposit_by_item' , 20 ) ;
        add_action( 'woocommerce_saved_order_items' , __CLASS__ . '::calculate_deposit_by_order' , 20 , 2 ) ;
        add_filter( 'woocommerce_hidden_order_itemmeta' , __CLASS__ . '::hide_order_itemmeta' , 20 ) ;
    }

    public static function get_order_item_balance_payable( $maybe_payment_order , $item = null ) {
        $remaining_payable_amount = null ;

        if( $maybe_payment_order->is_child() ) {
            $remaining_payable_amount = get_post_meta( $maybe_payment_order->order_id , SUMO_PP_PLUGIN_PREFIX . 'remaining_payable_amount' , true ) ;

            //BKWD CMPT < 5.1
            if( ! is_numeric( $remaining_payable_amount ) ) {
                $payment = $maybe_payment_order->has_payment_product() ;

                if( ! $payment ) {
                    return $remaining_payable_amount ;
                }

                if( 'my_account' === get_post_meta( $maybe_payment_order->order_id , SUMO_PP_PLUGIN_PREFIX . 'created_via' , true ) ) {
                    $next_of_next_installment_count = 1 + absint( get_post_meta( $maybe_payment_order->order_id , SUMO_PP_PLUGIN_PREFIX . 'next_installment_count' , true ) ) ;
                } else {
                    $next_of_next_installment_count = $payment->get_next_of_next_installment_count() ;
                }
                $remaining_payable_amount = $payment->get_remaining_payable_amount( $next_of_next_installment_count ) ;
            }
        } else {
            if( is_null( $item ) ) {
                if( 1 === sizeof( $maybe_payment_order->order->get_items() ) ) {
                    $payment_data = $maybe_payment_order->contains_payment_data() ;
                } else {
                    return $remaining_payable_amount ;
                }
            } else {
                $payment_data = $maybe_payment_order->item_contains_payment_data( $item ) ;
            }

            if( isset( $payment_data[ 'remaining_payable_amount' ] ) ) {
                $remaining_payable_amount = is_numeric( $payment_data[ 'remaining_payable_amount' ] ) ? $payment_data[ 'remaining_payable_amount' ] : 0 ;
            } else {
                //BKWD CMPT < 3.1
                if( ! empty( $item[ 'product_id' ] ) ) {
                    if( $payment = $maybe_payment_order->has_payment_product( $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ]  ) ) {
                        $remaining_payable_amount = $payment->get_remaining_payable_amount() ;
                    }
                } else {
                    if( $payment = $maybe_payment_order->has_payment_product() ) {
                        $remaining_payable_amount = $payment->get_remaining_payable_amount() ;
                    }
                }
            }
        }
        return $remaining_payable_amount ;
    }

    public static function add_order_item_meta( $item , $cart_item_key , $cart_item , $order ) {
        if( ! empty( $cart_item[ 'sumopaymentplans' ] ) ) {
            self::add_order_item_payment_meta( $item , $cart_item[ 'sumopaymentplans' ] ) ;
        }
    }

    public static function render_order_item_balance_payable( $subtotal , $item , $order ) {
        $maybe_payment_order = _sumo_pp_get_order( $order ) ;

        if( ! $maybe_payment_order ) {
            return $subtotal ;
        }

        $remaining_payable_amount = self::get_order_item_balance_payable( $maybe_payment_order , $item ) ;

        if( ! is_numeric( $remaining_payable_amount ) ) {
            return $subtotal ;
        }

        $subtotal .= sprintf( __( '<p class="lineitemmeta"><small style="color:#777;">Balance <strong>%s</strong> payable</small></p>' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , wc_price( $remaining_payable_amount , array( 'currency' => $maybe_payment_order->get_currency() ) ) ) ;
        return $subtotal ;
    }

    public static function render_balance_payable_amount( $order_id ) {
        $maybe_payment_order = _sumo_pp_get_order( $order_id ) ;

        if( ! $maybe_payment_order ) {
            return ;
        }

        $remaining_payable_amount = self::get_order_item_balance_payable( $maybe_payment_order ) ;

        if( ! is_numeric( $remaining_payable_amount ) ) {
            return ;
        }

        echo '<tr>'
        . '<td class="label">' . esc_html__( 'Balance Payable' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) . ':</td>'
        . '<td width="1%"></td>'
        . '<td class="sumo_pp_balance_payable">' . wc_price( $remaining_payable_amount , array( 'currency' => $maybe_payment_order->get_currency() ) ) . '</td>'
        . '</tr>' ;
    }

    public static function calculate_deposit_by_item( $item ) {
        if( empty( $_POST[ 'items' ] ) ) {
            return ;
        }

        if(get_class($item) == "WC_Order_Item_Fee") {
            return ;
        }

        parse_str( $_POST[ 'items' ] , $items ) ;

        if(is_admin()){
        //      echo "<pre>Stuff:<br/>";
        //      echo get_class($item);
        //      print_r($item);
        //      echo "<pre>";
        }

        $product_id        = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id() ;
        $payment_data_args = array(
            'product_props' => $product_id ,
            'qty'           => $item->get_quantity() ,
                ) ;

        if( isset( $items[ '_sumo_pp_selected_plan' ][ $product_id ] ) ) {
            if( empty( $items[ '_sumo_pp_selected_plan' ][ $product_id ] ) ) {
                return ;
            }

            $payment_data_args[ 'plan_props' ] = $items[ '_sumo_pp_selected_plan' ][ $product_id ] ;
        } else if( isset( $items[ '_sumo_pp_deposit_amount' ][ $product_id ] ) ) {
            if( ! is_numeric( $items[ '_sumo_pp_deposit_amount' ][ $product_id ] ) ) {
                return ;
            }

            $payment_data_args[ 'deposited_amount' ] = floatval( $items[ '_sumo_pp_deposit_amount' ][ $product_id ] ) ;
        }

        $payment_data = SUMO_PP_Data_Manager::get_payment_data( $payment_data_args ) ;

        if(
                ! empty( $payment_data[ 'payment_product_props' ][ 'payment_type' ] ) &&
                SUMO_PP_Product_Manager::is_payment_product( $payment_data[ 'payment_product_props' ] )
        ) {
            $product = wc_get_product( $product_id ) ;
            $item->set_total( wc_get_price_excluding_tax( $product , array( 'qty' => $item->get_quantity() , 'price' => $payment_data[ 'down_payment' ] ) ) ) ;
            $item->set_subtotal( wc_get_price_excluding_tax( $product , array( 'qty' => $item->get_quantity() , 'price' => $payment_data[ 'down_payment' ] ) ) ) ;

            self::add_order_item_payment_meta( $item , $payment_data ) ;
        }
    }

    public static function calculate_deposit_by_order( $order_id , $items ) {
        if( empty( $_POST[ 'items' ] ) ) {
            return ;
        }

        parse_str( $_POST[ 'items' ] , $items ) ;

        if( empty( $items[ '_sumo_pp_product_type' ] ) || 'order' !== $items[ '_sumo_pp_product_type' ] ) {
            return ;
        }

        $payment_order = _sumo_pp_get_order( $order_id ) ;

        $payment_data_args = array(
            'order_total' => $payment_order->order->get_total() ,
                ) ;

        if( isset( $items[ '_sumo_pp_selected_plan' ] ) ) {
            if( empty( $items[ '_sumo_pp_selected_plan' ] ) ) {
                return ;
            }

            $payment_data_args[ 'plan_props' ] = $items[ '_sumo_pp_selected_plan' ] ;
        } else if( isset( $items[ '_sumo_pp_deposit_amount' ] ) ) {
            if( ! is_numeric( $items[ '_sumo_pp_deposit_amount' ] ) ) {
                return ;
            }

            $payment_data_args[ 'down_payment' ] = floatval( $items[ '_sumo_pp_deposit_amount' ] ) ;
        }

        $order_item_data = array() ;
        foreach( $payment_order->order->get_items() as $item ) {
            if( ! $product = $item->get_product() ) {
                continue ;
            }

            $order_item_data[]                                        = array( 'product' => $product , 'order_item' => new WC_Order_Item_Product( $item->get_id() ) ) ;
            $payment_data_args[ 'order_items' ][ $product->get_id() ] = array(
                'price'             => $product->get_price() ,
                'qty'               => $item->get_quantity() ,
                'line_subtotal'     => $item->get_subtotal() ,
                'line_subtotal_tax' => $item->get_subtotal_tax() ,
                'line_total'        => $item->get_total() ,
                'line_tax'          => $item->get_total_tax() ,
                    ) ;
        }

        if( empty( $order_item_data ) ) {
            return ;
        }

        $payment_order->order->remove_order_items( 'line_item' ) ;

        SUMO_PP_Order_Payment_Plan::set_order_props( $payment_data_args ) ;

        $order_props = SUMO_PP_Order_Payment_Plan::get_order_props( false ) ;
        $item_data   = current( $order_item_data ) ;

        SUMO_PP_Order_Payment_Plan::add_items_to_order( $payment_order , $item_data[ 'product' ] , array(
            'order_props'     => $order_props ,
            'line_total'      => (is_numeric( $order_props[ 'down_payment' ] ) ? $order_props[ 'down_payment' ] : 0 ) ,
            'order_item_data' => $order_item_data ,
        ) ) ;
    }

    public static function hide_order_itemmeta( $hidden_metas ) {
        $hidden_metas[] = SUMO_PP_PLUGIN_PREFIX . 'payment_id' ;
        return $hidden_metas ;
    }

    public static function add_order_item_payment_meta( $item , $payment_data ) {
        $payment_type = null ;
        if( ! empty( $payment_data[ 'payment_type' ] ) ) {
            $payment_type = $payment_data[ 'payment_type' ] ;
        } else if( ! empty( $payment_data[ 'payment_product_props' ][ 'payment_type' ] ) ) {
            $payment_type = $payment_data[ 'payment_product_props' ][ 'payment_type' ] ;
        }

        if( 'payment-plans' === $payment_type ) {
            $meta_key = __( 'Payment Plan' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ;

            if( is_numeric( $item ) ) {
                wc_delete_order_item_meta( $item , $meta_key ) ;
                wc_add_order_item_meta( $item , $meta_key , get_the_title( $payment_data[ 'payment_plan_props' ][ 'plan_id' ] ) ) ;
            } else {
                $item->delete_meta_data( $meta_key ) ;
                $item->add_meta_data( $meta_key , get_the_title( $payment_data[ 'payment_plan_props' ][ 'plan_id' ] ) ) ;
            }
        }

        if( ! empty( $payment_data[ 'total_payable_amount' ] ) ) {
            $meta_key = __( 'Total payable' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ;

            if( is_numeric( $item ) ) {
                wc_delete_order_item_meta( $item , $meta_key ) ;
                wc_add_order_item_meta( $item , $meta_key , wc_price( $payment_data[ 'total_payable_amount' ] ) ) ;
            } else {
                $item->delete_meta_data( $meta_key ) ;
                $item->add_meta_data( $meta_key , wc_price( $payment_data[ 'total_payable_amount' ] ) ) ;
            }
        }

        $next_payment_date = '' ;
        if( $payment_data[ 'next_payment_date' ] ) {
            $next_payment_date = _sumo_pp_get_date_to_display( $payment_data[ 'next_payment_date' ] ) ;
        } else if( 'after_admin_approval' === $payment_data[ 'activate_payment' ] ) {
            $next_payment_date = __( 'After Admin Approval' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ;
        }

        if( ! empty( $next_payment_date ) ) {
            $meta_key = __( 'Next Payment Date' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ;

            if( is_numeric( $item ) ) {
                wc_delete_order_item_meta( $item , $meta_key ) ;
                wc_add_order_item_meta( $item , $meta_key , $next_payment_date ) ;
            } else {
                $item->delete_meta_data( $meta_key ) ;
                $item->add_meta_data( $meta_key , $next_payment_date ) ;
            }
        }

        if( is_numeric( $item ) ) {
            wc_delete_order_item_meta( $item , SUMO_PP_PLUGIN_PREFIX . 'payment_data' ) ;
            wc_add_order_item_meta( $item , SUMO_PP_PLUGIN_PREFIX . 'payment_data' , $payment_data , true ) ;
        } else {
            $item->delete_meta_data( SUMO_PP_PLUGIN_PREFIX . 'payment_data' ) ;
            $item->add_meta_data( SUMO_PP_PLUGIN_PREFIX . 'payment_data' , $payment_data , true ) ;
        }
    }

}
