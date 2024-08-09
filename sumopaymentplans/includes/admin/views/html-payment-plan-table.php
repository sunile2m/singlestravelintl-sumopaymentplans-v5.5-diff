<?php
if( ! defined( 'ABSPATH' ) ) {
    exit ;
}
?>
<div class="inside">    
    <table class="plan_options">
        <thead>
            <tr class="price_type">
                <td><?php _e( 'Price Type: ' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></td>
                <td>
                    <select name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'price_type' ; ?>">
                        <option value="percent" <?php selected( $price_type , 'percent' ) ?>><?php _e( 'Percentage' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></option>
                        <option value="fixed-price" <?php selected( $price_type , 'fixed-price' ) ?>><?php _e( 'Fixed Price' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></option>
                    </select>
                </td>
            </tr>
            <tr class="sync">
                <td><?php _e( 'Sync Payment: ' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></td>
                <td>
                    <select name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'sync' ; ?>">
                        <option value="disabled" <?php selected( $sync , 'disabled' ) ?>><?php _e( 'Disabled' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></option>
                        <option value="enabled" <?php selected( $sync , 'enabled' ) ?>><?php _e( 'Enabled' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></option>
                    </select>
                </td>
            </tr>
            <tr class="sync_fields">
                <td><?php _e( 'Sync Start Date: ' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></td>
                <td>
                    <input type="number" min="1" max="28" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'sync_start_date[day]' ; ?>" placeholder="<?php _e( 'Select Day ' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?>" value="<?php echo ! empty( $sync_start_date[ 'day' ] ) ? $sync_start_date[ 'day' ] : '' ; ?>">
                    <select name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'sync_start_date[month]' ; ?>">
                        <option><?php _e( 'Select Month ' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></option>
                        <?php foreach( _sumo_pp_get_month_options() as $duration => $label ): ?>
                            <option value="<?php echo $duration ; ?>" <?php selected(  ! empty( $sync_start_date[ 'month' ] ) ? $sync_start_date[ 'month' ] : '' , $duration ) ?>><?php echo $label ; ?></option>
                        <?php endforeach ; ?>
                    </select>
                    <input type="number" min="2016" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'sync_start_date[year]' ; ?>" placeholder="<?php _e( 'Select Year ' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?>" value="<?php echo ! empty( $sync_start_date[ 'year' ] ) ? $sync_start_date[ 'year' ] : '' ; ?>">
                </td>
            </tr>
            <tr class="sync_fields">
                <td><?php _e( 'Sync Payment Every ' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></td>
                <td>
                    <select name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'sync_month_duration' ; ?>">
                        <?php foreach( array( 1 => 1 , 2 => 2 , 3 => 3 , 4 => 4 , 6 => 6 , 12 => 12 ) as $duration => $label ): ?>
                            <option value="<?php echo $duration ; ?>" <?php selected( get_post_meta( $post->ID , '_sync_month_duration' , true ) , $duration ) ?>><?php echo $label ; ?></option>
                        <?php endforeach ; ?>
                    </select>
                    <?php _e( ' month(s)' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?>
                </td>
            </tr>
            <tr class="pay_balance_type">
                <td><?php _e( 'Next Payment Date: ' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></td>
                <td>
                    <select name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'pay_balance_type' ; ?>">
                        <option value="after" <?php selected( $pay_balance_type , 'after' ) ?>><?php _e( 'After Specific Number of Days' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></option>
                        <option value="before" <?php selected( $pay_balance_type , 'before' ) ?>><?php _e( 'Before a Specific Date' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></option>
                    </select>
                </td>
            </tr>
            <tr class="installments_type">
                <td><?php _e( 'Installments: ' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></td>
                <td>
                    <select name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'installments_type' ; ?>">
                        <option value="variable" <?php selected( $installments_type , 'variable' ) ?>><?php _e( 'Variable' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></option>
                        <option value="fixed" <?php selected( $installments_type , 'fixed' ) ?>><?php _e( 'Fixed' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></option>
                    </select>
                </td>
            </tr>
            <tr class="installments_type_fields">
                <td><?php _e( 'No. of Installments: ' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></td>
                <td>
                    <input class="fixed_no_of_installments" type="number" step="1" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'fixed_no_of_installments' ; ?>" value="<?php echo $fixed_no_of_installments ? $fixed_no_of_installments : '' ; ?>"/>
                </td>
            </tr>
            <tr class="installments_type_fields">
                <td><?php _e( 'Payment Amount: ' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></td>
                <td>
                    <input class="fixed_payment_amount" type="number" step="0.01" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'fixed_payment_amount' ; ?>" value="<?php echo $fixed_payment_amount ; ?>"/><span><?php echo 'fixed-price' === $price_type ? get_woocommerce_currency_symbol() : '%' ; ?></span>
                </td>
            </tr>
            <tr class="installments_type_fields">
                <td><?php _e( 'Interval: ' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></td>
                <td>
                    <?php _e( 'After' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?>
                    <input class="fixed_duration_length" type="number" min="1" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'fixed_duration_length' ; ?>" value="<?php echo ! empty( $fixed_duration_length ) ? $fixed_duration_length : 1 ; ?>"/>
                    <select class="fixed_duration_period" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'fixed_duration_period' ; ?>">
                        <?php foreach( _sumo_pp_get_duration_options() as $period => $label ) { ?>
                            <option value="<?php echo $period ; ?>" <?php selected( $period === $fixed_duration_period , true ) ?>><?php echo $label ; ?></option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
        </thead>
    </table>
    <input type="hidden" id="<?php echo SUMO_PP_PLUGIN_PREFIX . 'hidden_datas' ; ?>" data-currency_symbol="<?php echo get_woocommerce_currency_symbol() ; ?>"/>
    <table class="widefat striped payment_plans">
        <thead>
            <tr>
                <th><b><?php _e( 'Payment Amount' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></b></th>
                <th style="<?php echo 'enabled' === $sync ? 'display:none;' : '' ?>"><b><?php _e( 'Interval' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></b></th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody class="payment_schedules">
            <tr style="<?php echo 'after' === $pay_balance_type || 'enabled' === $sync ? '' : 'display:none;' ?>">
                <td>                           
                    <input class="payment_amount" type="number" step="0.01" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'initial_payment' ; ?>" value="<?php echo $initial_payment ; ?>"/><span><?php echo 'fixed-price' === $price_type ? get_woocommerce_currency_symbol() : '%' ; ?></span>
                </td>
                <td style="<?php echo 'after' === $pay_balance_type ? '' : 'display:none;' ?>"><?php _e( 'Initial Payment' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></td>
                <td><span style="<?php echo 'enabled' === $sync ? '' : 'display:none;' ?>"><?php _e( 'Initial Payment' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></span></td>
            </tr>
            <?php
            if( is_array( $payment_schedules ) ) {
                foreach( $payment_schedules as $plan_row_id => $defined_plan ) {
                    $scheduled_payment = isset( $defined_plan[ 'scheduled_payment' ] ) ? $defined_plan[ 'scheduled_payment' ] : 0 ;
                    $scheduled_date    = isset( $defined_plan[ 'scheduled_date' ] ) ? $defined_plan[ 'scheduled_date' ] : '' ;
                    $scheduled_period  = isset( $defined_plan[ 'scheduled_period' ] ) ? $defined_plan[ 'scheduled_period' ] : '' ;

                    $scheduled_duration_length = '' ;
                    if( isset( $defined_plan[ 'scheduled_duration_length' ] ) ) {
                        $is_final_installment      = sizeof( $payment_schedules ) - 1 === $plan_row_id ;
                        $scheduled_duration_length = $is_final_installment && 0 === $defined_plan[ 'scheduled_duration_length' ] ? '' : $defined_plan[ 'scheduled_duration_length' ] ;
                    }

                    $total_payment_amount += wc_format_decimal( $scheduled_payment , wc_get_price_decimals() ) ;
                    ?>
                    <tr>
                        <td>                                   
                            <input class="payment_amount" type="number" step="0.01" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'scheduled_payment[' . $plan_row_id . ']' ; ?>" value="<?php echo $scheduled_payment ; ?>"/><span><?php echo 'fixed-price' === $price_type ? get_woocommerce_currency_symbol() : '%' ; ?></span>
                        </td>
                        <td style="<?php echo 'enabled' === $sync ? 'display:none;' : '' ?>">
                            <div class="pay_balance_by_after" style="<?php echo 'after' === $pay_balance_type ? '' : 'display:none;' ?>">
                                <?php _e( 'After' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?>
                                <input class="duration_length" type="number" min="1" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'scheduled_duration_length[' . $plan_row_id . ']' ; ?>" value="<?php echo $scheduled_duration_length ; ?>"/>
                                <select class="duration_period" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'scheduled_period[' . $plan_row_id . ']' ; ?>">
                                    <?php foreach( _sumo_pp_get_duration_options() as $period => $label ) { ?>
                                        <option value="<?php echo $period ; ?>" <?php selected( $period === $scheduled_period , true ) ?>><?php echo $label ; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="pay_balance_by_before" style="<?php echo 'before' === $pay_balance_type ? '' : 'display:none;' ?>">
                                <input class="scheduled_date" type="text" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'scheduled_date[' . $plan_row_id . ']' ; ?>" value="<?php echo $scheduled_date ; ?>"/>                                        
                            </div>
                        </td>
                        <td><a href="#" class="remove_row button">X</a></td>
                    </tr>
                    <?php
                }
            }
            ?>
        </tbody>
        <tfoot>
            <tr>
                <th><b><?php _e( 'Total Payment Amount: ' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></b><span class="total_payment_amount"><?php echo 'fixed-price' === $price_type ? get_woocommerce_currency_symbol() . "$total_payment_amount" : "$total_payment_amount%" ; ?></span></th>
                <th colspan="<?php echo 'enabled' === $sync ? '2' : '3' ?>"><a href="#" class="add button"><?php _e( 'Add Rule' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></a> <span class="pagination hide-if-no-paging" style="float: right;"></span></th>
            </tr>
        </tfoot>
    </table>
</div>