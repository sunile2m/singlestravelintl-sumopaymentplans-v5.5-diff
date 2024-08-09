<?php

if( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle Admin metaboxes.
 * 
 * @class SUMO_PP_Admin_Metaboxes
 * @category Class
 */
class SUMO_PP_Admin_Metaboxes {

    /**
     * SUMO_PP_Admin_Metaboxes constructor.
     */
    public function __construct() {
        add_action( 'add_meta_boxes' , array( $this , 'add_meta_boxes' ) ) ;
        add_action( 'add_meta_boxes' , array( $this , 'remove_meta_boxes' ) ) ;
        add_action( 'admin_head' , array( $this , 'add_metaboxes_position' ) , 99999 ) ;
        add_action( 'post_updated_messages' , array( $this , 'get_admin_post_messages' ) ) ;
        add_action( 'woocommerce_order_item_line_item_html' , array( $this , 'render_payment_plan_and_deposit_by_item' ) , 20 , 3 ) ;
        add_action( 'woocommerce_admin_order_items_after_line_items' , array( $this , 'render_payment_plan_and_deposit_by_order' ) , 20 , 1 ) ;
        add_action( 'save_post' , array( $this , 'save' ) , 1 , 3 ) ;
    }

    /**
     * Add Metaboxes.
     * @global object $post
     */
    public function add_meta_boxes() {
        global $post ;

        add_meta_box( SUMO_PP_PLUGIN_PREFIX . 'plan_description' , __( 'Plan Description' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , array( $this , 'render_payment_plan_description' ) , 'sumo_payment_plans' , 'normal' , 'high' ) ;
        add_meta_box( SUMO_PP_PLUGIN_PREFIX . 'plan_creation' , __( 'Payment Plan' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , array( $this , 'render_payment_plan_creation' ) , 'sumo_payment_plans' , 'normal' , 'low' ) ;
        add_meta_box( SUMO_PP_PLUGIN_PREFIX . 'plan_balance_payable_orders_creation' , __( 'Payment Plan Orders Creation' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , array( $this , 'render_payment_plan_orders_creation' ) , 'sumo_payment_plans' , 'side' , 'low' ) ;

        if( ! empty( $post->ID ) && 'enabled' === get_post_meta( $post->ID , '_sync' , true ) ) {
            add_meta_box( SUMO_PP_PLUGIN_PREFIX . 'plan_synced_payment_dates' , __( 'Synced Payment Dates' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , array( $this , 'render_payment_plan_synced_payment_dates' ) , 'sumo_payment_plans' , 'side' , 'low' ) ;
        }

        add_meta_box( SUMO_PP_PLUGIN_PREFIX . 'payment_details' , __( 'Payment Details' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , array( $this , 'render_payment_details' ) , 'sumo_pp_payments' , 'normal' , 'high' ) ;
        add_meta_box( SUMO_PP_PLUGIN_PREFIX . 'payment_notes' , __( 'Payment Logs' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , array( $this , 'render_payment_notes' ) , 'sumo_pp_payments' , 'side' , 'low' ) ;
        add_meta_box( SUMO_PP_PLUGIN_PREFIX . 'payment_actions' , __( 'Actions' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , array( $this , 'render_payment_actions' ) , 'sumo_pp_payments' , 'side' , 'high' ) ;
        add_meta_box( SUMO_PP_PLUGIN_PREFIX . 'payment_orders' , __( 'Payment Orders' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , array( $this , 'render_payment_orders' ) , 'sumo_pp_payments' , 'normal' , 'default' ) ;
        add_meta_box( SUMO_PP_PLUGIN_PREFIX . 'payment_item' , __( 'Payment Item' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , array( $this , 'render_payment_item' ) , 'sumo_pp_payments' , 'normal' , 'default' ) ;
    }

    /**
     * Remove Metaboxes.
     */
    public function remove_meta_boxes() {
        remove_meta_box( 'commentsdiv' , 'sumo_payment_plans' , 'normal' ) ;
        remove_meta_box( 'commentsdiv' , 'sumo_pp_payments' , 'normal' ) ;
        remove_meta_box( 'submitdiv' , 'sumo_pp_payments' , 'side' ) ;
    }

    /**
     * Set default Payment Plans metaboxes positions
     */
    public function add_metaboxes_position() {

        if( 'sumo_pp_payments' === get_post_type() ) {
            if( ! $user = wp_get_current_user() ) {
                return ;
            }

            if( false === get_user_option( 'meta-box-order_sumo_pp_payments' , $user->ID ) ) {
                $prefix = SUMO_PP_PLUGIN_PREFIX ;
                delete_user_option( $user->ID , 'meta-box-order_sumo_pp_payments' , true ) ;
                update_user_option( $user->ID , 'meta-box-order_sumo_pp_payments' , array(
                    'side'     => "{$prefix}payment_actions,{$prefix}payment_notes" ,
                    'normal'   => "{$prefix}payment_details,{$prefix}payment_item,{$prefix}payment_orders" ,
                    'advanced' => ''
                        ) , true ) ;
            }
            if( false === get_user_option( 'screen_layout_sumo_pp_payments' , $user->ID ) ) {
                delete_user_option( $user->ID , 'screen_layout_sumo_pp_payments' , true ) ;
                update_user_option( $user->ID , 'screen_layout_sumo_pp_payments' , 'auto' , true ) ;
            }
        }
    }

    /**
     * Display updated Payment Plans post message.
     * @param array $messages
     * @return array
     */
    public function get_admin_post_messages( $messages ) {
        $messages[ 'sumo_payment_plans' ] = array(
            0 => '' , // Unused. Messages start at index 1.
            1 => __( 'Plan updated.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            2 => __( 'Custom field(s) updated.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            4 => __( 'Plan updated.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                ) ;
        $messages[ 'sumo_pp_payments' ]   = array(
            0 => '' , // Unused. Messages start at index 1.
            1 => __( 'Payment updated.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            2 => __( 'Custom field(s) updated.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            4 => __( 'Payment updated.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                ) ;

        return $messages ;
    }

    public function render_payment_plan_description( $post ) {
        echo '<p>' ;
        echo '<textarea cols="90" rows="5" name="' . SUMO_PP_PLUGIN_PREFIX . 'plan_description' . '" required="required" placeholder="' . __( 'Describe this plan about to customers' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) . '">' . get_post_meta( $post->ID , '_plan_description' , true ) . '</textarea>' ;
        echo '</p>' ;
    }

    public function render_payment_details( $post ) {
        wp_nonce_field( '_sumo_pp_save_data' , '_sumo_pp_meta_nonce' ) ;

        $payment               = _sumo_pp_get_payment( $post->ID ) ;
        $balance_payable_order = _sumo_pp_get_order( $payment->get_balance_payable_order_id() ) ;
        $initial_payment_order = _sumo_pp_get_order( $payment->get_initial_payment_order_id() ) ;

        include( 'views/html-payment-details.php' ) ;
    }

    public function render_payment_plan_creation( $post ) {
        wp_nonce_field( '_sumo_pp_save_data' , '_sumo_pp_meta_nonce' ) ;

        $sync                 = get_post_meta( $post->ID , '_sync' , true ) ;
        $price_type           = get_post_meta( $post->ID , '_price_type' , true ) ;
        $pay_balance_type     = 'enabled' === $sync ? 'before' : get_post_meta( $post->ID , '_pay_balance_type' , true ) ;
        $installments_type    = get_post_meta( $post->ID , '_installments_type' , true ) ;
        $initial_payment      = get_post_meta( $post->ID , '_initial_payment' , true ) ;
        $payment_schedules    = get_post_meta( $post->ID , '_payment_schedules' , true ) ;
        $total_payment_amount = 'before' === $pay_balance_type && 'enabled' !== $sync ? 0 : floatval( $initial_payment ) ;
        $sync_start_date      = get_post_meta( $post->ID , '_sync_start_date' , true ) ;

        $fixed_no_of_installments = get_post_meta( $post->ID , '_fixed_no_of_installments' , true ) ;
        $fixed_payment_amount     = get_post_meta( $post->ID , '_fixed_payment_amount' , true ) ;
        $fixed_duration_length    = get_post_meta( $post->ID , '_fixed_duration_length' , true ) ;
        $fixed_duration_period    = get_post_meta( $post->ID , '_fixed_duration_period' , true ) ;

        include( 'views/html-payment-plan-table.php' ) ;
    }

    public function render_payment_plan_orders_creation( $post ) {
        $balance_payable_orders_creation = get_post_meta( $post->ID , '_balance_payable_orders_creation' , true ) ;
        $next_payment_date_based_on      = get_post_meta( $post->ID , '_next_payment_date_based_on' , true ) ;

        include( 'views/html-payment-plan-orders-creation.php' ) ;
    }

    public function render_payment_plan_synced_payment_dates( $post ) {
        $plan_props = SUMO_PP_Payment_Plan_Manager::get_props( $post->ID ) ;

        foreach( $plan_props[ 'payment_schedules' ] as $installment => $schedule ) {
            if( isset( $schedule[ 'scheduled_date' ] ) ) {
                echo _sumo_pp_get_date( $schedule[ 'scheduled_date' ] ) . '<br>' ;
            }
        }
        echo '.....' ;
    }

    public function render_payment_notes( $post ) {
        $payment = _sumo_pp_get_payment( $post ) ;
        $notes   = $payment->get_payment_notes() ;

        include( 'views/html-payment-notes.php' ) ;
    }

    public function render_payment_actions( $post ) {
        $payment = _sumo_pp_get_payment( $post ) ;

        include( 'views/html-payment-actions.php' ) ;
    }

    public function render_payment_orders( $post ) {
        echo '<div class="inside">' ;
        _sumo_pp_get_payment_orders_table( $post->ID , array(
            'page'        => 'admin' ,
            'class'       => 'widefat wc_input_table _sumo_pp_footable' ,
            'custom_attr' => 'data-sort=false data-filter=#filter data-page-size=10 data-page-previous-text=prev data-filter-text-only=true data-page-next-text=next' ,
        ) ) ;
        echo '<div class="pagination pagination-centered"></div></div>' ;
    }

    public function render_payment_item( $post ) {
        $payment  = _sumo_pp_get_payment( $post ) ;
        $order_id = $payment->get_initial_payment_order_id() ;

        if( $order = _sumo_pp_get_order( $order_id ) ) {
            include( 'views/html-order-items.php' ) ;
        }
    }

    public function render_payment_plan_and_deposit_by_item( $item_id , $item , $order ) {
        if( $order = _sumo_pp_get_order( $order ) ) {
            $product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id() ;

            if(
                    SUMO_PP_Product_Manager::is_payment_product( $product_id ) &&
                    ! $order->is_payment_order()
            ) {
                $product_type   = SUMO_PP_Product_Manager::get_prop( 'product_type' ) ;
                $payment_type   = SUMO_PP_Product_Manager::get_prop( 'payment_type' ) ;
                $selected_plans = SUMO_PP_Product_Manager::get_prop( 'selected_plans' ) ;

                include( 'views/html-payment-plan-and-deposit.php' ) ;
            }
        }
    }

    public function render_payment_plan_and_deposit_by_order( $order_id ) {
        $order = _sumo_pp_get_order( $order_id ) ;

        if( ! $order || $order->is_payment_order() || $order->contains_payment_data() || sizeof( $order->order->get_items() ) <= 0 || $order->order->get_total() <= 0 ) {
            return ;
        }

        $order_contains_payment_item = false ;
        foreach( $order->order->get_items() as $item ) {
            $product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id() ;

            if( SUMO_PP_Product_Manager::is_payment_product( $product_id ) ) {
                $order_contains_payment_item = true ;
                break ;
            }
        }

        if( ! $order_contains_payment_item ) {
            $option_props = SUMO_PP_Order_Payment_Plan::get_option_props() ;

            if( $option_props[ 'order_payment_plan_enabled' ] && 'order' === $option_props[ 'product_type' ] ) {
                $product_type   = $option_props[ 'product_type' ] ;
                $payment_type   = $option_props[ 'payment_type' ] ;
                $selected_plans = $option_props[ 'selected_plans' ] ;

                include( 'views/html-payment-plan-and-deposit.php' ) ;
            }
        }
    }

    /**
     * Save data.
     * @param int $post_id The post ID.
     * @param object $post The post object.
     * @param bool $update Whether this is an existing post being updated or not.
     */
    public function save( $post_id , $post , $update ) {
        // $post_id and $post are required
        if( empty( $post_id ) || empty( $post ) ) {
            return ;
        }

        // Dont' save meta boxes for revisions or autosaves
        if( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
            return ;
        }

        // Check the nonce
        if( ! isset( $_POST[ '_sumo_pp_meta_nonce' ] ) || empty( $_POST[ '_sumo_pp_meta_nonce' ] ) || ! wp_verify_nonce( $_POST[ '_sumo_pp_meta_nonce' ] , '_sumo_pp_save_data' ) ) {
            return ;
        }

        // Check the post being saved == the $post_id to prevent triggering this call for other save_post events
        if( empty( $_POST[ 'post_ID' ] ) || $_POST[ 'post_ID' ] != $post_id ) {
            return ;
        }

        // Check user has permission to edit
        if( ! current_user_can( 'edit_post' , $post_id ) ) {
            return ;
        }

        switch( $post->post_type ) {
            case 'sumo_payment_plans':
                $payment_schedules = array() ;

                if( ! empty( $_POST[ SUMO_PP_PLUGIN_PREFIX . 'scheduled_payment' ] ) ) {
                    $scheduled_payment = array_map( 'wc_clean' , $_POST[ SUMO_PP_PLUGIN_PREFIX . 'scheduled_payment' ] ) ;

                    foreach( $scheduled_payment as $i => $payment ) {
                        if( ! isset( $scheduled_payment[ $i ] ) ) {
                            continue ;
                        }

                        if( 'before' === $_POST[ SUMO_PP_PLUGIN_PREFIX . 'pay_balance_type' ] || 'enabled' === $_POST[ SUMO_PP_PLUGIN_PREFIX . 'sync' ] ) {
                            $_POST[ SUMO_PP_PLUGIN_PREFIX . 'pay_balance_type' ] = 'before' ;

                            if( 'enabled' === $_POST[ SUMO_PP_PLUGIN_PREFIX . 'sync' ] ) {
                                $payment_schedules[] = array(
                                    'scheduled_payment' => $scheduled_payment[ $i ] ,
                                    'scheduled_date'    => '' ,
                                        ) ;
                            } else if( isset( $_POST[ SUMO_PP_PLUGIN_PREFIX . 'scheduled_date' ][ $i ] ) ) {
                                $scheduled_date      = array_map( 'wc_clean' , $_POST[ SUMO_PP_PLUGIN_PREFIX . 'scheduled_date' ] ) ;
                                $payment_schedules[] = array(
                                    'scheduled_payment' => $scheduled_payment[ $i ] ,
                                    'scheduled_date'    => $scheduled_date[ $i ] ,
                                        ) ;
                            }
                        } else if( isset( $_POST[ SUMO_PP_PLUGIN_PREFIX . 'scheduled_duration_length' ][ $i ] , $_POST[ SUMO_PP_PLUGIN_PREFIX . 'scheduled_period' ][ $i ] ) ) {
                            $scheduled_duration_length = array_map( 'absint' , $_POST[ SUMO_PP_PLUGIN_PREFIX . 'scheduled_duration_length' ] ) ;
                            $scheduled_period          = array_map( 'wc_clean' , $_POST[ SUMO_PP_PLUGIN_PREFIX . 'scheduled_period' ] ) ;
                            $payment_schedules[]       = array(
                                'scheduled_payment'         => $scheduled_payment[ $i ] ,
                                'scheduled_duration_length' => $scheduled_duration_length[ $i ] ,
                                'scheduled_period'          => $scheduled_period[ $i ] ,
                                    ) ;
                        }
                    }
                }

                update_post_meta( $post_id , '_payment_schedules' , $payment_schedules ) ;
                update_post_meta( $post_id , '_price_type' , $_POST[ SUMO_PP_PLUGIN_PREFIX . 'price_type' ] ) ;
                update_post_meta( $post_id , '_pay_balance_type' , $_POST[ SUMO_PP_PLUGIN_PREFIX . 'pay_balance_type' ] ) ;
                update_post_meta( $post_id , '_installments_type' , $_POST[ SUMO_PP_PLUGIN_PREFIX . 'installments_type' ] ) ;
                update_post_meta( $post_id , '_sync' , $_POST[ SUMO_PP_PLUGIN_PREFIX . 'sync' ] ) ;
                update_post_meta( $post_id , '_sync_start_date' , $_POST[ SUMO_PP_PLUGIN_PREFIX . 'sync_start_date' ] ) ;
                update_post_meta( $post_id , '_sync_month_duration' , $_POST[ SUMO_PP_PLUGIN_PREFIX . 'sync_month_duration' ] ) ;
                update_post_meta( $post_id , '_plan_description' , isset( $_POST[ SUMO_PP_PLUGIN_PREFIX . 'plan_description' ] ) ? $_POST[ SUMO_PP_PLUGIN_PREFIX . 'plan_description' ] : ''  ) ;
                update_post_meta( $post_id , '_initial_payment' , isset( $_POST[ SUMO_PP_PLUGIN_PREFIX . 'initial_payment' ] ) ? floatval( wc_clean( $_POST[ SUMO_PP_PLUGIN_PREFIX . 'initial_payment' ] ) ) : '0'  ) ;
                update_post_meta( $post_id , '_balance_payable_orders_creation' , $_POST[ SUMO_PP_PLUGIN_PREFIX . 'balance_payable_orders_creation' ] ) ;
                update_post_meta( $post_id , '_next_payment_date_based_on' , $_POST[ SUMO_PP_PLUGIN_PREFIX . 'next_payment_date_based_on' ] ) ;
                update_post_meta( $post_id , '_fixed_no_of_installments' , $_POST[ SUMO_PP_PLUGIN_PREFIX . 'fixed_no_of_installments' ] ) ;
                update_post_meta( $post_id , '_fixed_payment_amount' , $_POST[ SUMO_PP_PLUGIN_PREFIX . 'fixed_payment_amount' ] ) ;
                update_post_meta( $post_id , '_fixed_duration_length' , $_POST[ SUMO_PP_PLUGIN_PREFIX . 'fixed_duration_length' ] ) ;
                update_post_meta( $post_id , '_fixed_duration_period' , $_POST[ SUMO_PP_PLUGIN_PREFIX . 'fixed_duration_period' ] ) ;
                break ;
            case 'sumo_pp_payments':
                $payment = _sumo_pp_get_payment( $post_id ) ;

                if( ! empty( $_POST[ SUMO_PP_PLUGIN_PREFIX . 'payment_status' ] ) ) {
                    switch( str_replace( SUMO_PP_PLUGIN_PREFIX , '' , $_POST[ SUMO_PP_PLUGIN_PREFIX . 'payment_status' ] ) ) {
                        case 'in_progress':
                            $payment->process_initial_payment( array(
                                'content' => sprintf( __( 'Payment is approved by Admin. Initial payment of order#%s is paid. Payment is in progress' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $payment->get_initial_payment_order_id() ) ,
                                'status'  => 'success' ,
                                'message' => __( 'Initial Payment Success' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                                    ) , true ) ;
                            break ;
                        case 'cancelled':
                            $payment->cancel_payment( array(
                                'content' => __( 'Admin manually cancelled the payment.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                                'status'  => 'success' ,
                                'message' => __( 'Balance Payment Cancelled' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                            ) ) ;
                            break ;
                    }
                }
                if( ! empty( $_POST[ SUMO_PP_PLUGIN_PREFIX . 'customer_email' ] ) && $_POST[ SUMO_PP_PLUGIN_PREFIX . 'customer_email' ] !== $payment->get_customer_email() ) {
                    if( ! filter_var( $_POST[ SUMO_PP_PLUGIN_PREFIX . 'customer_email' ] , FILTER_VALIDATE_EMAIL ) === false ) {

                        $payment->update_prop( 'customer_email' , $_POST[ SUMO_PP_PLUGIN_PREFIX . 'customer_email' ] ) ;
                        $payment->add_payment_note( sprintf( __( 'Admin has changed the payment customer email to %s. Customer will be notified via email by this Mail ID only.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $_POST[ SUMO_PP_PLUGIN_PREFIX . 'customer_email' ] ) , 'success' , __( 'Customer Email Changed Manually' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ) ;
                    }
                }
                if( ! empty( $_POST[ SUMO_PP_PLUGIN_PREFIX . 'payment_actions' ] ) ) {
                    $action = wc_clean( $_POST[ SUMO_PP_PLUGIN_PREFIX . 'payment_actions' ] ) ;

                    if( strstr( $action , 'send_email_' ) ) {
                        // Ensure gateways are loaded in case they need to insert data into the emails
                        WC()->payment_gateways() ;
                        WC()->shipping() ;

                        $template_id = str_replace( SUMO_PP_PLUGIN_PREFIX , '' , str_replace( 'send_email_' , '' , $action ) ) ;
                        $order_id    = in_array( $template_id , array(
                                    'deposit_balance_payment_invoice' ,
                                    'deposit_balance_payment_overdue' ,
                                    'payment_plan_invoice' ,
                                    'payment_plan_overdue' ,
                                ) ) ? $payment->get_balance_payable_order_id() : $payment->get_initial_payment_order_id() ;

                        // Trigger mailer.
                        if( $order_id ) {
                            $payment->send_payment_email( $template_id , $order_id ) ;
                        }
                    }
                }
                break ;
        }
    }

}

new SUMO_PP_Admin_Metaboxes() ;
