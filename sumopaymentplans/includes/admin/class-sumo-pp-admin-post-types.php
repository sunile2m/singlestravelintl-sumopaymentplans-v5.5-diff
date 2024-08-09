<?php

if( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Post Types Admin
 * 
 * @class SUMO_PP_Admin_Post_Types
 * @category Class
 */
class SUMO_PP_Admin_Post_Types {

    protected static $custom_post_types = array(
        'sumo_payment_plans' => 'payment_plans' ,
        'sumo_pp_payments'   => 'payments' ,
        'sumo_pp_masterlog'  => 'masterlog' ,
        'sumo_pp_cron_jobs'  => 'cron_jobs' ,
            ) ;

    /**
     * Init SUMO_PP_Admin_Post_Types.
     */
    public static function init() {

        foreach( self::$custom_post_types as $post_type => $meant_for ) {
            add_filter( "manage_{$post_type}_posts_columns" , __CLASS__ . "::define_{$meant_for}_columns" ) ;
            add_filter( "manage_edit-{$post_type}_sortable_columns" , __CLASS__ . '::define_sortable_columns' ) ;
            add_filter( "bulk_actions-edit-{$post_type}" , __CLASS__ . '::define_bulk_actions' ) ;
            add_action( "manage_{$post_type}_posts_custom_column" , __CLASS__ . "::render_{$meant_for}_columns" , 10 , 2 ) ;
        }

        add_filter( 'manage_shop_order_posts_columns' , __CLASS__ . '::define_shop_order_columns' , 11 ) ;
        add_action( 'manage_shop_order_posts_custom_column' , __CLASS__ . '::render_shop_order_columns' , 11 , 2 ) ;

        add_filter( 'enter_title_here' , __CLASS__ . '::enter_title_here' , 1 , 2 ) ;
        add_filter( 'post_row_actions' , __CLASS__ . '::row_actions' , 99 , 2 ) ;
        add_action( 'manage_posts_extra_tablenav' , __CLASS__ . '::extra_tablenav' ) ;
        add_filter( 'request' , __CLASS__ . '::request_query' ) ;
        add_action( 'admin_init' , __CLASS__ . '::approve_payment' ) ;

        add_filter( 'get_search_query' , __CLASS__ . '::search_label' ) ;
        add_filter( 'query_vars' , __CLASS__ . '::add_custom_query_var' ) ;
        add_action( 'parse_query' , __CLASS__ . '::search_custom_fields' ) ;
        add_action( 'restrict_manage_posts' , __CLASS__ . '::render_search_filters' ) ;
    }

    /**
     * Define which payment plan columns to show on this screen.
     *
     * @param array $existing_columns Existing columns.
     * @return array
     */
    public static function define_payment_plans_columns( $existing_columns ) {
        $columns = array(
            'cb'               => $existing_columns[ 'cb' ] ,
            'plan_name'        => __( 'Payment Plan Name' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'plan_description' => __( 'Payment Plan Description' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                ) ;
        return $columns ;
    }

    /**
     * Define which payment columns to show on this screen.
     *
     * @param array $existing_columns Existing columns.
     * @return array
     */
    public static function define_payments_columns( $existing_columns ) {
        $columns = array(
            'cb'                       => $existing_columns[ 'cb' ] ,
            'payment_status'           => __( 'Payment Status' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'payment_number'           => __( 'Payment Identification Number' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'product_name'             => __( 'Product Name' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'order_id'                 => __( 'Order ID' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'buyer_email'              => __( 'Buyer Email' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'billing_name'             => __( 'Billing Name' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'payment_type'             => __( 'Payment Type' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'payment_plan'             => __( 'Payment Plan' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'remaining_installments'   => __( 'Remaining Installments' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'remaining_payable_amount' => __( 'Remaining Payable Amount' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'next_installment_amount'  => __( 'Next Installment Amount' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'payment_start_date'       => __( 'Payment Start Date' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'next_payment_date'        => __( 'Next Payment Date' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'payment_ending_date'      => __( 'Payment Ending Date' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'last_payment_date'        => __( 'Previous Payment Date' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                ) ;
        return $columns ;
    }

    /**
     * Define which masterlog columns to show on this screen.
     *
     * @param array $existing_columns Existing columns.
     * @return array
     */
    public static function define_masterlog_columns( $existing_columns ) {
        $columns = array(
            'cb'             => $existing_columns[ 'cb' ] ,
            'status'         => __( 'Status' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'message'        => __( 'Message' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'user_name'      => __( 'User Name' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'payment_number' => __( 'Payment Number' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'product_name'   => __( 'Product Name' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'payment_plan'   => __( 'Payment Plan' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'order_id'       => __( 'Order ID' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'log'            => __( 'Log' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'date'           => __( 'Date' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                ) ;
        return $columns ;
    }

    /**
     * Define which cron job columns to show on this screen.
     *
     * @param array $existing_columns Existing columns.
     * @return array
     */
    public static function define_cron_jobs_columns( $existing_columns ) {
        $columns = array(
            'cb'             => $existing_columns[ 'cb' ] ,
            'job_id'         => __( 'Job ID' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'payment_number' => __( 'Payment Number' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'job_name'       => __( 'Job Name' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'next_run'       => __( 'Next Run' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'args'           => __( 'Arguments' , SUMO_PP_PLUGIN_TEXT_DOMAIN )
                ) ;
        return $columns ;
    }

    /**
     * Define which wc order columns to show on this screen.
     *
     * @param array $existing_columns Existing columns.
     * @return array
     */
    public static function define_shop_order_columns( $existing_columns ) {
        $existing_columns[ SUMO_PP_PLUGIN_PREFIX . 'payment_info' ] = __( 'Payment Info' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ;
        return $existing_columns ;
    }

    /**
     * Define which columns are sortable.
     *
     * @param array $existing_columns Existing columns.
     * @return array
     */
    public static function define_sortable_columns( $existing_columns ) {
        global $current_screen ;

        if( ! isset( $current_screen->post_type ) ) {
            return $existing_columns ;
        }

        $columns = array() ;
        switch( $current_screen->post_type ) {
            case 'sumo_payment_plans':
                $columns = array(
                    'plan_name' => 'title' ,
                        ) ;
                break ;
            case 'sumo_pp_payments':
                $columns = array(
                    'payment_number'          => 'ID' ,
                    'payment_type'            => 'payment_type' ,
                    'order_id'                => 'initial_payment_order_id' ,
                    'remaining_installments'  => 'remaining_installments' ,
                    'next_installment_amount' => 'next_installment_amount' ,
                    'buyer_email'             => 'customer_email' ,
                    'next_payment_date'       => 'next_payment_date' ,
                    'last_payment_date'       => 'last_payment_date' ,
                    'payment_ending_date'     => 'payment_end_date' ,
                        ) ;
                break ;
            case 'sumo_pp_masterlog':
                $columns = array(
                    'payment_number' => 'payment_number' ,
                    'order_id'       => 'payment_order_id' ,
                    'user_name'      => 'user_name' ,
                        ) ;
                break ;
            case 'sumo_pp_cron_jobs':
                $columns = array(
                    'job_id'         => 'ID' ,
                    'payment_number' => 'ID' ,
                        ) ;
                break ;
        }

        return wp_parse_args( $columns , $existing_columns ) ;
    }

    /**
     * Define bulk actions.
     *
     * @param array $actions Existing actions.
     * @return array
     */
    public static function define_bulk_actions( $actions ) {
        unset( $actions[ 'edit' ] ) ;
        return $actions ;
    }

    /**
     * Render individual payment plan columns.
     *
     * @param string $column Column ID to render.
     * @param int    $post_id Post ID.
     */
    public static function render_payment_plans_columns( $column , $post_id ) {

        switch( $column ) {
            case 'plan_name':
                echo '<a href="' . admin_url( "post.php?post={$post_id}&action=edit" ) . '">' . get_the_title( $post_id ) . '</a>' ;
                break ;
            case 'plan_description':
                echo get_post_meta( $post_id , '_plan_description' , true ) ;
                break ;
        }
    }

    /**
     * Render individual payment columns.
     *
     * @param string $column Column ID to render.
     * @param int    $post_id Post ID.
     */
    public static function render_payments_columns( $column , $post_id ) {
        $payment               = new SUMO_PP_Payment( $post_id ) ;
        $initial_payment_order = _sumo_pp_get_order( $payment->get_initial_payment_order_id() ) ;

        switch( $column ) {
            case 'payment_status':
                printf( '<mark class="%s"/>%s</mark>' , $payment->get_status( true ) , esc_attr( $payment->get_status_label() ) ) ;
                break ;
            case 'payment_number':
                echo '<a href="' . admin_url( "post.php?post={$post_id}&action=edit" ) . '">#' . $payment->get_payment_number() . '</a>' ;
                break ;
            case 'product_name':
                echo $payment->get_formatted_product_name( array( 'page' => 'admin' ) ) ;
                break ;
            case 'order_id':
                echo '<a href="' . admin_url( "post.php?post={$payment->get_initial_payment_order_id()}&action=edit" ) . '">#' . $payment->get_initial_payment_order_id() . '</a>' ;
                break ;
            case 'buyer_email':
                echo $payment->get_customer_email() ;
                break ;
            case 'billing_name':
                if( $initial_payment_order ) {
                    echo $initial_payment_order->get_billing_first_name() . ' ' . $initial_payment_order->get_billing_last_name() ;
                } else {
                    echo 'N/A' ;
                }
                break ;
            case 'payment_type':
                echo $payment->get_payment_type( true ) ;
                break ;
            case 'payment_plan':
                if( 'payment-plans' === $payment->get_payment_type() ) {
                    echo '<a href="' . admin_url( "post.php?post={$payment->get_plan()->ID}&action=edit" ) . '">' . $payment->get_plan()->post_title . '</a>' ;
                } else {
                    echo '--' ;
                }
                break ;
            case 'remaining_installments':
                echo $payment->get_prop( 'remaining_installments' ) ;
                break ;
            case 'remaining_payable_amount':
                echo wc_price( $payment->get_prop( 'remaining_payable_amount' ) , array( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) ) ;
                break ;
            case 'next_installment_amount':
                echo wc_price( $payment->get_prop( 'next_installment_amount' ) , array( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) ) ;
                break ;
            case 'payment_start_date':
                echo $payment->get_prop( 'payment_start_date' ) ? _sumo_pp_get_date_to_display( $payment->get_prop( 'payment_start_date' ) ) : '--' ;
                break ;
            case 'next_payment_date':
                echo $payment->get_prop( 'next_payment_date' ) ? _sumo_pp_get_date_to_display( $payment->get_prop( 'next_payment_date' ) ) : '--' ;
                break ;
            case 'payment_ending_date':
                echo 'payment-plans' === $payment->get_payment_type() && $payment->get_prop( 'payment_end_date' ) ? _sumo_pp_get_date_to_display( $payment->get_prop( 'payment_end_date' ) ) : '--' ;
                break ;
            case 'last_payment_date':
                echo $payment->get_prop( 'last_payment_date' ) ? _sumo_pp_get_date_to_display( $payment->get_prop( 'last_payment_date' ) ) : '--' ;
                break ;
        }
    }

    /**
     * Render individual masterlog columns.
     *
     * @param string $column Column ID to render.
     * @param int    $post_id Post ID.
     */
    public static function render_masterlog_columns( $column , $post_id ) {
        $payment = _sumo_pp_get_payment( get_post_meta( $post_id , '_payment_id' , true ) ) ;

        switch( $column ) {
            case 'status':
                $status = get_post_meta( $post_id , '_status' , true ) ;

                if( in_array( $status , array( 'success' , 'pending' ) ) ) {
                    printf( __( '<div style="background-color: #259e12;width:50px;height:20px;text-align:center;color:#ffffff;padding:5px;">%s</div>' ) , 'Success' ) ;
                } else {
                    printf( __( '<div style="background-color: #ef381c;width:50px;height:20px;text-align:center;color:#ffffff;padding:5px;">%s</div>' ) , 'Failure' ) ;
                }
                break ;
            case 'message':
                echo get_post_meta( $post_id , '_message' , true ) ;
                break ;
            case 'user_name':
                echo get_post_meta( $post_id , '_user_name' , true ) ;
                break ;
            case 'payment_number':
                $payment_id     = get_post_meta( $post_id , '_payment_id' , true ) ;
                $payment_number = get_post_meta( $post_id , '_payment_number' , true ) ;

                echo '<a href="' . admin_url( "post.php?post={$payment_id}&action=edit" ) . '">#' . $payment_number . '</a>' ;
                break ;
            case 'payment_plan':
                $payment_id = get_post_meta( $post_id , '_payment_id' , true ) ;

                if( 'payment-plans' === get_post_meta( $payment_id , '_payment_type' , true ) ) {
                    $plan_id = get_post_meta( $payment_id , '_plan_id' , true ) ;

                    echo '<a href="' . admin_url( "post.php?post={$plan_id}&action=edit" ) . '">' . get_the_title( $plan_id ) . '</a>' ;
                } else {
                    echo '--' ;
                }
                break ;
            case 'product_name':
                echo $payment ? $payment->get_formatted_product_name( array( 'page' => 'admin' ) ) : '' ;
                break ;
            case 'order_id':
                $payment_order_id = get_post_meta( $post_id , '_payment_order_id' , true ) ;

                echo '<a href="' . admin_url( "post.php?post={$payment_order_id}&action=edit" ) . '">#' . $payment_order_id . '</a>' ;
                break ;
            case 'log':
                echo get_post_meta( $post_id , '_log' , true ) ;
                break ;
        }
    }

    /**
     * Render individual cron job columns.
     *
     * @param string $column Column ID to render.
     * @param int    $post_id Post ID.
     */
    public static function render_cron_jobs_columns( $column , $post_id ) {
        $payment_id = absint( get_post_meta( $post_id , '_payment_id' , true ) ) ;
        $jobs       = get_post_meta( $post_id , '_scheduled_jobs' , true ) ;

        $job_name  = array() ;
        $next_run  = array() ;
        $arguments = array() ;

        if( isset( $jobs[ $payment_id ] ) && is_array( $jobs[ $payment_id ] ) ) {
            foreach( $jobs[ $payment_id ] as $_job_name => $args ) {
                if( ! is_array( $args ) ) {
                    continue ;
                }

                $job_name[] = $_job_name ;

                $job_time = '' ;
                foreach( $args as $job_timestamp => $job_args ) {
                    if( ! is_numeric( $job_timestamp ) ) {
                        continue ;
                    }

                    $job_time .= _sumo_pp_get_date( $job_timestamp ) . nl2br( "\n[" . _sumo_pp_get_date_difference( $job_timestamp ) . "]\n\n" ) ;
                }
                $next_run[] = $job_time ;

                $arg = '' ;
                foreach( $args as $job_timestamp => $job_args ) {
                    if( ! is_array( $job_args ) ) {
                        continue ;
                    }
                    $arg .= '"' . implode( ', ' , $job_args ) . '",&nbsp;<br>' ;
                }
                if( '' !== $arg ) {
                    $arguments[] = $arg ;
                }
            }
        }

        switch( $column ) {
            case 'job_id':
                echo '#' . $post_id ;
                break ;
            case 'payment_number':
                $payment_number = get_post_meta( $payment_id , '_payment_number' , true ) ;

                echo '<a href="' . admin_url( "post.php?post={$payment_id}&action=edit" ) . '">#' . $payment_number . '</a>' ;
                break ;
            case 'job_name':
                echo $job_name ? implode( ',' . str_repeat( "</br>" , 4 ) , $job_name ) : 'None' ;
                break ;
            case 'next_run':
                echo $next_run ? '<b>*</b>' . implode( '<b>*</b> ' , $next_run ) : 'None' ;
                break ;
            case 'args':
                echo $arguments ? implode( str_repeat( "</br>" , 4 ) , $arguments ) : 'None' ;
                break ;
        }
    }

    /**
     * Render individual wc order columns.
     *
     * @param string $column Column ID to render.
     * @param int    $post_id Post ID.
     */
    public static function render_shop_order_columns( $column , $post_id ) {

        switch( $column ) {
            case SUMO_PP_PLUGIN_PREFIX . 'payment_info':
                $order = _sumo_pp_get_order( $post_id ) ;

                if( $order && $order->is_payment_order() ) {
                    if( $order->is_parent() ) {
                        $payments = _sumo_pp()->query->get( array(
                            'type'         => 'sumo_pp_payments' ,
                            'status'       => array_keys( _sumo_pp_get_payment_statuses() ) ,
                            'meta_key'     => '_initial_payment_order_id' ,
                            'meta_value'   => $order->order_id ,
                            'meta_compare' => 'LIKE' ,
                                ) ) ;
                        if( ! empty( $payments ) ) {
                            $payments_link = array() ;
                            foreach( $payments as $payment_id ) {
                                if( $payment = _sumo_pp_get_payment( $payment_id ) ) {
                                    $payments_link[] = "<a href='" . admin_url( "post.php?post={$payment->id}&action=edit" ) . "'>#{$payment->get_payment_number()}</a>" ;
                                }
                            }
                            if( ! empty( $payments_link ) ) {
                                if( sizeof( $payments_link ) > 1 ) {
                                    printf( __( 'This Order is linked with payment(s) %s' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , implode( ', ' , $payments_link ) ) ;
                                } else {
                                    printf( __( 'This Order is linked with payment%s' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , implode( ',' , $payments_link ) ) ;
                                }
                            }
                        }
                    } else if( $order->is_child() ) {
                        if( $payment = _sumo_pp_get_payment( get_post_meta( $order->order_id , '_payment_id' , true ) ) ) {
                            printf( __( 'This Order is linked with payment%s' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , "<a href='" . admin_url( "post.php?post={$payment->id}&action=edit" ) . "'>#{$payment->get_payment_number()}</a>" ) ;
                        }
                    }
                } else {
                    echo '--' ;
                }
                break ;
        }
    }

    /**
     * Change title boxes in admin.
     * 
     * @param  string $text
     * @param  object $post
     * @return string
     */
    public static function enter_title_here( $text , $post ) {
        switch( $post->post_type ) {
            case 'sumo_payment_plans' :
                $text = __( 'Plan name' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ;
                break ;
        }
        return $text ;
    }

    /**
     * Set row actions.
     *
     * @param array   $actions Array of actions.
     * @param WP_Post $post Current post object.
     * @return array
     */
    public static function row_actions( $actions , $post ) {
        switch( $post->post_type ) {
            case 'sumo_pp_payments':
                unset( $actions[ 'inline hide-if-no-js' ] , $actions[ 'view' ] ) ;

                if( $payment = _sumo_pp_get_payment( $post ) ) {
                    if( $payment->has_status( 'await_aprvl' ) ) {
                        $actions[ 'approve-payment' ] = sprintf( '<span class="edit"><a href="%s" aria-label="Approve">Approve</a></span>' , admin_url( "edit.php?post_type=sumo_pp_payments&payment_id={$payment->id}&action=approve&_sumo_pp_nonce=" . wp_create_nonce( "{$payment->id}" ) ) ) ;
                    }
                    if( $payment->has_status( 'await_cancl' ) ) {
                        $actions[ 'approve-cancel' ] = sprintf( '<span class="edit"><a href="%s" aria-label="Cancel">Cancel</a></span>' , admin_url( "edit.php?post_type=sumo_pp_payments&payment_id={$payment->id}&action=cancel&_sumo_pp_nonce=" . wp_create_nonce( "{$payment->id}" ) ) ) ;
                    }
                }
                break ;
            case 'sumo_payment_plans':
            case 'sumo_pp_masterlog':
            case 'sumo_pp_cron_jobs':
                unset( $actions[ 'inline hide-if-no-js' ] , $actions[ 'view' ] , $actions[ 'edit' ] ) ;
                break ;
        }
        return $actions ;
    }

    /**
     * Render blank slate.
     * 
     * @param string $which String which tablenav is being shown.
     */
    public static function extra_tablenav( $which ) {
        if( 'top' === $which && 'sumo_pp_payments' === get_post_type() ) {
            echo '<a class="button-primary" target="blank" href="' . SUMO_PP_Payments_Exporter::get_exporter_page_url() . '">' . __( 'Export' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) . '</a>' ;
        }
    }

    /**
     * Handle any filters.
     *
     * @param array $query_vars Query vars.
     * @return array
     */
    public static function request_query( $query_vars ) {
        global $typenow ;

        if( ! in_array( $typenow , array_keys( self::$custom_post_types ) ) ) {
            return $query_vars ;
        }

        //Sorting
        if( empty( $query_vars[ 'orderby' ] ) ) {
            $query_vars[ 'orderby' ] = 'ID' ;
        }

        if( empty( $query_vars[ 'order' ] ) ) {
            $query_vars[ 'order' ] = 'DESC' ;
        }

        if( ! empty( $query_vars[ 'orderby' ] ) ) {
            switch( $query_vars[ 'orderby' ] ) {
                case 'next_payment_date':
                case 'last_payment_date':
                case 'payment_end_date':
                    $query_vars[ 'meta_key' ]  = "_{$query_vars[ 'orderby' ]}" ;
                    $query_vars[ 'meta_type' ] = 'DATETIME' ;
                    $query_vars[ 'orderby' ]   = 'meta_value' ;
                    break ;
                case 'customer_email':
                case 'user_name':
                case 'payment_type':
                case 'payment_number':
                    $query_vars[ 'meta_key' ]  = "_{$query_vars[ 'orderby' ]}" ;
                    $query_vars[ 'orderby' ]   = 'meta_value' ;
                    break ;
                case 'initial_payment_order_id':
                case 'payment_order_id':
                case 'remaining_installments':
                case 'next_installment_amount':
                    $query_vars[ 'meta_key' ]  = "_{$query_vars[ 'orderby' ]}" ;
                    $query_vars[ 'orderby' ]   = 'meta_value_num' ;
                    break ;
            }
        }

        if( ! empty( $_REQUEST[ SUMO_PP_PLUGIN_PREFIX . 'get_filtered_product' ][ 0 ] ) ) {
            if( ! empty( $_REQUEST[ SUMO_PP_PLUGIN_PREFIX . 'get_filtered_plan' ][ 0 ] ) ) {
                $query_vars[ 'meta_query' ] = array(
                    'relation' => 'AND' ,
                    array(
                        'key'   => '_product_id' ,
                        'value' => absint( $_REQUEST[ SUMO_PP_PLUGIN_PREFIX . 'get_filtered_product' ][ 0 ] ) ,
                    ) ,
                    array(
                        'key'   => '_plan_id' ,
                        'value' => absint( $_REQUEST[ SUMO_PP_PLUGIN_PREFIX . 'get_filtered_plan' ][ 0 ] ) ,
                    ) ,
                        ) ;
            } else {
                $query_vars[ 'meta_key' ]   = '_product_id' ;
                $query_vars[ 'meta_value' ] = absint( $_REQUEST[ SUMO_PP_PLUGIN_PREFIX . 'get_filtered_product' ][ 0 ] ) ;
            }
        } else if( ! empty( $_REQUEST[ SUMO_PP_PLUGIN_PREFIX . 'get_filtered_plan' ][ 0 ] ) ) {
            $query_vars[ 'meta_key' ]   = '_plan_id' ;
            $query_vars[ 'meta_value' ] = absint( $_REQUEST[ SUMO_PP_PLUGIN_PREFIX . 'get_filtered_plan' ][ 0 ] ) ;
        }
        return $query_vars ;
    }

    public static function approve_payment() {
        if( empty( $_GET[ '_sumo_pp_nonce' ] ) || empty( $_GET[ 'action' ] ) || empty( $_GET[ 'payment_id' ] ) || ! wp_verify_nonce( $_GET[ '_sumo_pp_nonce' ] , $_GET[ 'payment_id' ] ) ) {
            return ;
        }

        $payment = _sumo_pp_get_payment( $_GET[ 'payment_id' ] ) ;
        switch( $_GET[ 'action' ] ) {
            case 'approve':
                $payment->process_initial_payment( array(
                    'content' => sprintf( __( 'Payment is approved by Admin. Initial payment of order#%s is paid. Payment is in progress' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $payment->get_initial_payment_order_id() ) ,
                    'status'  => 'success' ,
                    'message' => __( 'Initial Payment Success' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                        ) , true ) ;
                break ;
            case 'cancel':
                $payment->cancel_payment( array(
                    'content' => __( 'Admin manually cancelled the payment.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                    'status'  => 'success' ,
                    'message' => __( 'Balance Payment Cancelled' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                ) ) ;
                break ;
        }
        wp_safe_redirect( esc_url_raw( admin_url( 'edit.php?post_type=sumo_pp_payments' ) ) ) ;
        exit ;
    }

    /**
     * Change the label when searching index.
     *
     * @param mixed $query Current search query.
     * @return string
     */
    public static function search_label( $query ) {
        global $pagenow , $typenow ;

        if( 'edit.php' !== $pagenow || ! in_array( $typenow , array_keys( self::$custom_post_types ) ) || ! get_query_var( "{$typenow}_search" ) || ! isset( $_GET[ 's' ] ) ) { // WPCS: input var ok.
            return $query ;
        }

        return wc_clean( wp_unslash( $_GET[ 's' ] ) ) ; // WPCS: input var ok, sanitization ok.
    }

    /**
     * Query vars for custom searches.
     *
     * @param mixed $public_query_vars Array of query vars.
     * @return array
     */
    public static function add_custom_query_var( $public_query_vars ) {
        return array_merge( $public_query_vars , array_map( function($type) {
                    return "{$type}_search" ;
                } , array_keys( self::$custom_post_types ) ) ) ;
    }

    /**
     * Search custom fields as well as content.
     *
     * @param WP_Query $wp Query object.
     */
    public static function search_custom_fields( $wp ) {
        global $pagenow , $wpdb ;

        if( 'edit.php' !== $pagenow || empty( $wp->query_vars[ 's' ] ) || ! in_array( $wp->query_vars[ 'post_type' ] , array_keys( self::$custom_post_types ) ) || ! isset( $_GET[ 's' ] ) ) { // WPCS: input var ok.
            return ;
        }

        $term     = str_replace( '#' , '' , wc_clean( wp_unslash( $wp->query_vars[ 's' ] ) ) ) ;
        $post_ids = array() ;

        switch( $wp->query_vars[ 'post_type' ] ) {
            case 'sumo_payment_plans':
                $search_fields = array(
                    '_plan_description' ,
                        ) ;
                break ;
            case 'sumo_pp_payments':
                $search_fields = array(
                    '_payment_number' ,
                    '_product_id' ,
                    '_initial_payment_order_id' ,
                    '_customer_email' ,
                    '_product_type' ,
                    '_payment_type' ,
                    '_plan_id' ,
                        ) ;

                $order_search_fields = array(
                    '_billing_address_index' ,
                    '_billing_first_name' ,
                    '_billing_last_name' ,
                        ) ;

                if( ! is_numeric( $term ) ) {
                    if( false !== stripos( 'pay in deposit' , $term ) ) {
                        $term = 'pay-in-deposit' ;
                    }

                    if( false !== stripos( 'payment plans' , $term ) ) {
                        $term = 'payment-plans' ;
                    }

                    if( false !== stripos( get_option( SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_label' ) , $term ) ) {
                        $term = 'order' ;
                    }
                }
                break ;
            case 'sumo_pp_masterlog':
                $search_fields = array(
                    '_message' ,
                    '_user_name' ,
                    '_payment_id' ,
                    '_payment_number' ,
                    '_payment_order_id' ,
                    '_log' ,
                        ) ;
                break ;
            case 'sumo_pp_cron_jobs':
                $search_fields = array(
                    '_payment_id' ,
                        ) ;
                break ;
        }

        if( empty( $search_fields ) ) {
            return ;
        }

        if( is_numeric( $term ) ) {
            $post_ids = array_unique(
                    array_merge( array( absint( $term ) ) , $wpdb->get_col(
                                    $wpdb->prepare(
                                            "SELECT DISTINCT p1.post_id FROM {$wpdb->postmeta} p1 WHERE p1.meta_value LIKE %s AND p1.meta_key IN ('" . implode( "','" , array_map( 'esc_sql' , $search_fields ) ) . "')" , '%' . $wpdb->esc_like( wc_clean( $term ) ) . '%'
                                    )
                            )
                    ) ) ;
        } else {
            //may be payment is searched based on billing details so that we are using as like WC Order search
            if( ! empty( $order_search_fields ) ) {
                $maybe_order_ids = array_unique(
                        $wpdb->get_col(
                                $wpdb->prepare(
                                        "SELECT DISTINCT p1.post_id FROM {$wpdb->postmeta} p1 WHERE p1.meta_value LIKE %s AND p1.meta_key IN ('" . implode( "','" , array_map( 'esc_sql' , $order_search_fields ) ) . "')" , '%' . $wpdb->esc_like( wc_clean( $term ) ) . '%'
                                )
                        ) ) ;

                $post_ids = $wpdb->get_col(
                        $wpdb->prepare(
                                "SELECT DISTINCT p1.post_id FROM {$wpdb->postmeta} p1 WHERE p1.meta_key LIKE %s AND p1.meta_value IN ('" . implode( "','" , array_map( 'esc_sql' , $maybe_order_ids ) ) . "')" , '_initial_payment_order_id'
                        ) ) ;
            }

            $post_ids = array_unique(
                    array_merge(
                            $post_ids , $wpdb->get_col(
                                    $wpdb->prepare(
                                            "SELECT DISTINCT p1.post_id FROM {$wpdb->postmeta} p1 WHERE p1.meta_value LIKE %s AND p1.meta_key IN ('" . implode( "','" , array_map( 'esc_sql' , $search_fields ) ) . "')" , '%' . $wpdb->esc_like( wc_clean( $term ) ) . '%'
                                    )
                            )
                    ) ) ;
        }

        if( ! empty( $post_ids ) ) {
            // Remove "s" - we don't want to search payment name.
            unset( $wp->query_vars[ 's' ] ) ;

            // so we know we're doing this.
            $wp->query_vars[ "{$wp->query_vars[ 'post_type' ]}_search" ] = true ;

            // Search by found posts.
            $wp->query_vars[ 'post__in' ] = array_merge( $post_ids , array( 0 ) ) ;
        }
    }

    /**
     * Render search filters.
     */
    public static function render_search_filters() {
        global $typenow ;

        if( 'sumo_pp_payments' === $typenow ) {
            $echo = _sumo_pp_wc_search_field( array(
                'class'       => 'wc-product-search' ,
                'name'        => SUMO_PP_PLUGIN_PREFIX . 'get_filtered_product' ,
                'type'        => 'product' ,
                'css'         => 'width: 35%;' ,
                'multiple'    => false ,
                'options'     => ! empty( $_GET[ SUMO_PP_PLUGIN_PREFIX . 'get_filtered_product' ][ 0 ] ) ? $_GET[ SUMO_PP_PLUGIN_PREFIX . 'get_filtered_product' ] : array() ,
                'action'      => 'woocommerce_json_search_products_and_variations' ,
                'placeholder' => __( 'Search for a product&hellip;' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                    ) , false ) ;

            $echo .= _sumo_pp_wc_search_field( array(
                'class'       => 'wc-product-search' ,
                'name'        => SUMO_PP_PLUGIN_PREFIX . 'get_filtered_plan' ,
                'type'        => 'payment_plans' ,
                'css'         => 'width: 35%;' ,
                'multiple'    => false ,
                'options'     => ! empty( $_GET[ SUMO_PP_PLUGIN_PREFIX . 'get_filtered_plan' ][ 0 ] ) ? $_GET[ SUMO_PP_PLUGIN_PREFIX . 'get_filtered_plan' ] : array() ,
                'action'      => '_sumo_pp_json_search_payment_plans' ,
                'placeholder' => __( 'Search for a payment plan&hellip;' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                    ) , false ) ;
            $echo .= '<style>.alignleft:not(.bulkactions){width:55%;}</style>' ;
            echo $echo ;
        }
    }

}

SUMO_PP_Admin_Post_Types::init() ;
