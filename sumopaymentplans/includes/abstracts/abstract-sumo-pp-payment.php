<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Abstract Payment
 * 
 * @abstract SUMO_PP_Abstract_Payment
 */
abstract class SUMO_PP_Abstract_Payment {

    public $id          = 0 ;
    public $payment     = false ;
    public $status      = '' ;
    public $balance_payable_order ;
    public $post_type   = 'sumo_pp_payments' ;
    public $prefix      = SUMO_PP_PLUGIN_PREFIX ;
    public $text_domain = SUMO_PP_PLUGIN_TEXT_DOMAIN ;

    /**
     * Populate Payment.
     */
    protected function populate( $payment ) {
        if ( ! is_null( $payment ) ) {
            if ( is_numeric( $payment ) ) {
                $this->id = absint( $payment ) ;
                $this->get_payment( $this->id ) ;
            } elseif ( $payment instanceof SUMO_PP_Payment ) {
                $this->id = absint( $payment->id ) ;
                $this->get_payment( $this->id ) ;
            } elseif ( isset( $payment->ID ) ) {
                $this->id = absint( $payment->ID ) ;
                $this->get_payment( $this->id ) ;
            }
        }
    }

    public function get_payment( $id ) {
        if ( ! $id ) {
            return false ;
        }

        if ( $this->post_type === get_post_type( $id ) ) {
            $this->payment = get_post( $id ) ;
            $this->status  = $this->get_status() ;
            return $this ;
        }
        return false ;
    }

    public function get_id() {
        return $this->id ;
    }

    public function get_status( $prefix = false ) {
        if ( $prefix ) {
            return $this->payment->post_status ;
        }
        return str_replace( $this->prefix , '' , $this->payment->post_status ) ;
    }

    public function get_status_label() {
        $payment_statuses = _sumo_pp_get_payment_statuses() ;
        return isset( $payment_statuses[ $this->prefix . $this->status ] ) ? esc_attr( $payment_statuses[ $this->prefix . $this->status ] ) : $this->payment->post_status ;
    }

    public function get_payment_number() {
        return $this->get_prop( 'payment_number' ) ;
    }

    public function get_customer_id() {
        return absint( $this->get_prop( 'customer_id' ) ) ;
    }

    public function get_customer_email() {
        return $this->get_prop( 'customer_email' ) ;
    }

    public function get_customer() {
        return $this->get_prop( 'get_customer' ) ;
    }

    public function get_product_id() {
        return absint( $this->get_prop( 'product_id' ) ) ;
    }

    public function get_product_qty() {
        return absint( $this->get_prop( 'product_qty' ) ) ? absint( $this->get_prop( 'product_qty' ) ) : 1 ;
    }

    public function get_product_price() {
        return floatval( $this->get_prop( 'product_price' ) ) ;
    }

    public function get_product_type() {
        return $this->get_prop( 'product_type' ) ;
    }

    public function get_plan_price_type() {
        return $this->get_prop( 'plan_price_type' ) ;
    }

    public function get_product_amount() {
        return $this->get_product_price() * $this->get_product_qty() ;
    }

    public function get_down_payment( $calc_qty = true ) {
        $down_payment = $this->is_version( '<' , '3.7' ) ? $this->get_prop( 'deposited_amount' ) : $this->get_prop( 'down_payment' ) ;

        if ( $calc_qty ) {
            return floatval( $down_payment ) * $this->get_product_qty() ;
        }
        return floatval( $down_payment ) ;
    }

    public function get_initial_payment_order_id() {
        return absint( $this->get_prop( 'initial_payment_order_id' ) ) ;
    }

    public function get_balance_paid_orders() {
        return is_array( $this->get_prop( 'balance_paid_orders' ) ) ? $this->get_prop( 'balance_paid_orders' ) : array () ;
    }

    public function get_balance_payable_order_props() {
        return is_array( $this->get_prop( 'balance_payable_order_props' ) ) ? $this->get_prop( 'balance_payable_order_props' ) : array () ;
    }

