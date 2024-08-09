<?php

if( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Manage payment order.
 * 
 * @class SUMO_PP_Order_Manager
 * @category Class
 */
class SUMO_PP_Order_Manager {

    /**
     * The single instance of the class.
     */
    protected static $instance = null ;

    /**
     * Create instance for SUMO_PP_Order_Manager.
     */
    public static function instance() {
        if( is_null( self::$instance ) ) {
            self::$instance = new self() ;
        }
        return self::$instance ;
    }

    /**
     * Construct SUMO_PP_Order_Manager.
     */
    public function __construct() {
        add_action( 'woocommerce_order_status_changed' , __CLASS__ . '::create_new_payments' , 19 , 3 ) ;
        add_action( 'woocommerce_order_status_changed' , __CLASS__ . '::update_payments' , 20 , 3 ) ;
        add_filter( 'woocommerce_can_reduce_order_stock' , __CLASS__ . '::prevent_stock_reduction' , 20 , 2 ) ;
    }

    /**
     * Create new payment orders after the subscriber successfully placed the initial payment order.
     * Fire only for the Initial Payment order.
     * 
     * @param int $order_id The Order post ID
     * @param string $old_order_status
     * @param string $new_order_status
     */
    public static function create_new_payments( $order_id , $old_order_status , $new_order_status ) {
        $order = _sumo_pp_get_order( $order_id ) ;

        if(
                $order &&
                apply_filters( 'sumopaymentplans_add_new_payments' , true , $order->order_id , $old_order_status , $new_order_status ) &&
                $order->is_parent() &&
                $order->contains_payment_data() &&
                ! $order->is_payment_order()
        ) {
            do_action( 'sumopaymentplans_before_adding_new_payments' , $order->order_id , $old_order_status , $new_order_status ) ;

            foreach( $order->order->get_items() as $item ) {
                //may be add new payment entry.
                if( $payment_data = $order->item_contains_payment_data( $item ) ) {
                    $payment_id = self::add_new_payment( $order , $payment_data ) ;

                    if( $payment_id ) {
                        $item->add_meta_data( SUMO_PP_PLUGIN_PREFIX . 'payment_id' , $payment_id , true ) ;
                    }
                }
            }

            do_action( 'sumopaymentplans_after_new_payments_added' , $order->order_id , $old_order_status , $new_order_status ) ;
        }
    }

    /**
     * Add new Payment.
     * @param int | object $order The Order post ID
     * @param mixed $payment_data
     */
    public static function add_new_payment( $order , $payment_data ) {

        try {
            //Insert new payment post
            $payment_id = wp_insert_post( array(
                'post_type'     => 'sumo_pp_payments' ,
                'post_date'     => _sumo_pp_get_date() ,
                'post_date_gmt' => _sumo_pp_get_date() ,
                'post_status'   => SUMO_PP_PLUGIN_PREFIX . 'pending' ,
                'post_author'   => 1 ,
                'post_title'    => __( 'Payments' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                    ) , true ) ;

            if( is_wp_error( $payment_id ) ) {
                throw new Exception( $payment_id->get_error_message() ) ;
            }
            $payment = _sumo_pp_get_payment( $payment_id ) ;

            if( ! empty( $payment_data ) ) {
                foreach( $payment_data as $meta_key => $value ) {
                    if( ! is_null( $value ) && '' !== $value ) {
                        if( 'payment_product_props' === $meta_key ) {
                            foreach( $value as $_meta_key => $_value ) {
                                if( ! is_null( $_value ) && '' !== $_value ) {
                                    $payment->add_prop( $_meta_key , $_value ) ;
                                }
                            }
                        } else if( 'payment_plan_props' === $meta_key ) {
                            foreach( $value as $_meta_key => $_value ) {
                                if( ! is_null( $_value ) && '' !== $_value ) {
                                    $payment->add_prop( $_meta_key , $_value ) ;
                                }
                            }
                        } else {
                            $payment->add_prop( $meta_key , $value ) ;
                        }
                    }
                }
            }

            $payment->add_prop( 'initial_payment_order_id' , $order->order_id ) ;
            $payment->add_prop( 'customer_id' , $order->get_customer_id() ) ;
            $payment->add_prop( 'customer_email' , $order->get_billing_email() ) ;
            $payment->add_prop( 'payment_number' , $payment->set_payment_serial_number() ) ;
            $payment->add_prop( 'version' , SUMO_PP_PLUGIN_VERSION ) ;
            $payment->add_prop( 'get_customer' , get_user_by( 'id' , $order->get_customer_id() ) ) ; //set Customer props
            $payment->add_prop( 'charge_tax_during' , get_option( SUMO_PP_PLUGIN_PREFIX . 'charge_tax_during' , 'initial-payment' ) ) ;
            $payment->add_prop( 'charge_shipping_during' , get_option( SUMO_PP_PLUGIN_PREFIX . 'charge_shipping_during' , 'initial-payment' ) ) ;
            add_post_meta( $order->order_id , 'is' . SUMO_PP_PLUGIN_PREFIX . 'order' , 'yes' ) ;

            $payment = _sumo_pp_get_payment( $payment->id ) ;
            $payment->add_payment_note( __( 'New payment order created.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , 'pending' , __( 'New Payment Order' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ) ;

            do_action( 'sumopaymentplans_new_payment_order' , $payment->id , $order->order_id ) ;
        } catch( Exception $e ) {
            return 0 ;
        }
        return $payment->id ;
    }

    /**
     * Update each payment data based upon Order status.
     * @param int $order_id The Order post ID
     * @param string $old_order_status
     * @param string $new_order_status
     */
    public static function update_payments( $order_id , $old_order_status , $new_order_status ) {

        if( ! $order = _sumo_pp_get_order( $order_id ) ) {
            return ;
        }
        //Check whether this order is already placed
        if( in_array( $old_order_status , array( 'completed' , 'processing' ) ) ) {
            return ;
        }

        $payments = _sumo_pp()->query->get( array(
            'type'       => 'sumo_pp_payments' ,
            'status'     => array_keys( _sumo_pp_get_payment_statuses() ) ,
            'meta_key'   => '_initial_payment_order_id' ,
            'meta_value' => $order->get_parent_id() ,
                ) ) ;

        foreach( $payments as $payment_id ) :
            $payment = _sumo_pp_get_payment( $payment_id ) ;
            //may be balance payment is paying.
            if( $order->is_child() ) {
                //Check which balance payment is paying from the parent order.
                if( $order->order_id == $payment->get_balance_payable_order_id() || $order->order_id == $payment->get_balance_payable_order_id( 'my_account' ) ) {
                    //Check payment status is valid to change.
                    if( ! $payment->has_status( array( 'pending' , 'in_progress' , 'overdue' , 'await_cancl' , 'cancelled' , 'failed' ) ) ) {
                        continue ;
                    }

                    //Proceed this payment based upon the Balance payment Order status.
                    switch( apply_filters( 'sumopaymentplans_order_status_to_update_payment' , $new_order_status , $payment->id , $order->order_id , 'balance-payment-order' ) ) {
                        case 'pending':
                        case 'on-hold':
                            $payment->add_payment_note( sprintf( __( 'Waiting for balance payment order#%s to complete.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $order->order_id ) , 'pending' , __( 'Waiting For Balance Payment' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ) ;

                            do_action( 'sumopaymentplans_payment_in_pending' , $payment->id , $order->order_id , 'balance-payment-order' ) ;
                            break ;
                        case 'completed':
                        case 'processing':
                            $payment->update_as_paid_order( $order->order_id ) ;

                            if( $payment->has_next_installment() ) {
                                if( $payment->has_status( 'await_cancl' ) && ( _sumo_pp_get_timestamp() >= _sumo_pp_get_timestamp( $payment->get_next_payment_date() ) ) ) {
                                    $payment->add_payment_note( sprintf( __( 'Balance payment of order#%s made successful.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $order->order_id ) , 'success' , __( 'Balance Payment Success' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ) ;
                                    $payment->update_actual_payments_date() ;
                                    $payment->update_prop( 'last_payment_date' , _sumo_pp_get_date() ) ;
                                    $payment->update_prop( 'next_installment_amount' , $payment->get_next_installment_amount() ) ;
                                    $payment->update_prop( 'remaining_payable_amount' , $payment->get_remaining_payable_amount() ) ;
                                    $payment->update_prop( 'remaining_installments' , $payment->get_remaining_installments() ) ;

                                    self::create_balance_payable_order( $payment ) ;

                                    if( $payment_cron = _sumo_pp_get_payment_cron( $payment ) ) {
                                        $payment_cron->unset_jobs() ;
                                    }
                                } else {
                                    $payment->process_balance_payment( $order ) ;
                                }
                            } else {
                                $payment->payment_complete( $order ) ;
                            }
                            break ;
                        case 'failed':
                        case 'cancelled':
                            $payment->add_payment_note( sprintf( __( 'Error in receiving payment from the user. Balance payable order#%s has been %s.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $order->order_id , $new_order_status ) , 'failure' , __( 'Balance Payment Failed' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ) ;
                            break ;
                    }
                }
                //may be Initial Payment is paying.
            } else if( $order->is_parent() && '' === $payment->get_prop( 'payment_start_date' ) ) {
                //Proceed this payment based upon the Initial payment Order status.
                switch( apply_filters( 'sumopaymentplans_order_status_to_update_payment' , $new_order_status , $payment->id , $order->order_id , 'initial-payment-order' ) ) {
                    case 'pending':
                    case 'on-hold':
                        if( $payment->update_status( 'pending' ) ) {
                            $payment->add_prop( 'next_installment_amount' , $payment->get_next_installment_amount() ) ;
                            $payment->add_prop( 'remaining_payable_amount' , $payment->get_remaining_payable_amount() ) ;
                            $payment->add_prop( 'remaining_installments' , $payment->get_remaining_installments() ) ;
                            $payment->add_payment_note( sprintf( __( 'Waiting for initial payment order#%s to complete.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $order->order_id ) , 'pending' , __( 'Waiting For Initial Payment' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ) ;

                            do_action( 'sumopaymentplans_payment_in_pending' , $payment->id , $order->order_id , 'initial-payment-order' ) ;
                        }
                        break ;
                    case 'completed':
                    case 'processing':
                        if( 'before' !== $payment->get_pay_balance_type() && 'after_admin_approval' === $payment->get_prop( 'activate_payment' ) ) {
                            if( $payment->update_status( 'await_aprvl' ) ) {

                                $payment->add_prop( 'next_installment_amount' , $payment->get_next_installment_amount() ) ;
                                $payment->add_prop( 'remaining_payable_amount' , $payment->get_remaining_payable_amount() ) ;
                                $payment->add_prop( 'remaining_installments' , $payment->get_remaining_installments() ) ;
                                $payment->add_payment_note( __( 'Awaiting Admin to approve the payment.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , 'pending' , __( 'Awaiting Admin Approval' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ) ;

                                do_action( 'sumopaymentplans_payment_awaiting_approval' , $payment->id , $order->order_id , 'initial-payment-order' ) ;
                            }
                        } else if( $payment->awaiting_initial_payment() ) {

                            $payment->process_initial_payment( array(
                                'content' => __( 'Payment is synced. Awaiting for the initial payment.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                                'status'  => 'pending' ,
                                'message' => __( 'Awaiting Initial Payment' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                                    ) , false , 'pending' ) ;
                        } else {
                            $payment->process_initial_payment() ;
                        }
                        break ;
                    case 'failed':
                        $payment->fail_payment( array(
                            'content' => sprintf( __( 'Failed to pay the initial payment of order#%s . Payment is failed.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $order->order_id ) ,
                            'status'  => 'failure' ,
                            'message' => __( 'Initial Payment Failed' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                        ) ) ;
                        break ;
                    case 'cancelled':
                        $payment->cancel_payment( array(
                            'content' => sprintf( __( 'Failed to pay the initial payment of order#%s. Payment is cancelled.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $order->order_id ) ,
                            'status'  => 'failure' ,
                            'message' => __( 'Initial Payment Cancelled' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                        ) ) ;
                        break ;
                }
            }
        endforeach ;
    }

    public static function prevent_stock_reduction( $bool , $order ) {
        if( ! $order = _sumo_pp_get_order( $order ) ) {
            return $bool ;
        }

        if( $order->is_child() && $order->is_payment_order() && _sumo_pp_get_payment( get_post_meta( $order->order_id , '_payment_id' , true ) ) ) {
            return false ;
        }
        return $bool ;
    }

    /**
     * Create Balance payable Order.
     * @param object $payment The Payment post.
     * @param array $args
     * @return int
     */
    public static function create_balance_payable_order( $payment , $args = array() ) {

        if( ! $initial_payment_order = _sumo_pp_get_order( $payment->get_initial_payment_order_id() ) ) {
            return ;
        }

        $args = wp_parse_args( $args , array(
            'next_installment_amount' => floatval( $payment->get_prop( 'next_installment_amount' ) ) ,
            'next_installment_count'  => $payment->get_next_installment_count() ,
            'remaining_installments'  => absint( $payment->get_prop( 'remaining_installments' ) ) ,
            'installments_included'   => 1 ,
            'created_via'             => 'default' ,
            'add_default_note'        => true ,
            'custom_note'             => '' ,
                ) ) ;

        $args[ 'remaining_payable_amount' ] = $payment->get_remaining_payable_amount( 1 + $args[ 'next_installment_count' ] ) ;

        //Create Order.
        $order_id = wp_insert_post( array(
            'post_type'   => 'shop_order' ,
            'post_status' => 'publish' ,
            'post_author' => 1 ,
            'post_parent' => $initial_payment_order->order_id ,
                ) , true ) ;

        if( is_wp_error( $order_id ) ) {
            $payment->add_payment_note( __( 'Error while creating balance payable order.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , 'failure' , __( 'Balance Payable Order Creation Error' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ) ;
            return 0 ;
        }

        //populate Order
        $balance_payable_order = _sumo_pp_get_order( $order_id ) ;

        //set billing address
        self::set_address_details( $initial_payment_order , $balance_payable_order , 'billing' ) ;
        //set shipping address
        self::set_address_details( $initial_payment_order , $balance_payable_order , 'shipping' ) ;
        //set order meta
        self::set_order_details( $initial_payment_order , $balance_payable_order ) ;

        //repopulate Order
        $balance_payable_order = _sumo_pp_get_order( $balance_payable_order->order_id ) ;

        //Add Payment items
        self::add_order_item( $initial_payment_order , $balance_payable_order , $payment , $args ) ;

        if( 'final-payment' === $payment->charge_shipping_during() && 1 === $payment->get_remaining_installments() ) {
            self::set_shipping_methods( $initial_payment_order , $balance_payable_order , $payment ) ;
        }

        if( $tax_enabled = ('each-payment' === $payment->charge_tax_during()) ) {
            self::set_tax( $initial_payment_order , $balance_payable_order ) ;
        }

        if( is_callable( array( $balance_payable_order->order , 'save' ) ) ) {
            $balance_payable_order->order->save() ;
        }

        // Updates tax totals
        if( is_callable( array( $balance_payable_order->order , 'update_taxes' ) ) ) {
            $balance_payable_order->order->update_taxes() ;
        }

        // Calc totals - this also triggers save
        $balance_payable_order->order->calculate_totals( $tax_enabled ) ;

        //Update Default Order status
        $balance_payable_order->update_status( 'pending' ) ;

        add_post_meta( $balance_payable_order->order_id , 'is' . SUMO_PP_PLUGIN_PREFIX . 'order' , 'yes' ) ;
        add_post_meta( $balance_payable_order->order_id , '_payment_id' , $payment->id ) ;

        foreach( $args as $key => $val ) {
            if( 'note' !== $key && $val ) {
                add_post_meta( $balance_payable_order->order_id , SUMO_PP_PLUGIN_PREFIX . $key , $val ) ;
            }
        }

        if( 'default' === $args[ 'created_via' ] ) {
            $payment->add_prop( 'balance_payable_order_id' , $balance_payable_order->order_id ) ;
        }

        $payment->update_prop( 'balance_payable_order_props' , array(
            $payment->get_balance_payable_order_id() => array( 'created_via' => 'default' )
                ) + array(
            $balance_payable_order->order_id => array( 'created_via' => $args[ 'created_via' ] )
        ) ) ;

        if( $args[ 'add_default_note' ] ) {
            $payment->add_payment_note( sprintf( __( 'Balance payable order#%s is created.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $balance_payable_order->order_id ) , 'pending' , __( 'Balance Payable Order Created' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ) ;
        }
        if( ! empty( $args[ 'custom_note' ] ) ) {
            $payment->add_payment_note( $args[ 'custom_note' ] , 'pending' , __( 'Balance Payable Order Created' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ) ;
        }

        return $balance_payable_order->order_id ;
    }

    /**
     * Extract billing and shipping information from Initial payment Order and set in Balance payable Order 
     */
    public static function set_address_details( $initial_payment_order , &$balance_payable_order , $type ) {

        $data = array(
            'first_name' => array( 'billing' , 'shipping' ) ,
            'last_name'  => array( 'billing' , 'shipping' ) ,
            'company'    => array( 'billing' , 'shipping' ) ,
            'address_1'  => array( 'billing' , 'shipping' ) ,
            'address_2'  => array( 'billing' , 'shipping' ) ,
            'city'       => array( 'billing' , 'shipping' ) ,
            'postcode'   => array( 'billing' , 'shipping' ) ,
            'country'    => array( 'billing' , 'shipping' ) ,
            'state'      => array( 'billing' , 'shipping' ) ,
            'email'      => array( 'billing' ) ,
            'phone'      => array( 'billing' ) ,
                ) ;

        foreach( $data as $key => $applicable_to ) {
            $value = '' ;

            if( _sumo_pp_is_wc_version( '<' , '3.0' ) ) {
                $value = get_post_meta( $initial_payment_order->order_id , "_{$type}_{$key}" , true ) ;
            }

            if( is_callable( array( $initial_payment_order->order , "get_{$type}_{$key}" ) ) ) {
                $value = $initial_payment_order->order->{"get_{$type}_{$key}"}() ;
            }

            if( '' === $value ) {
                //may be useful if shipping address is empty
                if( _sumo_pp_is_wc_version( '<' , '3.0' ) ) {
                    $value = get_post_meta( $initial_payment_order->order_id , "_billing_{$key}" , true ) ;
                }

                if( is_callable( array( $initial_payment_order->order , "get_billing_{$key}" ) ) ) {
                    $value = $initial_payment_order->order->{"get_billing_{$key}"}() ;
                }
            }

            if( in_array( $type , $applicable_to ) ) {
                update_post_meta( $balance_payable_order->order_id , "_{$type}_{$key}" , $value ) ;
            }

            if( is_callable( array( $balance_payable_order->order , "set_{$type}_{$key}" ) ) ) {
                $balance_payable_order->order->{"set_{$type}_{$key}"}( $value ) ;
            }
        }
    }

    /**
     * Extract Initial payment Order details other than shipping/billing and set in Balance payable Order 
     */
    public static function set_order_details( $initial_payment_order , &$balance_payable_order ) {

        $data = array(
            'version'            => 'order_version' ,
            'currency'           => 'order_currency' ,
            'order_key'          => 'order_key' ,
            'shipping_total'     => 'order_shipping' ,
            'shipping_tax'       => 'order_shipping_tax' ,
            'total_tax'          => 'order_tax' ,
            'customer_id'        => 'customer_user' ,
            'prices_include_tax' => 'prices_include_tax' ,
                ) ;

        foreach( $data as $method_key => $meta_key ) {
            $value = '' ;

            if( _sumo_pp_is_wc_version( '<' , '3.0' ) ) {
                $value = get_post_meta( $initial_payment_order->order_id , "_{$meta_key}" , true ) ;
            }

            if( is_callable( array( $initial_payment_order->order , "get_{$method_key}" ) ) ) {
                $value = $initial_payment_order->order->{"get_{$method_key}"}() ;
            }

            update_post_meta( $balance_payable_order->order_id , "_{$meta_key}" , $value ) ;

            if( is_callable( array( $balance_payable_order->order , "set_{$method_key}" ) ) ) {
                $balance_payable_order->order->{"set_{$method_key}"}( $value ) ;
            }
        }
    }

    /**
     * Add Payment order Item in balance payable Order.
     */
    public static function add_order_item( $initial_payment_order , &$balance_payable_order , $payment , $args ) {

        do_action( 'sumopaymentplans_before_adding_balance_payable_order_item' , $initial_payment_order , $balance_payable_order , $payment ) ;

        if( _sumo_pp_is_wc_version( '<' , '3.0' ) ) {
            return ;
        }

        $renewal_item_id = false ;
        $item_meta       = false ;
        if( 'order' === $payment->get_product_type() ) {
            if( ! is_array( $payment->get_prop( 'order_items' ) ) ) {
                return ;
            }

            $order_item_data = array() ;
            foreach( $payment->get_prop( 'order_items' ) as $product_id => $data ) {
                if( ! $_product = wc_get_product( $product_id ) ) {
                    continue ;
                }

                $order_item_data[] = array( 'product' => $_product , 'order_item' => array( 'quantity' => $data[ 'qty' ] ) ) ;
            }

            $item_data       = current( $order_item_data ) ;
            $renewal_item_id = SUMO_PP_Order_Payment_Plan::add_items_to_order( $balance_payable_order , $item_data[ 'product' ] , array(
                        'line_total'       => wc_format_decimal( $args[ 'next_installment_amount' ] ) ,
                        'order_item_data'  => $order_item_data ,
                        'add_payment_meta' => false ,
                    ) ) ;

            foreach( $initial_payment_order->order->get_items() as $_item ) {
                if( isset( $_item[ 'item_meta' ] ) ) {
                    $item_meta = $_item[ 'item_meta' ] ;
                }
                break ;
            }
        } else {
            foreach( $initial_payment_order->order->get_items() as $_item ) {
                $product_id = $_item[ 'variation_id' ] > 0 ? $_item[ 'variation_id' ] : $_item[ 'product_id' ] ;

                if(
                        $product_id == $payment->get_product_id() &&
                        ($_product = wc_get_product( $product_id ))
                ) {
                    $product_qty = $payment->get_product_qty() ? $payment->get_product_qty() : 1 ;
                    $line_total  = wc_format_decimal( $args[ 'next_installment_amount' ] / $product_qty ) ;

                    $renewal_item_id = $balance_payable_order->order->add_product( $_product , $product_qty , array(
                        'subtotal' => wc_get_price_excluding_tax( $_product , array(
                            'qty'   => $product_qty ,
                            'price' => $line_total
                        ) ) ,
                        'total'    => wc_get_price_excluding_tax( $_product , array(
                            'qty'   => $product_qty ,
                            'price' => $line_total
                        ) ) ,
                            ) ) ;

                    if( isset( $_item[ 'item_meta' ] ) ) {
                        $item_meta = $_item[ 'item_meta' ] ;
                    }
                    break ;
                }
            }
        }

        if( ! $renewal_item_id || is_wp_error( $renewal_item_id ) ) {
            return ;
        }

        wc_add_order_item_meta( $renewal_item_id , SUMO_PP_PLUGIN_PREFIX . 'payment_id' , $payment->id , true ) ;

        if( 'payment-plans' === $payment->get_payment_type() ) {
            wc_add_order_item_meta( $renewal_item_id , __( 'Payment Plan' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $payment->get_plan()->post_title , true ) ;
        }

        wc_add_order_item_meta( $renewal_item_id , __( 'Total payable' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , wc_price( $payment->get_total_payable_amount() , array( 'currency' => $initial_payment_order->get_currency() ) ) , true ) ;

        //Consider > 1 upon validate, since we excluding this unpaid order
        if( $args[ 'remaining_installments' ] > 1 ) {
            $next_of_next_installment_count = 1 + $args[ 'next_installment_count' ] ;
            $due_date_label_deprecated      = str_replace( ':' , '' , get_option( SUMO_PP_PLUGIN_PREFIX . 'next_payment_date_label' ) ) ;

            if( $due_date_label_deprecated && false === strpos( $due_date_label_deprecated , '[sumo_pp_next_payment_date]' ) ) {
                $due_date_label = $due_date_label_deprecated ;
            } else {
                //$due_date_label = __( 'Next Payment Date' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ;
                $due_date_label = __( 'Final Payment Date' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ;
            }

            wc_add_order_item_meta( $renewal_item_id , __( 'Next installment amount' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , wc_price( $payment->get_next_installment_amount( $next_of_next_installment_count ) , array( 'currency' => $initial_payment_order->get_currency() ) ) , true ) ;
            wc_add_order_item_meta( $renewal_item_id , $due_date_label , _sumo_pp_get_date_to_display( $payment->get_next_payment_date( $next_of_next_installment_count ) ) , true ) ;
        }

        if( ! empty( $item_meta[ SUMO_PP_PLUGIN_PREFIX . 'payment_data' ] ) ) {
            wc_add_order_item_meta( $renewal_item_id , SUMO_PP_PLUGIN_PREFIX . 'payment_data' , $item_meta[ SUMO_PP_PLUGIN_PREFIX . 'payment_data' ] , true ) ;
        }
    }

    /**
     * Extract shipping method from Initial payment Order and set in balance payable Order.
     */
    public static function set_shipping_methods( $initial_payment_order , &$balance_payable_order , $payment ) {
        if( ! ($shipping_methods = $initial_payment_order->order->get_shipping_methods()) || sizeof( $initial_payment_order->order->get_items() ) > 1 ) {
            return ;
        }

        do_action( 'sumopaymentplans_before_adding_shippping_in_payment_order' , $initial_payment_order , $balance_payable_order , $payment ) ;

        if( _sumo_pp_is_wc_version( '<' , '3.0' ) ) {
            return ;
        }

        foreach( $shipping_methods as $item_id => $shipping_rate ) {
            $item = new WC_Order_Item_Shipping() ;
            $item->set_props( array(
                'method_title' => $shipping_rate[ 'name' ] ,
                'method_id'    => $shipping_rate[ 'id' ] ,
                'total'        => wc_format_decimal( $shipping_rate[ 'total' ] ) ,
                'taxes'        => $shipping_rate[ 'taxes' ] ,
                'order_id'     => $balance_payable_order->get_id() ,
            ) ) ;

            foreach( $shipping_rate->get_meta_data() as $key => $value ) {
                $item->add_meta_data( $key , $value , true ) ;
            }

            $item->save() ;
            $balance_payable_order->order->add_item( $item ) ;
        }
    }

    /**
     * Extract Taxes from Initial payment Order and set in balance payable Order 
     */
    public static function set_tax( $initial_payment_order , &$balance_payable_order ) {
        if( _sumo_pp_is_wc_version( '<' , '3.0' ) || ( ! $taxes = $initial_payment_order->order->get_taxes()) ) {
            return ;
        }

        foreach( $taxes as $key => $tax ) {
            $item = new WC_Order_Item_Tax() ;
            $item->set_props( array(
                'rate_id'            => $tax[ 'rate_id' ] ,
                'tax_total'          => $tax[ 'tax_total' ] ,
                'shipping_tax_total' => 0 ,
                'order_id'           => $balance_payable_order->order_id ,
            ) ) ;

            $item->save() ;
            $balance_payable_order->order->add_item( $item ) ;
        }
    }

}
