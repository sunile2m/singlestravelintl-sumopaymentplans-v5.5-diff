<?php

if( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle when Recurrence cron gets elapsed
 * 
 * @class SUMO_PP_Background_Updater
 * @category Class
 */
class SUMO_PP_Background_Updater {

    /**
     * Cron Interval in Seconds.
     * 
     * @var int
     * @access private
     */
    private static $cron_interval = SUMO_PP_PLUGIN_CRON_INTERVAL ;

    /**
     * Cron hook identifier
     *
     * @var mixed
     * @access protected
     */
    protected static $cron_hook_identifier ;

    /**
     * Cron interval identifier
     *
     * @var mixed
     * @access protected
     */
    protected static $cron_interval_identifier ;

    /**
     * Init SUMO_PP_Background_Updater
     */
    public static function init() {

        self::$cron_hook_identifier     = 'sumopaymentplans_background_updater' ;
        self::$cron_interval_identifier = 'sumopaymentplans_cron_interval' ;

        self::create_cron_job() ;
        self::init_background_updater() ;
    }

    /**
     * Create Recurrence Cron Job.
     */
    public static function create_cron_job() {

        //may be preventing the recurrence Cron interval not to be greater than SUMO_PP_PLUGIN_CRON_INTERVAL
        if( (wp_next_scheduled( self::$cron_hook_identifier ) - _sumo_pp_get_timestamp()) > self::$cron_interval ) {
            wp_clear_scheduled_hook( self::$cron_hook_identifier ) ;
        }

        //Schedule Recurrence Cron job
        if( ! wp_next_scheduled( self::$cron_hook_identifier ) ) {
            wp_schedule_event( _sumo_pp_get_timestamp() + self::$cron_interval , self::$cron_interval_identifier , self::$cron_hook_identifier ) ;
        }
    }

    /**
     * Init background updater.
     */
    public static function init_background_updater() {
        //Fire when Recurrence cron gets elapsed
        add_action( self::$cron_hook_identifier , array( __CLASS__ , 'background_updater' ) ) ;

        // Fire Scheduled Cron Hooks. $payment_jobs as job name => do some job
        $payment_jobs = array(
            'create_balance_payable_order' => 'create_balance_payable_order' ,
            'notify_reminder'              => 'notify_reminder' ,
            'notify_overdue'               => 'set_overdue' ,
            'notify_cancelled'             => 'set_cancelled' ,
                ) ;

        foreach( $payment_jobs as $job_name => $do_job ) {
            add_action( "sumopaymentplans_fire_{$job_name}" , __CLASS__ . "::{$do_job}" ) ;
        }

        add_action( 'sumopaymentplans_find_products_to_bulk_update' , __CLASS__ . '::find_products_to_bulk_update' ) ;
        add_action( 'sumopaymentplans_update_products_in_bulk' , __CLASS__ . '::update_products_in_bulk' ) ;
    }

    /**
     * Fire when recurrence Cron gets Elapsed
     * Background Updater.
     */
    public static function background_updater() {
        $cron_jobs = _sumo_pp()->query->get( array(
            'type'   => 'sumo_pp_cron_jobs' ,
            'status' => 'publish' ,
                ) ) ;

        if( empty( $cron_jobs ) ) {
            return ;
        }

        //Loop through each Scheduled Job Query post and check whether time gets elapsed
        foreach( $cron_jobs as $job_id ) {
            $jobs = get_post_meta( $job_id , '_scheduled_jobs' , true ) ;

            if( ! is_array( $jobs ) ) {
                continue ;
            }

            foreach( $jobs as $payment_id => $payment_jobs ) {
                foreach( $payment_jobs as $job_name => $job_args ) {
                    if( ! is_array( $job_args ) ) {
                        continue ;
                    }

                    foreach( $job_args as $job_timestamp => $args ) {
                        if( ! is_int( $job_timestamp ) || ! $job_timestamp ) {
                            continue ;
                        }
                        //When the time gets elapsed then do the corresponding job.
                        if( _sumo_pp_get_timestamp() >= $job_timestamp ) {
                            do_action( "sumopaymentplans_fire_{$job_name}" , array_merge( array(
                                'payment_id' => $payment_id
                                            ) , $args ) ) ;

                            //Refresh job.
                            $jobs = get_post_meta( $job_id , '_scheduled_jobs' , true ) ;

                            //Clear the Job when the corresponding job is done.
                            if( did_action( "sumopaymentplans_fire_{$job_name}" ) ) {
                                unset( $jobs[ $payment_id ][ $job_name ][ $job_timestamp ] ) ;
                            }
                        }
                    }
                    //Flush the meta once the timestamp is not available for the specific job
                    if( empty( $jobs[ $payment_id ][ $job_name ] ) ) {
                        unset( $jobs[ $payment_id ][ $job_name ] ) ;
                    }
                }
            }
            //Get updated scheduled jobs.
            if( is_array( $jobs ) ) {
                update_post_meta( $job_id , '_scheduled_jobs' , $jobs ) ;
            }
        }
    }

    /**
     * Create Balance Payable Order for the Payment
     * @param array $args
     */
    public static function create_balance_payable_order( $args ) {

        $args = wp_parse_args( $args , array(
            'payment_id'      => 0 ,
            'next_payment_on' => '' ,
                ) ) ;

        $payment = _sumo_pp_get_payment( $args[ 'payment_id' ] ) ;

        if( $payment && ($payment->has_status( 'in_progress' ) || $payment->awaiting_initial_payment()) ) {
            if( $payment->balance_payable_order_exists() ) {
                $balance_payable_order_id = $payment->get_balance_payable_order_id() ;
            } else {
                $balance_payable_order_id = SUMO_PP_Order_Manager::create_balance_payable_order( $payment ) ;
            }

            if( $payment_cron = _sumo_pp_get_payment_cron( $payment ) ) {
                $payment_cron->unset_jobs() ;

                if( $overdue_time_till = _sumo_pp_get_overdue_time_till( $args[ 'next_payment_on' ] ) ) {
                    $payment_cron->schedule_overdue_notify( $balance_payable_order_id , $args[ 'next_payment_on' ] , $overdue_time_till ) ;
                } else {
                    $payment_cron->schedule_cancelled_notify( $balance_payable_order_id , $args[ 'next_payment_on' ] ) ;
                }

                if( 'payment-plans' === $payment->get_payment_type() ) {
                    $payment_cron->schedule_reminder( $balance_payable_order_id , $args[ 'next_payment_on' ] , 'payment_plan_invoice' ) ;
                } else {
                    $payment_cron->schedule_reminder( $balance_payable_order_id , $args[ 'next_payment_on' ] , 'deposit_balance_payment_invoice' ) ;
                }
            }
        }
    }

    /**
     * Create Single/Multiple Reminder
     * @param array $args
     */
    public static function notify_reminder( $args ) {

        $args = wp_parse_args( $args , array(
            'payment_id'               => 0 ,
            'balance_payable_order_id' => 0 ,
            'mail_template_id'         => ''
                ) ) ;

        if( ! $balance_payable_order = _sumo_pp_get_order( $args[ 'balance_payable_order_id' ] ) ) {
            return ;
        }

        if( $balance_payable_order->has_status( array( 'completed' , 'processing' ) ) || $balance_payable_order->order->get_total() <= 0 ) {
            return ;
        }

        $payment = _sumo_pp_get_payment( $args[ 'payment_id' ] ) ;

        switch( $args[ 'mail_template_id' ] ) {
            case 'payment_plan_invoice':
            case 'deposit_balance_payment_invoice':
                if( $payment && ($payment->has_status( 'in_progress' ) || $payment->awaiting_initial_payment()) ) {
                    //Trigger email
                    $payment->send_payment_email( $args[ 'mail_template_id' ] , $args[ 'balance_payable_order_id' ] ) ;
                }
                break ;
            case 'payment_plan_overdue':
            case 'deposit_balance_payment_overdue':
                if( $payment && $payment->has_status( 'overdue' ) ) {
                    //Trigger email
                    $payment->send_payment_email( $args[ 'mail_template_id' ] , $args[ 'balance_payable_order_id' ] ) ;
                }
                break ;
        }
    }

    /**
     * Set Payment status as Overdue
     * @param array $args
     */
    public static function set_overdue( $args ) {

        $args = wp_parse_args( $args , array(
            'payment_id'               => 0 ,
            'balance_payable_order_id' => 0 ,
            'overdue_date_till'        => 0 ,
                ) ) ;

        if( ! $balance_payable_order = _sumo_pp_get_order( $args[ 'balance_payable_order_id' ] ) ) {
            return ;
        }

        if( $balance_payable_order->has_status( array( 'completed' , 'processing' ) ) ) {
            return ;
        }

        if( $balance_payable_order->order->get_total() <= 0 ) {
            //Auto complete the payment.
            $balance_payable_order->order->payment_complete() ;
            return ;
        }

        $payment = _sumo_pp_get_payment( $args[ 'payment_id' ] ) ;

        if( $payment && ($payment->has_status( 'in_progress' ) || $payment->awaiting_initial_payment()) && $payment->update_status( 'overdue' ) ) {
            $payment->add_payment_note( sprintf( __( 'Balance payment of order#%s is not paid so far. Payment is in Overdue.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $args[ 'balance_payable_order_id' ] ) , 'pending' , __( 'Overdue Payment' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ) ;
            $payment->update_prop( 'next_payment_date' , '' ) ;

            //Schedule multiple Overdue reminder email
            if( $payment_cron = _sumo_pp_get_payment_cron( $payment ) ) {
                $payment_closing_date = _sumo_pp_get_timestamp( '+1 day' , $args[ 'overdue_date_till' ] ) ;

                if( $payment->get_remaining_installments() > 1 ) {
                    $next_installment_date = _sumo_pp_get_timestamp( $payment->get_next_payment_date( $payment->get_next_of_next_installment_count() ) ) ;

                    if( $payment_closing_date >= $next_installment_date ) {
                        $payment_closing_date = $next_installment_date ;
                    }
                }

                $payment_cron->unset_jobs() ;
                $payment_cron->schedule_cancelled_notify( $args[ 'balance_payable_order_id' ] , $payment_closing_date ) ;

                if( 'payment-plans' === $payment->get_payment_type() ) {
                    $payment_cron->schedule_reminder( $args[ 'balance_payable_order_id' ] , $payment_closing_date , 'payment_plan_overdue' ) ;
                } else {
                    $payment_cron->schedule_reminder( $args[ 'balance_payable_order_id' ] , $payment_closing_date , 'deposit_balance_payment_overdue' ) ;
                }
            }
            if( 'payment-plans' === $payment->get_payment_type() ) {
                $payment->send_payment_email( 'payment_plan_overdue' , $args[ 'balance_payable_order_id' ] ) ;
            } else {
                $payment->send_payment_email( 'deposit_balance_payment_overdue' , $args[ 'balance_payable_order_id' ] ) ;
            }

            do_action( 'sumopaymentplans_payment_is_overdue' , $payment->id , $args[ 'balance_payable_order_id' ] , 'balance-payment-order' ) ;
        }
    }

    /**
     * Set Payment status as Cancelled
     * @param array $args
     */
    public static function set_cancelled( $args ) {

        $args = wp_parse_args( $args , array(
            'payment_id'               => 0 ,
            'balance_payable_order_id' => 0 ,
                ) ) ;

        if( ! $balance_payable_order = _sumo_pp_get_order( $args[ 'balance_payable_order_id' ] ) ) {
            return ;
        }

        if( $balance_payable_order->has_status( array( 'completed' , 'processing' ) ) ) {
            return ;
        }

        if( $balance_payable_order->order->get_total() <= 0 ) {
            //Auto complete the payment.
            $balance_payable_order->order->payment_complete() ;
            return ;
        }

        $payment = _sumo_pp_get_payment( $args[ 'payment_id' ] ) ;

        if( $payment && ($payment->has_status( array( 'in_progress' , 'overdue' ) ) || $payment->awaiting_initial_payment()) ) {
            if( 'after_admin_approval' === get_option( SUMO_PP_PLUGIN_PREFIX . 'cancel_payments_after_balance_payment_due_date' , 'after_admin_approval' ) ) {
                if( $payment->update_status( 'await_cancl' ) ) {
                    $payment->update_prop( 'next_payment_date' , '' ) ;

                    if( $payment_cron = _sumo_pp_get_payment_cron( $payment ) ) {
                        $payment_cron->unset_jobs() ;
                    }

                    $payment->send_payment_email( 'payment_awaiting_cancel' , $args[ 'balance_payable_order_id' ] ) ;

                    do_action( 'sumopaymentplans_payment_awaiting_cancel' , $payment->id , $args[ 'balance_payable_order_id' ] , 'balance-payment-order' ) ;
                }
            } else {
                $payment->cancel_payment( array(
                    'content' => sprintf( __( 'Balance payment of order#%s is not paid so far. Payment is Cancelled.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $args[ 'balance_payable_order_id' ] ) ,
                    'status'  => 'success' ,
                    'message' => __( 'Balance Payment Cancelled' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                ) ) ;
            }
        }
    }

    /**
     * Find products to update in bulk
     */
    public static function find_products_to_bulk_update() {
        $found_products = get_transient( SUMO_PP_PLUGIN_PREFIX . 'found_products_to_bulk_update' ) ;

        if( empty( $found_products ) || ! is_array( $found_products ) ) {
            return ;
        }

        $found_products = array_filter( array_chunk( $found_products , 10 ) ) ;

        foreach( $found_products as $index => $chunked_products ) {
            as_schedule_single_action(
                    time() + $index , 'sumopaymentplans_update_products_in_bulk' , array(
                'products' => $chunked_products ,
                    ) , 'sumopaymentplans-product-bulk-updates' ) ;
        }
    }

    /**
     * Start bulk updation of products.
     */
    public static function update_products_in_bulk( $products ) {
        $product_props = array() ;

        foreach( SUMO_PP_Admin_Product::get_payment_fields() as $field_name => $type ) {
            $meta_key                   = SUMO_PP_PLUGIN_PREFIX . $field_name ;
            $product_props[ $meta_key ] = get_option( "bulk{$meta_key}" ) ;
        }

        foreach( $products as $product_id ) {
            $_product = wc_get_product( $product_id ) ;

            if( ! $_product ) {
                continue ;
            }

            switch( $_product->get_type() ) {
                case 'simple':
                case 'variation':
                    SUMO_PP_Admin_Product::save_meta( $product_id , '' , $product_props ) ;
                    break ;
                case 'variable':
                    $variations = get_children( array(
                        'post_parent' => $product_id ,
                        'post_type'   => 'product_variation' ,
                        'fields'      => 'ids' ,
                        'post_status' => array( 'publish' , 'private' ) ,
                        'numberposts' => -1 ,
                            ) ) ;

                    if( empty( $variations ) ) {
                        continue 2 ;
                    }

                    foreach( $variations as $variation_id ) {
                        if( $variation_id ) {
                            SUMO_PP_Admin_Product::save_meta( $variation_id , '' , $product_props ) ;
                        }
                    }
                    break ;
            }
        }
    }

}
