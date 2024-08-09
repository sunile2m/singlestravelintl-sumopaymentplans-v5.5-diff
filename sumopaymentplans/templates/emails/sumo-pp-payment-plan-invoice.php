<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

$payment_count                  = sizeof( $payment->get_balance_paid_orders() ) + 1 ;
$product_title_with_installment = sprintf( __( 'Installment #%s of %s' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $payment_count , $payment->get_formatted_product_name( array (
            'tips' => false ,
            'qty'  => false ,
        ) ) ) ;
?>

<?php do_action( 'woocommerce_email_header' , $email_heading , $email ) ; ?>

<?php if ( $order->has_status( 'pending' ) ) : ?>

    <p><?php printf( __( 'Hi, <br>Your Invoice for %s from payment #%s has been generated. The Payment details are as follows' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $product_title_with_installment , $payment->get_payment_number() ) ; ?></p>

<?php endif ; ?>

<?php do_action( 'woocommerce_email_before_order_table' , $order->order , $sent_to_admin , $plain_text , $email ) ; ?>

<h2><?php printf( __( 'Payment #%s' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $payment->get_payment_number() ) ; ?></h2>

<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
    <thead>
        <tr>
            <th class="td" scope="col" style="text-align:left;"><?php _e( 'Product' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></th>
            <th class="td" scope="col" style="text-align:left;"><?php _e( 'Quantity' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></th>
            <th class="td" scope="col" style="text-align:left;"><?php _e( 'Price' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></th>
        </tr>
    </thead>
    <tbody>
        <?php echo $order->get_email_order_items_table() ; ?>
    </tbody>
    <tfoot>
        <?php echo $order->get_email_order_item_totals() ; ?>
    </tfoot>
</table>

<p><?php printf( __( 'Please make the payment using the payment link %s on or before <strong>%s</strong>' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , '<a href="' . $order->get_pay_url() . '">' . __( 'pay' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) . '</a>' , _sumo_pp_get_date_to_display( $payment->get_prop( 'next_payment_date' ) ) ) ; ?></p>

<?php do_action( 'woocommerce_email_footer' , $email ) ; ?>