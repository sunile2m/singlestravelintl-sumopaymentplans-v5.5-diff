<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ;
}
?>
<ul class="order_actions submitbox">
    <li class="wide" id="<?php echo SUMO_PP_PLUGIN_PREFIX . 'payment_actions' ; ?>">
        <select name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'payment_actions' ; ?>" class="wc-enhanced-select wide">
            <option value=""><?php _e( 'Actions' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></option>
            <optgroup label="<?php _e( 'Resend payment emails' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?>">
                <?php
                $mails            = WC()->mailer()->get_emails() ;
                $available_emails = array ( SUMO_PP_PLUGIN_PREFIX . 'payment_schedule' , SUMO_PP_PLUGIN_PREFIX . 'payment_cancelled' ) ;

                if ( $payment->get_balance_payable_order_id() > 0 ) {
                    if ( 'pay-in-deposit' === $payment->get_payment_type() ) {
                        $available_emails = array_merge( $available_emails , array ( SUMO_PP_PLUGIN_PREFIX . 'deposit_balance_payment_invoice' , SUMO_PP_PLUGIN_PREFIX . 'deposit_balance_payment_overdue' ) ) ;
                    } else {
                        $available_emails = array_merge( $available_emails , array ( SUMO_PP_PLUGIN_PREFIX . 'payment_plan_invoice' , SUMO_PP_PLUGIN_PREFIX . 'payment_plan_overdue' ) ) ;
                    }
                }

                if ( is_array( $mails ) && $mails ) {
                    foreach ( $mails as $mail ) {
                        if ( isset( $mail->id ) && in_array( $mail->id , $available_emails ) ) {
                            echo '<option value="send_email_' . esc_attr( $mail->id ) . '">' . esc_html( $mail->title ) . '</option>' ;
                        }
                    }
                }
                ?>
            </optgroup>
        </select>
    </li>
    <li class="wide">
        <div id="delete-action">
            <?php
            if ( current_user_can( 'delete_post' , $post->ID ) ) {
                if ( ! EMPTY_TRASH_DAYS ) {
                    $delete_text = __( 'Delete Permanently' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ;
                } else {
                    $delete_text = __( 'Move to Trash' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ;
                }
                ?>
                <a class="submitdelete deletion" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ) ; ?>"><?php echo $delete_text ; ?></a>
                <?php
            }
            ?>
        </div>
        <input type="submit" class="button save_payments save_order button-primary tips" name="save" value="<?php printf( __( 'Save %s' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , get_post_type_object( $post->post_type )->labels->singular_name ) ; ?>" data-tip="<?php printf( __( 'Save/update the %s' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , get_post_type_object( $post->post_type )->labels->singular_name ) ; ?>" />
    </li>
</ul>