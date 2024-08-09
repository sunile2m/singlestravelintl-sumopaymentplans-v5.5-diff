<?php

if( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

function _sumo_pp_payment_exists( $payment_id ) {
    return _sumo_pp_get_payment( $payment_id ) ? true : false ;
}

function _sumo_pp_get_payment_number( $payment_id ) {
    if( $payment = _sumo_pp_get_payment( $payment_id ) ) {
        return $payment->get_payment_number() ;
    }
    return 0 ;
}

function _sumo_pp_payment_has_status( $payment_id , $status ) {
    if( $payment = _sumo_pp_get_payment( $payment_id ) ) {
        return $payment->has_status( $status ) ;
    }
    return false ;
}

function _sumo_pp_get_formatted_payment_product_title( $payment_id , $args = array() ) {
    if( $payment = _sumo_pp_get_payment( $payment_id ) ) {
        return $payment->get_formatted_product_name( $args ) ;
    }
    return '' ;
}

function _sumo_pp_payment_has_next_installment( $payment_id ) {
    if( $payment = _sumo_pp_get_payment( $payment_id ) ) {
        return $payment->has_next_installment() ;
    }
    return false ;
}

function _sumo_pp_get_payment_status( $payment_id ) {
    $payment_status_label = $payment_status       = '' ;

    if( $payment = _sumo_pp_get_payment( $payment_id ) ) {
        $payment_status       = $payment->get_status( true ) ;
        $payment_status_label = $payment->get_status_label() ;
    }
    return array(
        'label' => $payment_status_label ,
        'name'  => $payment_status ,
            ) ;
}

function _sumo_pp_update_payment_status( $payment_id , $payment_status ) {
    if( $payment = _sumo_pp_get_payment( $payment_id ) ) {
        return $payment->update_status( $payment_status ) ;
    }
    return false ;
}

function _sumo_pp_get_payment_end_date( $payment_id ) {
    if( $payment = _sumo_pp_get_payment( $payment_id ) ) {
        return $payment->get_payment_end_date() ;
    }
    return '' ;
}

function _sumo_pp_get_next_payment_date( $payment_id , $next_of_next = false ) {
    if( $payment = _sumo_pp_get_payment( $payment_id ) ) {
        if( $next_of_next ) {
            $installment = $payment->get_next_of_next_installment_count() ;
        } else {
            $installment = null ;
        }
        return $payment->get_next_payment_date( $installment ) ;
    }
    return '' ;
}

function _sumo_pp_is_balance_payable_order_exists( $payment_id ) {
    if( $payment = _sumo_pp_get_payment( $payment_id ) ) {
        return $payment->balance_payable_order_exists() ;
    }
    return false ;
}

function _sumo_pp_get_balance_paid_orders( $payment_id ) {
    if( $payment = _sumo_pp_get_payment( $payment_id ) ) {
        return $payment->get_balance_paid_orders() ;
    }
    return array() ;
}

function _sumo_pp_get_next_installment_amount( $payment_id , $next_of_next = false ) {
    if( $payment = _sumo_pp_get_payment( $payment_id ) ) {
        if( $next_of_next ) {
            $installment = $payment->get_next_of_next_installment_count() ;
        } else {
            $installment = null ;
        }
        return $payment->get_next_installment_amount( $installment ) ;
    }
    return 0 ;
}

function _sumo_pp_get_remaining_installments( $payment_id ) {
    if( $payment = _sumo_pp_get_payment( $payment_id ) ) {
        return $payment->get_remaining_installments() ;
    }
    return 0 ;
}

function _sumo_pp_get_remaining_payable_amount( $payment_id , $next_of_next = false ) {
    if( $payment = _sumo_pp_get_payment( $payment_id ) ) {
        if( $next_of_next ) {
            $installment = $payment->get_next_of_next_installment_count() ;
        } else {
            $installment = null ;
        }
        return $payment->get_remaining_payable_amount( $installment ) ;
    }
    return 0 ;
}

function _sumo_pp_get_total_payable_amount( $payment_id ) {
    if( $payment = _sumo_pp_get_payment( $payment_id ) ) {
        return $payment->get_total_payable_amoun() ;
    }
    return 0 ;
}

function _sumo_pp_get_payment_notes( $args = array() ) {
    if( $payment = _sumo_pp_get_payment( $args[ 'payment_id' ] ) ) {
        return $payment->get_payment_notes( $args ) ;
    }
    return 0 ;
}

function _sumo_pp_send_payment_email( $payment_id , $template_id , $order_id , $manual = false ) {
    if( $payment = _sumo_pp_get_payment( $payment_id ) ) {
        return $payment->send_payment_email( $template_id , $order_id , $manual ) ;
    }
    return false ;
}

function _sumo_pp_get_initial_payment_order( $order , $check_in_child = true ) {
    if( ! $order = _sumo_pp_get_order( $order ) ) {
        return 0 ;
    }
    return $order->get_parent_id() ;
}

function _sumo_pp_get_cart_data( $product = null , $customer_id = 0 ) {
    if( SUMO_PP_Cart_Manager::cart_contains_payment() ) {
        if( ! is_null( $product ) ) {
            $product_id = 0 ;
            if( is_callable( array( $product , 'get_id' ) ) ) {
                $product_id = $product->get_id() ;
            } else {
                if( $product = wc_get_product( $product ) ) {
                    $product_id = $product->get_id() ;
                }
            }

            foreach( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
                if( ! empty( $cart_item[ 'sumopaymentplans' ][ 'product_id' ] ) && $product_id === $cart_item[ 'sumopaymentplans' ][ 'product_id' ] ) {
                    return $cart_item[ 'sumopaymentplans' ] ;
                }
            }
        } else {
            $item_session = array() ;
            foreach( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
                if( ! empty( $cart_item[ 'sumopaymentplans' ][ 'product_id' ] ) ) {
                    $item_session[ $cart_item[ 'sumopaymentplans' ][ 'product_id' ] ] = $cart_item[ 'sumopaymentplans' ] ;
                }
            }
            return $item_session ;
        }
    }
    return null ;
}

function _sumo_pp_is_payment_product( $product , $customer_id = 0 ) {
    return SUMO_PP_Cart_Manager::is_payment_item( $product ) ;
}

function _sumo_pp_get_payment_data( $product = null ) {
    $meta_data = array() ;
    $payment   = SUMO_PP_Cart_Manager::is_payment_item( $product ) ;

    if( empty( $payment[ 'payment_product_props' ][ 'payment_type' ] ) ) {
        return $meta_data ;
    }

    if( 'payment-plans' === $payment[ 'payment_product_props' ][ 'payment_type' ] ) {
        $meta_data[ 'plan_name' ]        = SUMO_PP_Cart_Manager::get_payment_info_to_display( $payment , 'plan_name' ) ;
        $meta_data[ 'plan_description' ] = $payment[ 'payment_plan_props' ][ 'plan_description' ] ;
    }

    $meta_data[ 'product_price' ]        = $payment[ 'payment_product_props' ][ 'product_price' ] ;
    $meta_data[ 'total_payable_amount' ] = $payment[ 'total_payable_amount' ] ;
    return $meta_data ;
}

function _sumo_pp_get_cart_balance_payable_amount() {
    return SUMO_PP_Cart_Manager::get_cart_balance_payable_amount() ;
}

function _sumo_pp_get_cart_payment_display_string( $product ) {
    if( ! $payment = SUMO_PP_Cart_Manager::is_payment_item( $product ) ) {
        return array() ;
    }

    return array(
        'under_product_column' => SUMO_PP_Cart_Manager::get_payment_info_to_display( $payment , 'plan_name' ) ,
        'under_price_column'   => SUMO_PP_Cart_Manager::get_payment_info_to_display( $payment ) ,
        'under_total_column'   => SUMO_PP_Cart_Manager::get_payment_info_to_display( $payment , 'balance_payable' ) ,
            ) ;
}

function _sumo_pp_set_payment_session( $_payment_data ) {

    $prefix           = SUMO_PP_PLUGIN_PREFIX ;
    $product_id       = isset( $_payment_data[ 'payment_product_props' ][ 'product_id' ] ) ? $_payment_data[ 'payment_product_props' ][ 'product_id' ] : null ;
    $quantity         = isset( $_payment_data[ 'product_qty' ] ) ? absint( $_payment_data[ 'product_qty' ] ) : 1 ;
    $deposited_amount = isset( $_payment_data[ 'deposited_amount' ] ) ? $_payment_data[ 'deposited_amount' ] : null ;

    if( ! $product_id = _sumo_pp_get_product_id( $product_id ) ) {
        return false ;
    }

    if( $SUMO_Payment_Plans_is_enabled = 'yes' === get_post_meta( $product_id , "{$prefix}enable_sumopaymentplans" , true ) ) {
        foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            if( ! empty( $cart_item[ 'variation_id' ] ) ) {
                $item_id = $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ] ;

                if( $item_id == $product_id ) {
                    $payment_data = SUMO_PP_Data_Manager::get_payment_data( array(
                                'product_props'    => $_payment_data[ 'payment_product_props' ] ,
                                'plan_props'       => $_payment_data[ 'payment_plan_props' ] ,
                                'deposited_amount' => $quantity ,
                                'qty'              => $deposited_amount ,
                                'item_meta'        => $cart_item ,
                            ) ) ;

                    if( empty( $payment_data[ 'payment_product_props' ][ 'payment_type' ] ) ) {
                        WC()->cart->cart_contents[ $cart_item_key ][ 'sumopaymentplans' ] = array() ;
                        continue ;
                    }

                    switch( $payment_data[ 'payment_product_props' ][ 'payment_type' ] ) {
                        case 'payment-plans':
                            if(
                                    empty( $payment_data[ 'payment_plan_props' ][ 'payment_schedules' ] ) ||
                                    empty( $payment_data[ 'payment_product_props' ][ 'selected_plans' ] )
                            ) {
                                WC()->cart->cart_contents[ $cart_item_key ][ 'sumopaymentplans' ] = array() ;
                                continue 2 ;
                            }

                            $plans_col_1 = ! empty( $payment_data[ 'payment_product_props' ][ 'selected_plans' ][ 'col_1' ] ) ? $payment_data[ 'payment_product_props' ][ 'selected_plans' ][ 'col_1' ] : array() ;
                            $plans_col_2 = ! empty( $payment_data[ 'payment_product_props' ][ 'selected_plans' ][ 'col_2' ] ) ? $payment_data[ 'payment_product_props' ][ 'selected_plans' ][ 'col_2' ] : array() ;

                            if( ! in_array( $payment_data[ 'payment_plan_props' ][ 'plan_id' ] , $plans_col_1 ) && ! in_array( $payment_data[ 'payment_plan_props' ][ 'plan_id' ] , $plans_col_2 ) ) {
                                WC()->cart->cart_contents[ $cart_item_key ][ 'sumopaymentplans' ] = array() ;
                                continue 2 ;
                            }
                            break ;
                        case 'pay-in-deposit':
                            break ;
                    }
                    WC()->cart->cart_contents[ $cart_item_key ][ 'sumopaymentplans' ] = $payment_data ;
                    break ;
                }
            }
        }
        return true ;
    }
    return false ;
}
