<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ;
}
?>
<tr>
    <td></td>
    <td>
        <?php
        switch ( $payment_type ) {
            case 'payment-plans':
                ?>
                <p class="<?php echo SUMO_PP_PLUGIN_PREFIX . 'selected_plan' ; ?>">
                    <select name="<?php echo 'order' === $product_type ? SUMO_PP_PLUGIN_PREFIX . 'selected_plan' : SUMO_PP_PLUGIN_PREFIX . 'selected_plan[' . $product_id . ']' ; ?>">
                        <option value=""><?php _e( 'Select the payment plan' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></option>
                        <?php
                        if ( is_array( $selected_plans ) ) {
                            foreach ( $selected_plans as $col => $plans ) {
                                if ( is_array( $plans ) ) {
                                    foreach ( $plans as $row => $plan_id ) {
                                        ?>
                                        <option value="<?php echo $plan_id ; ?>"><?php echo get_the_title( $plan_id ) ; ?></option>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <option value="<?php echo $plans ; ?>"><?php echo get_the_title( $plans ) ; ?></option>
                                    <?php
                                }
                            }
                        }
                        ?>
                    </select>
                    <input type="hidden" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'product_type' ; ?>" value="<?php echo $product_type ; ?>"/>
                </p>
                <?php
                break ;
            case 'pay-in-deposit':
                ?>
                <p class="<?php echo SUMO_PP_PLUGIN_PREFIX . 'deposit_amount' ; ?>">
                    <input type="number" name="<?php echo 'order' === $product_type ? SUMO_PP_PLUGIN_PREFIX . 'deposit_amount' : SUMO_PP_PLUGIN_PREFIX . 'deposit_amount[' . $product_id . ']' ; ?>" value="" min="0" step="0.01" style="width: 50%;" placeholder="<?php _e( 'Enter the deposit amount' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?>">
                    <input type="hidden" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'product_type' ; ?>" value="<?php echo $product_type ; ?>"/>
                </p>
                <?php
                break ;
        }
        ?>
    </td>
</tr>