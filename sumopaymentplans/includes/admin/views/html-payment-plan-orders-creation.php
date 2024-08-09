<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ;
}
?>
<p class="balance_payable_orders_creation">
    <select name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'balance_payable_orders_creation' ; ?>">
        <option value="based_upon_settings" <?php selected( $balance_payable_orders_creation , 'based_upon_settings' ) ?>><?php _e( 'Based Upon Settings' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></option>
        <option value="immediately_after_payment" <?php selected( $balance_payable_orders_creation , 'immediately_after_payment' ) ?>><?php _e( 'Immediately After Payment' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></option>
    </select>
</p>
<p class="next_payment_date">
    <label for="next_payment_date"><?php _e( 'Next Payment Date:' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></label>
    <select name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'next_payment_date_based_on' ; ?>">
        <option value="expected-payment-date" <?php selected( $next_payment_date_based_on , 'expected-payment-date' ) ?>><?php _e( 'Actual Payment Date' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></option>
        <option value="modified-payment-date" <?php selected( $next_payment_date_based_on , 'modified-payment-date' ) ?>><?php _e( 'Modified Payment Date' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></option>
    </select>
    <?php echo wc_help_tip( 'If "Modified Date" option is selected then, when the customer makes payment for 2 or more installments in a single pay, the modified expected payment date will be set as the expected payment date of the upcoming installment.' ) ; ?>
</p>