    public function get_balance_payable_order_id( $created_via = 'default' ) {
        $balance_payable_order = $this->get_balance_payable_order_props() ;

        if ( ! empty( $balance_payable_order ) ) {
            foreach ( $balance_payable_order as $id => $data ) {
                if ( ! empty( $data[ 'created_via' ] ) && $created_via === $data[ 'created_via' ] ) {
                    return absint( $id ) ;
                }
            }
        } else if ( 'default' === $created_via ) {
            return absint( $this->get_prop( 'balance_payable_order_id' ) ) ;
        }
        return false ;
    }

    public function charge_tax_during() {
        return $this->get_prop( 'charge_tax_during' ) ;
    }

    public function charge_shipping_during() {
        if( 'order' === $this->get_product_type() ) {
            return '' ;
        }

        $charge_shipping = $this->get_prop( 'charge_shipping_during' ) ;

        if( '' === $charge_shipping ) {
            return 'initial-payment' ;
        }

        $initial_payment_order = _sumo_pp_get_order( $this->get_initial_payment_order_id() ) ;

        if( ! $initial_payment_order || sizeof( $initial_payment_order->order->get_items() ) > 1 ) {
            return 'initial-payment' ;
        }

        return $charge_shipping ;
    }

    public function get_formatted_product_name( $args = array () ) {
        $args = wp_parse_args( $args , array (
            'tips'           => true ,
            'maybe_variable' => true ,
            'qty'            => true ,
            'esc_html'       => false ,
            'page'           => 'frontend' ,
                ) ) ;

        if ( 'order' === $this->get_product_type() ) {
            $product_title = get_option( $this->prefix . 'order_payment_plan_label' ) ;
            $item_title    = array () ;

            foreach ( $this->get_prop( 'order_items' ) as $item_id => $item ) {
                if ( ! $product = wc_get_product( $item_id ) ) {
                    continue ;
                }

                if ( $args[ 'maybe_variable' ] && $product->get_parent_id() ) {
                    if ( ! $product = wc_get_product( $product->get_parent_id() ) ) {
                        continue ;
                    }
                }

                if ( $args[ 'esc_html' ] ) {
                    $item_title[] = $product->get_title() . ($args[ 'qty' ] ? "x{$item[ 'qty' ]}" : '') ;
                } else {
                    $item_title[] = $product->get_title() . ($args[ 'qty' ] ? "&nbsp;&nbsp;x{$item[ 'qty' ]}" : '') ;
                }
            }

            if ( $args[ 'esc_html' ] ) {
                $product_title .= ' - ' . implode( ', ' , $item_title ) ;
            } else if ( $args[ 'tips' ] ) {
                $product_title = sprintf( __( '<a href="#" class="%s" data-tip="%s">%s</a>' ) , "{$this->prefix}tips" , implode( ',<br>' , $item_title ) , $product_title ) ;
            } else if ( $args[ 'qty' ] ) {
                $product_title .= ' --><br>' . implode( ',<br>' , $item_title ) ;
            }
        } else {
            if ( ! $product = wc_get_product( $this->get_product_id() ) ) {
                return '--' ;
            }

            if ( $args[ 'maybe_variable' ] && $product->get_parent_id() ) {
                if ( ! $product = wc_get_product( $product->get_parent_id() ) ) {
                    return '--' ;
                }
            }

            if ( 'admin' === $args[ 'page' ] ) {
                $product_url = admin_url( "post.php?post={$product->get_id()}&action=edit" ) ;
            } else {
                $product_url = get_permalink( $product->get_id() ) ;
            }

            $product_title = $product->get_title() ;
            $maybe_add_qty = $args[ 'qty' ] ? "&nbsp;&nbsp;x{$this->get_product_qty()}" : '' ;

            if ( $args[ 'esc_html' ] ) {
                $product_title .= $args[ 'qty' ] ? "x{$this->get_product_qty()}" : '' ;
            } else if ( $args[ 'tips' ] ) {
                $product_title = sprintf( __( '<a href="%s" class="%s" data-tip="%s%s">%s</a>' ) , $product_url , "{$this->prefix}tips" , $product_title , $maybe_add_qty , $product_title ) ;
            } else if ( $maybe_add_qty ) {
                $product_title .= $maybe_add_qty ;
            }
        }
        return $product_title ;
    }

    public function get_payment_type( $view = false ) {
        if ( $view ) {
            return ucfirst( str_replace( '-' , ' ' , $this->get_prop( 'payment_type' ) ) ) ;
        }
        return $this->get_prop( 'payment_type' ) ;
    }

