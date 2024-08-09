<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}


$product_title = $payment->get_formatted_product_name( array (
    'tips' => false ,
    'qty'  => false ,
        ) ) ;
?>

<?php

$theordernumber = wc_get_original_order_number($payment->get_payment_number());

/*
$cname = $order->billing_first_name . " " . $order->billing_last_name;

$orderdate = $order->get_date_created();
$orderdate = date_create($orderdate);
$orderdate = date_format($orderdate,"m/d/Y");

$ordernumber = $order->get_order_number();
*/
?>

<?php //do_action( 'woocommerce_email_header' , $email_heading , $email ) ; ?>
<?php // echo getWooEmailHeader(); ?>
<?php echo getWooEmailHeader_invoice($cname,$orderdate, $ordernumber); ?>

<?php
echo "<h1>".$theordernumber."</h1>";
?>

<?php //do_action( 'woocommerce_email_header' , $email_heading , $email ) ; ?>
<?php //echo getWooEmailHeader(); ?>

<p><?php printf( __( 'Hi, <br>The Balance Payment for your purchase of %s from payment #%s has been paid Successfully' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $product_title , $payment->get_payment_number() ) ; ?></p>

<p><?php _e( 'Thanks' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></p>

<?php //do_action( 'woocommerce_email_footer' , $email ) ; ?>

<?php echo getWooEmailFooter(); ?>