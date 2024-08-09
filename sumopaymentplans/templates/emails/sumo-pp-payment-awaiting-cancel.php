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

<p><?php printf( __( 'Hi, <br>Payment #%s on %s is awaiting for cancel. To cancel the payment <a href="%s">click here</a>' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $payment->get_payment_number() , get_option( 'blogname' ) , admin_url( "post.php?post={$payment->id}&action=edit" ) ) ; ?></p>

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

<?php do_action( 'woocommerce_email_footer' , $email ) ; ?>