    public function get_plan() {
        return get_post( $this->get_prop( 'plan_id' ) ) ;
    }

    public function get_pay_balance_type() {
        return $this->get_prop( 'pay_balance_type' ) ;
    }

    public function get_pay_balance_before() {
        return $this->get_prop( 'pay_balance_before' ) ;
    }

    public function get_pay_balance_after() {
        if ( $this->is_version( '<' , '1.4' ) ) {
            $pay_balance_after = absint( $this->get_prop( 'balance_payment_due' ) ) ; //in days
        } else {
            $pay_balance_after = absint( $this->get_prop( 'pay_balance_after' ) ) ; //in days
        }
        return $pay_balance_after ;
    }

    public function get_view_endpoint_url() {
        global $post;
        
        if( _sumo_pp_is_my_payments_page() ) {
            $payment_endpoint = wc_get_endpoint_url( 'sumo-pp-view-payment' , $this->id ,  ! empty( $post->ID ) ? get_permalink( $post->ID ) : get_home_url() ) ;
        } else {
            if( _sumo_pp_is_wc_version( '<' , '2.6' ) ) {
                $payment_endpoint = esc_url_raw( add_query_arg( array( 'q' => 'sumo-pp-view-payment' , 'payment-id' => $this->id ) ) ) ;
            } else {
                $payment_endpoint = wc_get_endpoint_url( 'sumo-pp-view-payment' , $this->id , wc_get_page_permalink( 'myaccount' ) ) ;
            }
        }
        return $payment_endpoint ;
    }

    public function get_total_installments() {
        $total_installments = 0 ;

        if ( 'payment-plans' === $this->get_payment_type() ) {
            $total_installments = sizeof( array_filter( array_map( function( $schedule ) {
                                return isset( $schedule[ 'scheduled_payment' ] ) ? $schedule : null ;
                            } , is_array( $this->get_prop( 'payment_schedules' ) ) ? $this->get_prop( 'payment_schedules' ) : array ()  ) ) ) ;
        } else if ( 'pay-in-deposit' === $this->get_payment_type() ) {
            if ( 0 === $this->get_next_installment_count() ) {
                $total_installments = 1 ;
            }
        }
        return $total_installments ;
    }

    public function get_next_installment_count() {
        return sizeof( $this->get_balance_paid_orders() ) ;
    }

    public function get_next_of_next_installment_count() {
        return 1 + $this->get_next_installment_count() ;
    }

    public function email_sending() {
        return '1' === $this->get_prop( 'email_sending_flag' ) ;
    }

    public function exists() {
        if ( $this->payment && in_array( $this->payment->post_status , array_keys( _sumo_pp_get_payment_statuses() ) ) ) {
            return true ;
        }
        return false ;
    }

    public function has_status( $status ) {
        if ( is_array( $status ) ) {
            return in_array( $this->get_status() , $status ) || in_array( $this->get_status( true ) , $status ) ;
        }
        return $status === $this->get_status() || $status === $this->get_status( true ) ;
    }

    public function is_version( $comparison_opr , $version ) {
        return version_compare( $this->get_prop( 'version' ) , $comparison_opr , $version ) ;
    }

    public function is_synced() {
        return 'enabled' === $this->get_prop( 'sync' ) ;
    }

    public function has_next_installment() {
        return $this->get_remaining_installments() > 0 ? true : false ;
    }

    public function awaiting_initial_payment() {
        return ($this->is_synced() && $this->get_down_payment() <= 0 && $this->has_status( 'pending' )) ;
    }

    public function balance_payable_order_exists( $created_via = 'default' ) {
        $this->balance_payable_order = _sumo_pp_get_order( $this->get_balance_payable_order_id( $created_via ) ) ;

        if ( $this->balance_payable_order && ! $this->balance_payable_order->has_status( array ( 'completed' , 'processing' ) ) ) {
            return true ;
        }
        return false ;
    }

    public function is_expected_payment_dates_modified() {
        $modified_dates = $this->get_prop( 'modified_expected_payment_dates' ) ;
        return is_array( $modified_dates ) && ! empty( $modified_dates ) ? true : false ;
    }

