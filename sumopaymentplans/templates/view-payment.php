<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}
?>
<div class="sumopaymentplans_view_payment">
    <?php
    $initial_payment_order = _sumo_pp_get_order( $payment->get_initial_payment_order_id() ) ;

    do_action( 'sumopaymentplans_before_view_payment_table' , $payment_id ) ;
    ?>

    <table class="payment_details" data-payment_id="<?php echo $payment->id ; ?>">
        <tr class="payment_status">
            <td><b><?php _e( 'Payment Status' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></b></td>
            <td>:</td>
            <td>
                <?php
                if ( $payment->has_status( 'await_cancl' ) ) {
                    $payment_statuses = _sumo_pp_get_payment_statuses() ;
                    printf( '<mark class="%s"/>%s</mark>' , SUMO_PP_PLUGIN_PREFIX . 'overdue' , esc_attr( $payment_statuses[ SUMO_PP_PLUGIN_PREFIX . 'overdue' ] ) ) ;
                } else {
                    printf( '<mark class="%s"/>%s</mark>' , $payment->get_status( true ) , esc_attr( $payment->get_status_label() ) ) ;
                }
                ?>
            </td>
        </tr>    
        <tr class="payment_product">
            <td><b><?php _e( 'Payment Product ' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></b></td>
            <td>:</td>
            <td>
                <?php
                echo $payment->get_formatted_product_name() ;
                ?>
            </td>
        </tr>    
        <tr class="payment_plan">
            <td><b><?php _e( 'Payment Plan ' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></b></td>
            <td>:</td>
            <td>
                <?php
                if ( 'payment-plans' === $payment->get_payment_type() ) {
                    echo $payment->get_plan()->post_title ;
                } else {
                    echo 'N/A' ;
                }
                ?>
            </td>
        </tr>
        <tr class="payment_start_date">
            <td><b><?php _e( 'Payment Start Date ' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></b></td>
            <td>:</td>
            <td>
                <?php
                if ( $payment->get_prop( 'payment_start_date' ) ) {
                    echo _sumo_pp_get_date_to_display( $payment->get_prop( 'payment_start_date' ) ) ;
                } else {
                    echo '--' ;
                }
                ?>
            </td>
        </tr>
        <tr class="payment_next_payment_date">
            <td><b><?php _e( 'Payment Next Payment Date ' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></b></td>
            <td>:</td>
            <td>
                <?php
                if ( $payment->get_prop( 'next_payment_date' ) ) {
                    echo _sumo_pp_get_date_to_display( $payment->get_prop( 'next_payment_date' ) ) ;
                } else {
                    echo '--' ;
                }
                ?>
            </td>
        </tr>
        <tr class="payment_end_date">
            <td><b><?php _e( 'Payment End Date ' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></b></td>
            <td>:</td>
            <td>
                <?php
                if ( $payment->get_prop( 'payment_end_date' ) ) {
                    echo _sumo_pp_get_date_to_display( $payment->get_prop( 'payment_end_date' ) ) ;
                } else {
                    echo '--' ;
                }
                ?>
        </tr>
        <tr class="initial_payment_amount">
            <td><b><?php _e( 'Initial Payment Amount ' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></b></td> 
            <td>:</td>
            <td>
                <?php
                if ( 'pay-in-deposit' === $payment->get_payment_type() ) {
                    echo wc_price( $payment->get_down_payment( false ) , array ( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) ) . ' x' . $payment->get_product_qty() ;
                } else {
                    if ( 'fixed-price' === $payment->get_plan_price_type() ) {
                        echo wc_price( $payment->get_prop( 'initial_payment' ) , array ( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) ) . ' x' . $payment->get_product_qty() ;
                    } else {
                        echo wc_price( (floatval( $payment->get_prop( 'initial_payment' ) ) * $payment->get_product_price()) / 100 , array ( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) ) . ' x' . $payment->get_product_qty() ;
                    }
                }
                ?>
            </td>
        </tr>
        <tr class="remaining_payable_amount">
            <td><b><?php _e( 'Remaining Payable Amount ' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></b></td> 
            <td>:</td>
            <td><?php echo wc_price( $payment->get_prop( 'remaining_payable_amount' ) , array ( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) ) ; ?></td>
        </tr>
        <tr class="remaining_installments">
            <td><b><?php _e( 'Remaining Installments ' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></b></td> 
            <td>:</td>
            <td><?php echo is_numeric( $payment->get_prop( 'remaining_installments' ) ) ? $payment->get_prop( 'remaining_installments' ) : '--' ; ?></td>
        </tr>
    </table>
    <h6><?php _e( 'Payment Schedule' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></h6>
    <?php
    if (
            'payment-plans' === $payment->get_payment_type() &&
            $payment->has_status( array ( 'in_progress' , 'overdue' , 'await_cancl' , 'cancelled' , 'failed' ) ) &&
            'immediately_after_payment' === $payment->get_prop( 'balance_payable_orders_creation' )
    ) {
        ?>
        <div class="pay_installments">
            <select>
                <option value="pay-remaining"><?php _e( 'Pay for Remaining installment(s)' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></option>
                <?php for ( $i = 0 ; $i < absint( $payment->get_prop( 'remaining_installments' ) ) ; $i ++ ) { ?>
                    <option value="<?php echo $i ; ?>"><?php printf( __( 'Pay for %s installment(s)' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $i + 1 ) ; ?></option>
                <?php } ?>
            </select>
            <input type="button" class="button" value="<?php _e( 'Pay Now' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?>">
        </div><br>
    <?php } ?>
    <?php
    _sumo_pp_get_payment_orders_table( $payment , array (
        'class'       => 'widefat wc_input_table _sumo_pp_footable' ,
        'custom_attr' => 'data-sort=false data-filter=#filter data-page-size=10 data-page-previous-text=prev data-filter-text-only=true data-page-next-text=next' ,
    ) ) ;
    ?>
    <div class="pagination pagination-centered"></div>
    <table class="payment_activities">
        <tr> 
            <td><h6><?php _e( 'Activity Logs ' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></h6></td>
        </tr>
        <tr>
            <td>
                <?php
                if ( $payment_notes = $payment->get_payment_notes() ) {
                    foreach ( $payment_notes as $index => $note ) :
                        if ( $index < 3 ) {
                            echo '<style type="text/css">.default_notes' . $index . '{display:block;}</style>' ;
                        } else {
                            echo '<style type="text/css">.default_notes' . $index . '{display:none;}</style>' ;
                        }

                        switch ( ! empty( $note->meta[ 'comment_status' ][ 0 ] ) ? $note->meta[ 'comment_status' ][ 0 ] : '' ) :
                            case 'success':
                                ?>
                                <div class="_alert_box _success default_notes<?php echo $index ; ?>"><span><?php echo $note->content ; ?></span></div>
                                <?php
                                break ;
                            case 'pending':
                                ?>
                                <div class="_alert_box warning default_notes<?php echo $index ; ?>"><span><?php echo $note->content ; ?></span></div>
                                <?php
                                break ;
                            case 'failure':
                                ?>
                                <div class="_alert_box error default_notes<?php echo $index ; ?>"><span><?php echo $note->content ; ?></span></div>
                                <?php
                                break ;
                            default :
                                ?>
                                <div class="_alert_box notice default_notes<?php echo $index ; ?>"><span><?php echo $note->content ; ?></span></div>
                            <?php
                        endswitch ;
                    endforeach ;

                    if ( ! empty( $index ) && $index >= 3 ) {
                        ?>
                        <a data-flag="more" id="prevent_more_notes" style="cursor: pointer;"> <?php _e( 'Show More' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></a>
                        <?php
                    }
                } else {
                    ?>
                    <div class="_alert_box notice">
                        <span><?php _e( 'No Activities Yet.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></span>
                    </div>
                    <?php
                }
                ?>
            </td>
        </tr>
    </table>
    <?php
    do_action( 'sumopaymentplans_after_view_payment_table' , $payment_id ) ;
    ?>
</div>
