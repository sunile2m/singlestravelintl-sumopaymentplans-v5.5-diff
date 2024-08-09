<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}
$product_title = $payment->get_formatted_product_name( array (
    'tips' => false ,
    'qty'  => false ,
        ) ) ;

$scheduled_timestamp = 0 ;
if ( $payment_cron        = _sumo_pp_get_payment_cron( $payment ) ) {
    $payment_jobs = $payment_cron->jobs ;

    if ( isset( $payment_jobs[ 'notify_overdue' ] ) && is_array( $payment_jobs[ 'notify_overdue' ] ) && $payment_jobs[ 'notify_overdue' ] ) {
        foreach ( $payment_jobs[ 'notify_overdue' ] as $args ) {
            if ( isset( $args[ 'overdue_date_till' ] ) ) {
                $scheduled_timestamp = $args[ 'overdue_date_till' ] ;
                break ;
            }
        }
    } else if ( isset( $payment_jobs[ 'notify_cancelled' ] ) && is_array( $payment_jobs[ 'notify_cancelled' ] ) && $payment_jobs[ 'notify_cancelled' ] ) {
        $scheduled_timestamp = array_keys( $payment_jobs[ 'notify_cancelled' ] ) ;
        $scheduled_timestamp = isset( $scheduled_timestamp[ 0 ] ) ? $scheduled_timestamp[ 0 ] : 0 ;
    }
}
$overdue_date = _sumo_pp_get_date_to_display( $scheduled_timestamp ) ;
?>

<?php do_action( 'woocommerce_email_header' , $email_heading , $email ) ; ?>

<p><?php printf( __( 'Hi, <br>Your Balance Payment for %s from payment #%s is currently Overdue. <br>Please make the payment using the payment link %s before <strong>%s</strong>. If Payment is not received within <strong>%s</strong>, the order will be Cancelled.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $product_title , $payment->get_payment_number() , '<a href="' . $order->get_pay_url() . '">' . __( 'pay' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) . '</a>' , $overdue_date , $overdue_date ) ; ?></p>

<p><?php _e( 'Thanks' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></p>

<?php do_action( 'woocommerce_email_footer' , $email ) ; ?>