<?php

if( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle SUMO Payment Plans Ajax Event.
 * 
 * @class SUMO_PP_Ajax
 * @category Class
 */
class SUMO_PP_Ajax {

    /**
     * Init SUMO_PP_Ajax.
     */
    public static function init() {
        //Get Ajax Events.
        $prefix      = SUMO_PP_PLUGIN_PREFIX ;
        $ajax_events = array(
            'add_payment_note'                  => false ,
            'delete_payment_note'               => false ,
            'get_wc_booking_deposit_fields'     => true ,
            'get_payment_plan_search_field'     => false ,
            'checkout_order_payment_plan'       => true ,
            'pay_remaining_custom_installments' => false ,
            'init_data_export'                  => false ,
            'handle_exported_data'              => false ,
            'bulk_update_product_meta'          => false ,
            'json_search_payment_plans'         => false ,
            'json_search_customers_by_email'    => false ,
                ) ;

        foreach( $ajax_events as $ajax_event => $nopriv ) {
            add_action( "wp_ajax_{$prefix}{$ajax_event}" , __CLASS__ . "::{$ajax_event}" ) ;

            if( $nopriv ) {
                add_action( "wp_ajax_nopriv_{$prefix}{$ajax_event}" , __CLASS__ . "::{$ajax_event}" ) ;
            }
        }
    }

    /**
     * Admin manually add payment notes.
     */
    public static function add_payment_note() {

        check_ajax_referer( 'sumo-pp-add-payment-note' , 'security' ) ;

        $payment = _sumo_pp_get_payment( $_POST[ 'post_id' ] ) ;
        $note    = $payment->add_payment_note( $_POST[ 'content' ] , 'pending' , __( 'Admin Manually Added Note' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ) ;

        if( $note = $payment->get_payment_note( $note ) ) {
            include( 'admin/views/html-payment-note.php' ) ;
        }
        die() ;
    }

    /**
     * Admin manually delete payment notes.
     */
    public static function delete_payment_note() {

        check_ajax_referer( 'sumo-pp-delete-payment-note' , 'security' ) ;

        wp_send_json( wp_delete_comment( $_POST[ 'delete_id' ] , true ) ) ;
    }

    public static function get_wc_booking_deposit_fields() {

        check_ajax_referer( 'sumo-pp-get-payment-type-fields' , 'security' ) ;

        $product_props        = SUMO_PP_Product_Manager::get_product_props( $_POST[ 'product' ] ) ;
        $can_add_booking_cost = false ;

        if( class_exists( 'SUMO_PP_WC_Bookings' ) && SUMO_PP_WC_Bookings::can_add_booking_cost( $product_props ) ) {
            $can_add_booking_cost = true ;
        } else if( class_exists( 'SUMO_PP_YITH_WC_Bookings' ) && SUMO_PP_YITH_WC_Bookings::can_add_booking_cost( $product_props ) ) {
            $can_add_booking_cost = true ;
        } else if( class_exists( 'SUMO_PP_SUMOBookings' ) && SUMO_PP_SUMOBookings::can_add_booking_cost( $product_props ) ) {
            $can_add_booking_cost = true ;
        }

        if( $can_add_booking_cost ) {
            wp_send_json( array(
                'result' => 'success' ,
                'html'   => SUMO_PP_Product_Manager::get_payment_type_fields() ,
            ) ) ;
        }

        wp_send_json( array(
            'result' => 'failure' ,
            'html'   => '' ,
        ) ) ;
    }

    /**
     * Get Payment Plan search field
     */
    public static function get_payment_plan_search_field() {

        check_ajax_referer( 'sumo-pp-get-payment-plan-search-field' , 'security' ) ;

        wp_send_json( array(
            'search_field' => _sumo_pp_wc_search_field( array(
                'class'       => 'wc-product-search' ,
                'action'      => '_sumo_pp_json_search_payment_plans' ,
                'id'          => isset( $_POST[ 'loop' ] ) ? "selected_{$_POST[ 'col' ]}_payment_plan_{$_POST[ 'rowID' ]}{$_POST[ 'loop' ]}" : "selected_{$_POST[ 'col' ]}_payment_plan_{$_POST[ 'rowID' ]}" ,
                'name'        => isset( $_POST[ 'loop' ] ) ? "_sumo_pp_selected_plans[{$_POST[ 'loop' ]}][{$_POST[ 'col' ]}][{$_POST[ 'rowID' ]}]" : "_sumo_pp_selected_plans[{$_POST[ 'col' ]}][{$_POST[ 'rowID' ]}]" ,
                'type'        => 'payment_plans' ,
                'selected'    => false ,
                'multiple'    => false ,
                'placeholder' => __( 'Search for a payment plan&hellip;' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                    ) , false ) ,
        ) ) ;
    }

    /**
     * Save order payment plan.
     */
    public static function checkout_order_payment_plan() {

        check_ajax_referer( 'sumo-pp-checkout-order-payment-plan' , 'security' ) ;

        WC()->session->__unset( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_enabled' ) ;
        WC()->session->__unset( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_deposited_amount' ) ;
        WC()->session->__unset( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_chosen_payment_plan' ) ;

        if( 'yes' === $_POST[ 'enabled' ] ) {
            WC()->session->set( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_enabled' , 'yes' ) ;

            switch( wc_clean( $_POST[ 'payment_type' ] ) ) {
                case 'pay-in-deposit':
                    if( isset( $_POST[ 'deposited_amount' ] ) ) {
                        WC()->session->set( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_deposited_amount' , $_POST[ 'deposited_amount' ] ) ;
                    }
                    break ;
                case 'payment-plans':
                    if( isset( $_POST[ 'chosen_payment_plan' ] ) ) {
                        WC()->session->set( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_chosen_payment_plan' , $_POST[ 'chosen_payment_plan' ] ) ;
                    }
                    break ;
            }
        }
        die() ;
    }

    public static function pay_remaining_custom_installments() {

        check_ajax_referer( 'sumo-pp-myaccount' , 'security' ) ;

        if( ! $payment = _sumo_pp_get_payment( $_POST[ 'payment_id' ] ) ) {
            wp_send_json( array(
                'result'   => 'failure' ,
                'redirect' => $payment->get_view_endpoint_url() ,
                'notice'   => __( 'Invalid Payment!!' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            ) ) ;
        }

        if( 'pay-remaining' === $_POST[ 'selected_installments' ] ) {
            if( $payment->balance_payable_order_exists( 'my_account' ) ) {
                wp_delete_post( $payment->balance_payable_order->order_id ) ;
            }
            $next_installment_amount       = 0 ;
            $remaining_unpaid_installments = $payment->get_next_installment_count() + absint( $payment->get_prop( 'remaining_installments' ) ) ;

            for( $unpaid_installment = $payment->get_next_installment_count() ; $unpaid_installment < $remaining_unpaid_installments ; $unpaid_installment ++ ) {
                $next_installment_amount += $payment->get_next_installment_amount( $unpaid_installment ) ;
            }

            $balance_payable_order_id = SUMO_PP_Order_Manager::create_balance_payable_order( $payment , array(
                        'next_installment_amount' => $next_installment_amount ,
                        'next_installment_count'  => $remaining_unpaid_installments - 1 ,
                        'remaining_installments'  => 0 ,
                        'installments_included'   => absint( $payment->get_prop( 'remaining_installments' ) ) ,
                        'created_via'             => 'my_account' ,
                        'add_default_note'        => false ,
                    ) ) ;
            $balance_payable_order    = _sumo_pp_get_order( $balance_payable_order_id ) ;

            if( $balance_payable_order ) {
                wp_send_json( array(
                    'result'   => 'success' ,
                    'redirect' => $balance_payable_order->get_pay_url() ,
                ) ) ;
            }
        } else if( is_numeric( $_POST[ 'selected_installments' ] ) ) {
            $selected_installments_count = absint( $_POST[ 'selected_installments' ] ) ;

            if( 0 === $selected_installments_count && $payment->balance_payable_order_exists() ) {
                $balance_payable_order = $payment->balance_payable_order ;
            } else {
                if( $payment->balance_payable_order_exists( 'my_account' ) ) {
                    wp_delete_post( $payment->balance_payable_order->order_id ) ;
                }
                $next_installment_amount      = 0 ;
                $next_installment_count       = $payment->get_next_installment_count() ;
                $selected_unpaid_installments = $next_installment_count + $selected_installments_count ;

                for( $unpaid_installment = $payment->get_next_installment_count() ; $unpaid_installment <= $selected_unpaid_installments ; $unpaid_installment ++ ) {
                    $next_installment_amount += $payment->get_next_installment_amount( $unpaid_installment ) ;
                    $next_installment_count = $unpaid_installment ;
                }

                $balance_payable_order_id = SUMO_PP_Order_Manager::create_balance_payable_order( $payment , array(
                            'next_installment_amount' => $next_installment_amount ,
                            'next_installment_count'  => $next_installment_count ,
                            'remaining_installments'  => $payment->get_remaining_installments( $next_installment_count ) ,
                            'installments_included'   => 1 + $selected_installments_count ,
                            'created_via'             => 'my_account' ,
                            'add_default_note'        => false ,
                        ) ) ;
                $balance_payable_order    = _sumo_pp_get_order( $balance_payable_order_id ) ;
            }

            if( $balance_payable_order ) {
                wp_send_json( array(
                    'result'   => 'success' ,
                    'redirect' => $balance_payable_order->get_pay_url() ,
                ) ) ;
            }
        }
    }

    /**
     * Init data export
     */
    public static function init_data_export() {

        check_ajax_referer( 'sumo-pp-payments-exporter' , 'security' ) ;

        $export_databy = array() ;
        parse_str( $_POST[ 'exportDataBy' ] , $export_databy ) ;

        $json_args = array() ;
        $args      = array(
            'type'     => 'sumo_pp_payments' ,
            'status'   => array_keys( _sumo_pp_get_payment_statuses() ) ,
            'order_by' => 'DESC' ,
                ) ;

        if( ! empty( $export_databy ) ) {
            if( ! empty( $export_databy[ 'payment_statuses' ] ) ) {
                $args[ 'status' ] = $export_databy[ 'payment_statuses' ] ;
            }

            if( ! empty( $export_databy[ 'payment_from_date' ] ) ) {
                $to_date              = ! empty( $export_databy[ 'payment_to_date' ] ) ? strtotime( $export_databy[ 'payment_to_date' ] ) : strtotime( date( 'Y-m-d' ) ) ;
                $args[ 'date_query' ] = array(
                    array(
                        'after'     => date( 'Y-m-d' , strtotime( $export_databy[ 'payment_from_date' ] ) ) ,
                        'before'    => array(
                            'year'  => date( 'Y' , $to_date ) ,
                            'month' => date( 'm' , $to_date ) ,
                            'day'   => date( 'd' , $to_date ) ,
                        ) ,
                        'inclusive' => true ,
                    ) ,
                        ) ;
            }

            $meta_query = array() ;
            if( ! empty( $export_databy[ 'payment_products' ] ) ) {
                $meta_query[] = array(
                    'key'     => '_product_id' ,
                    'value'   => ( array ) $export_databy[ 'payment_products' ] ,
                    'compare' => 'IN'
                        ) ;
            }

            if( ! empty( $export_databy[ 'payment_types' ] ) ) {
                $meta_query[] = array(
                    'key'     => '_payment_type' ,
                    'value'   => ( array ) $export_databy[ 'payment_types' ] ,
                    'compare' => 'IN'
                        ) ;
            }

            if( ! empty( $export_databy[ 'payment_plans' ] ) ) {
                $meta_query[] = array(
                    'key'     => '_plan_id' ,
                    'value'   => ( array ) $export_databy[ 'payment_plans' ] ,
                    'compare' => 'IN'
                        ) ;
            }

            if( ! empty( $export_databy[ 'payment_buyers' ] ) ) {
                $meta_query[] = array(
                    'key'     => '_customer_email' ,
                    'value'   => ( array ) $export_databy[ 'payment_buyers' ] ,
                    'compare' => 'IN'
                        ) ;
            }

            if( ! empty( $meta_query ) ) {
                $args[ 'meta_query' ] = array( 'relation' => 'AND' ) + $meta_query ;
            }
        }

        $payments = _sumo_pp()->query->get( $args ) ;

        if( sizeof( $payments ) <= 1 ) {
            $json_args[ 'export' ]         = 'done' ;
            $json_args[ 'generated_data' ] = array_map( array( 'SUMO_PP_Payments_Exporter' , 'generate_data' ) , $payments ) ;
            $json_args[ 'redirect_url' ]   = SUMO_PP_Payments_Exporter::get_download_url( $json_args[ 'generated_data' ] ) ;
        } else {
            $json_args[ 'export' ]        = 'processing' ;
            $json_args[ 'original_data' ] = $payments ;
        }

        wp_send_json( wp_parse_args( $json_args , array(
            'export'         => '' ,
            'generated_data' => array() ,
            'original_data'  => array() ,
            'redirect_url'   => SUMO_PP_Payments_Exporter::get_exporter_page_url() ,
        ) ) ) ;
    }

    /**
     * Handle exported data
     */
    public static function handle_exported_data() {

        check_ajax_referer( 'sumo-pp-payments-exporter' , 'security' ) ;

        $json_args                     = array() ;
        $pre_generated_data            = json_decode( stripslashes( $_POST[ 'generated_data' ] ) ) ;
        $new_generated_data            = array_map( array( 'SUMO_PP_Payments_Exporter' , 'generate_data' ) , array_filter( ( array ) $_POST[ 'chunkedData' ] ) ) ;
        $json_args[ 'generated_data' ] = array_values( array_filter( array_merge( array_filter( ( array ) $pre_generated_data ) , $new_generated_data ) ) ) ;

        if( absint( $_POST[ 'originalDataLength' ] ) === sizeof( $json_args[ 'generated_data' ] ) ) {
            $json_args[ 'export' ]       = 'done' ;
            $json_args[ 'redirect_url' ] = SUMO_PP_Payments_Exporter::get_download_url( $json_args[ 'generated_data' ] ) ;
        }

        wp_send_json( wp_parse_args( $json_args , array(
            'export'         => 'processing' ,
            'generated_data' => array() ,
            'original_data'  => array() ,
            'redirect_url'   => SUMO_PP_Payments_Exporter::get_exporter_page_url() ,
        ) ) ) ;
    }

    /**
     * Process bulk update.
     */
    public static function bulk_update_product_meta() {

        check_ajax_referer( 'bulk-update-payment-plans' , 'security' ) ;

        $product_props = array() ;
        parse_str( $_POST[ 'product_props' ] , $product_props ) ;

        if( empty( $product_props[ 'get_product_select_type' ] ) ) {
            wp_send_json_error( array(
                'productsCount' => 0 ,
            ) ) ;
        }

        //Save the settings
        update_option( 'bulk' . SUMO_PP_PLUGIN_PREFIX . 'get_product_select_type' , wc_clean( $product_props[ 'get_product_select_type' ] ) ) ;
        update_option( 'bulk' . SUMO_PP_PLUGIN_PREFIX . 'get_selected_categories' ,  ! empty( $product_props[ 'get_selected_categories' ] ) ? wc_clean( $product_props[ 'get_selected_categories' ] ) : array()  ) ;
        update_option( 'bulk' . SUMO_PP_PLUGIN_PREFIX . 'get_selected_products' ,  ! empty( $product_props[ 'get_selected_products' ] ) ? wc_clean( is_array( $product_props[ 'get_selected_products' ] ) ? $product_props[ 'get_selected_products' ] : explode( ',' , $product_props[ 'get_selected_products' ] )  ) : array()  ) ;

        foreach( SUMO_PP_Admin_Product::get_payment_fields() as $field_name => $type ) {
            $meta_key         = SUMO_PP_PLUGIN_PREFIX . $field_name ;
            $posted_meta_data = isset( $product_props[ "$meta_key" ] ) ? $product_props[ "$meta_key" ] : '' ;

            if( 'price' === $type ) {
                $posted_meta_data = wc_format_decimal( $posted_meta_data ) ;
            }
            if( 'selected_plans' === $field_name && is_array( $posted_meta_data ) ) {
                $selected_plans = array() ;
                foreach( array( 'col_1' , 'col_2' ) as $column_id ) {
                    $selected_plans[ $column_id ] = ! empty( $posted_meta_data[ $column_id ] ) && is_array( $posted_meta_data[ $column_id ] ) ? array_map( 'implode' , (array_values( $posted_meta_data[ $column_id ] ) ) ) : array() ;
                }
                $posted_meta_data = $selected_plans ;
            }
            update_option( "bulk{$meta_key}" , wc_clean( $posted_meta_data ) ) ;
        }

        $found_products = array() ;
        switch( get_option( 'bulk' . SUMO_PP_PLUGIN_PREFIX . 'get_product_select_type' ) ) {
            case 'all-products':
            case 'all-categories':
                $products = new WP_Query( array(
                    'post_type'      => array( 'product' , 'product_variation' ) ,
                    'posts_per_page' => '-1' ,
                    'post_status'    => 'publish' ,
                    'fields'         => 'ids' ,
                    'cache_results'  => false ,
                    'tax_query'      => array(
                        array(
                            'taxonomy' => 'product_type' ,
                            'field'    => 'slug' ,
                            'terms'    => array( 'variable' , 'grouped' ) ,
                            'operator' => 'NOT IN' ,
                        )
                    ) ,
                        ) ) ;

                if( ! empty( $products->posts ) ) {
                    $found_products = $products->posts ;
                }
                break ;
            case 'selected-products':
                $found_products = get_option( 'bulk' . SUMO_PP_PLUGIN_PREFIX . 'get_selected_products' , array() ) ;
                break ;
            case 'selected-categories':
                $products       = new WP_Query( array(
                    'post_type'      => array( 'product' , 'product_variation' ) ,
                    'post_status'    => 'publish' ,
                    'posts_per_page' => '-1' ,
                    'fields'         => 'ids' ,
                    'cache_results'  => false ,
                    'tax_query'      => array(
                        'relation' => 'AND' ,
                        array(
                            'taxonomy' => 'product_cat' ,
                            'field'    => 'term_id' , // Or 'name' or 'term_id'
                            'terms'    => get_option( 'bulk' . SUMO_PP_PLUGIN_PREFIX . 'get_selected_categories' , array() ) ,
                            'operator' => 'IN' , // Included
                        ) ,
                        array(
                            'taxonomy' => 'product_type' ,
                            'field'    => 'slug' ,
                            'terms'    => array( 'grouped' ) ,
                            'operator' => 'NOT IN' ,
                        )
                    ) ,
                        ) ) ;

                if( ! empty( $products->posts ) ) {
                    $found_products = $products->posts ;
                }
                break ;
        }

        if( empty( $found_products ) ) {
            wp_send_json_error( array(
                'productsCount' => 0 ,
            ) ) ;
        }

        set_transient( SUMO_PP_PLUGIN_PREFIX . 'found_products_to_bulk_update' , $found_products , time() + 60 ) ;

        $job_id = as_schedule_single_action( time() , 'sumopaymentplans_find_products_to_bulk_update' , array() , 'sumopaymentplans-product-bulk-updates' ) ;

        if( ! $job_id || ! is_numeric( $job_id ) ) {
            wp_send_json_error( array(
                'productsCount' => sizeof( $found_products ) ,
            ) ) ;
        }

        wp_send_json_success( array(
            'productsCount' => sizeof( $found_products ) ,
        ) ) ;
    }

    /**
     * Search for payment plans
     */
    public static function json_search_payment_plans() {
        ob_start() ;

        $term    = ( string ) wc_clean( stripslashes( isset( $_GET[ 'term' ] ) ? $_GET[ 'term' ] : ''  ) ) ;
        $exclude = array() ;

        if( isset( $_GET[ 'exclude' ] ) && ! empty( $_GET[ 'exclude' ] ) ) {
            $exclude = array_map( 'intval' , explode( ',' , $_GET[ 'exclude' ] ) ) ;
        }

        $args = array(
            'type'    => 'sumo_payment_plans' ,
            'status'  => 'publish' ,
            'return'  => 'posts' ,
            'order'   => 'ASC' ,
            'orderby' => 'parent title' ,
            's'       => $term ,
            'exclude' => $exclude ,
                ) ;

        if( is_numeric( $term ) ) {
            unset( $args[ 's' ] ) ;
            $args[ 'post__in' ] = array( ( int ) $term ) ;
        }

        $posts       = _sumo_pp()->query->get( $args ) ;
        $found_plans = array() ;

        if( ! empty( $posts ) ) {
            foreach( $posts as $post ) {
                $found_plans[ $post->ID ] = sprintf( '(#%s) %s' , $post->ID , $post->post_title ) ;
            }
        }
        wp_send_json( $found_plans ) ;
    }

    /**
     * Search for customers by email and return json.
     */
    public static function json_search_customers_by_email() {
        ob_start() ;

        if( ! current_user_can( 'edit_shop_orders' ) ) {
            wp_die( -1 ) ;
        }

        $term  = wc_clean( wp_unslash( $_GET[ 'term' ] ) ) ;
        $limit = '' ;

        if( empty( $term ) ) {
            wp_die() ;
        }

        $ids = array() ;
        // Search by ID.
        if( is_numeric( $term ) ) {
            $customer = new WC_Customer( intval( $term ) ) ;

            // Customer does not exists.
            if( 0 !== $customer->get_id() ) {
                $ids = array( $customer->get_id() ) ;
            }
        }

        // Usernames can be numeric so we first check that no users was found by ID before searching for numeric username, this prevents performance issues with ID lookups.
        if( empty( $ids ) ) {
            $data_store = WC_Data_Store::load( 'customer' ) ;

            // If search is smaller than 3 characters, limit result set to avoid
            // too many rows being returned.
            if( 3 > strlen( $term ) ) {
                $limit = 20 ;
            }
            $ids = $data_store->search_customers( $term , $limit ) ;
        }

        $found_customers = array() ;
        if( ! empty( $_GET[ 'exclude' ] ) ) {
            $ids = array_diff( $ids , ( array ) $_GET[ 'exclude' ] ) ;
        }

        foreach( $ids as $id ) {
            $customer                                  = new WC_Customer( $id ) ;
            /* translators: 1: user display name 2: user ID 3: user email */
            $found_customers[ $customer->get_email() ] = sprintf(
                    esc_html__( '%1$s (#%2$s &ndash; %3$s)' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $customer->get_first_name() . ' ' . $customer->get_last_name() , $customer->get_id() , $customer->get_email()
                    ) ;
        }

        wp_send_json( $found_customers ) ;
    }

}

SUMO_PP_Ajax::init() ;
