<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}
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

<?php //do_action( 'woocommerce_email_header' , $email_heading , $email ) ; ?>
<?php //echo getWooEmailHeader(); ?>

<?php
echo "<h1>".$theordernumber."</h1>";
?>

<p><?php printf( __( 'Hi, <br>You have successfully completed the Payment Schedule for Payment #%s. Your Payment details are as follows.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $payment->get_payment_number() ) ; ?></p>

<h2><?php _e( 'Payment Schedule' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></h2>

<?php
_sumo_pp_get_payment_orders_table( $payment , array (
    'class'          => 'td' ,
    'custom_attr'    => 'cellspacing=0 cellpadding=6 border=1' ,
    'css'            => "width: 100%;font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" ,
    'th_class'       => 'td' ,
    'th_css'         => 'text-align:left;' ,
    'th_custom_attr' => 'scope=col' ,
) ) ;
?>

<p><?php _e( 'Thanks' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></p>

<?php //do_action( 'woocommerce_email_footer' , $email ) ; ?>
<?php echo getWooEmailFooter(); ?>