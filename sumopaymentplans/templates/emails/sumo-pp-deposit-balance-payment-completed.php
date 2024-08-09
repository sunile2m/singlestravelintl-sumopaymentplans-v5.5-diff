<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}
$product_title = $payment->get_formatted_product_name( array (
    'tips' => false ,
    'qty'  => false ,
        ) ) ;
?>

<?php do_action( 'woocommerce_email_header' , $email_heading , $email ) ; ?>

<p><?php printf( __( 'Hi, <br>The Balance Payment for your purchase of %s from payment #%s has been paid Successfully' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $product_title , $payment->get_payment_number() ) ; ?></p>

<p><?php _e( 'Thanks' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></p>

<?php do_action( 'woocommerce_email_footer' , $email ) ; ?>