    public function set_payment_serial_number() {
        $custom_prefix  = esc_attr( get_option( $this->prefix . 'payment_number_prefix' , '' ) ) ;
        $last_serial_no = absint( get_option( $this->prefix . 'payment_serial_number' , '1' ) ) ;
        $new_serial_no  = $last_serial_no ? 1 + $last_serial_no : 1 ;

        update_option( $this->prefix . 'payment_serial_number' , $new_serial_no ) ;
        return $custom_prefix . $new_serial_no ;
    }

    public function set_email_sending_flag() {
        $this->add_prop( 'email_sending_flag' , '1' ) ;
    }

    public function set_email_sent_flag() {
        $this->delete_prop( 'email_sending_flag' ) ;
    }

    public function get_prop( $context = '' ) {
        return get_post_meta( $this->id , "_{$context}" , true ) ;
    }

    public function add_prop( $context , $value ) {
        return add_post_meta( $this->id , "_{$context}" , $value ) ;
    }

    public function update_prop( $context , $value ) {
        return update_post_meta( $this->id , "_{$context}" , $value ) ;
    }

    public function delete_prop( $context ) {
        return delete_post_meta( $this->id , "_{$context}" ) ;
    }







// 2TON Added
    public function get_formatted_product_name_2ton( $args = array () ) {
        $args = wp_parse_args( $args , array (
            'tips'           => true ,
            'maybe_variable' => true ,
            'qty'            => true ,
            'esc_html'       => false ,
            'page'           => 'frontend' ,
                ) ) ;

        if ( 'order' === $this->get_product_type() ) {
            $product_title = get_option( $this->prefix . 'order_payment_plan_label' ) ;
            $item_title    = array () ;

            foreach ( $this->get_prop( 'order_items' ) as $item_id => $item ) {
                if ( ! $product = wc_get_product( $item_id ) ) {
                    continue ;
                }

                if ( $args[ 'maybe_variable' ] && $product->get_parent_id() ) {
                    if ( ! $product = wc_get_product( $product->get_parent_id() ) ) {
                        continue ;
                    }
                }

                if ( $args[ 'esc_html' ] ) {
                    $item_title[] = $product->get_title() . ($args[ 'qty' ] ? "x{$item[ 'qty' ]}" : '') ;
                } else {
                    $item_title[] = $product->get_title() . ($args[ 'qty' ] ? "&nbsp;&nbsp;x{$item[ 'qty' ]}" : '') ;
                }
            }

            if ( $args[ 'esc_html' ] ) {
                $product_title .= ' - ' . implode( ', ' , $item_title ) ;
            } else if ( $args[ 'tips' ] ) {
                $product_title = sprintf( __( '<a href="#" class="%s" data-tip="%s">%s</a>' ) , "{$this->prefix}tips" , implode( ',<br>' , $item_title ) , $product_title ) ;
            } else if ( $args[ 'qty' ] ) {
                $product_title .= ' --><br>' . implode( ',<br>' , $item_title ) ;
            }
        } else {
            if ( ! $product = wc_get_product( $this->get_product_id() ) ) {
                return '--' ;
            }

            if ( $args[ 'maybe_variable' ] && $product->get_parent_id() ) {
                if ( ! $product = wc_get_product( $product->get_parent_id() ) ) {
                    return '--' ;
                }
            }

            if ( 'admin' === $args[ 'page' ] ) {
                $product_url = admin_url( "post.php?post={$product->get_id()}&action=edit" ) ;
            } else {
                $product_url = get_permalink( $product->get_id() ) ;
            }

            $product_title = $product->get_title() ;
            $maybe_add_qty = $args[ 'qty' ] ? "&nbsp;&nbsp;x{$this->get_product_qty()}" : '' ;

            if ( $args[ 'esc_html' ] ) {
                $product_title .= $args[ 'qty' ] ? "x{$this->get_product_qty()}" : '' ;
            } else if ( $args[ 'tips' ] ) {
                //$product_title = sprintf( __( '<a href="%s" class="%s" data-tip="%s%s">%s</a>' ) , $product_url , "{$this->prefix}tips" , $product_title , $maybe_add_qty , $product_title ) ;
                $product_title = sprintf( __( '%s' ) , $product_title , $maybe_add_qty , $product_title ) ;
            } else if ( $maybe_add_qty ) {
                $product_title .= $maybe_add_qty ;
            }
        }
        return $product_title ;
    }


}