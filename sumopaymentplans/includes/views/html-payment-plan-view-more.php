<div class="<?php echo SUMO_PP_PLUGIN_PREFIX . 'modal' ; ?>">
    <div class="<?php echo SUMO_PP_PLUGIN_PREFIX . 'modal-wrapper' ; ?>">
        <div class="<?php echo SUMO_PP_PLUGIN_PREFIX . 'modal-close' ; ?>">
            <img src="<?php echo SUMO_PP_PLUGIN_URL . '/assets/images/close.png' ; ?>">
        </div>    
        <table class="<?php echo SUMO_PP_PLUGIN_PREFIX . 'modal-info' ; ?>" style="width:90%;">
            <thead>
                <tr>
                    <th><?php _e( 'Payment Amount' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></th>
                    <th><?php _e( 'Payment Date' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach( $plan_props[ 'payment_schedules' ] as $payment_schedule ) {
                    if( ! isset( $payment_schedule[ 'scheduled_payment' ] ) ) {
                        continue ;
                    }
                    ?>
                    <tr>
                        <td>
                            <?php
                            if( 'fixed-price' === $plan_props[ 'plan_price_type' ] ) {
                                echo self::get_formatted_price( floatval( $payment_schedule[ 'scheduled_payment' ] ) ) ;
                            } else {
                                echo self::get_formatted_price( ($props[ 'product_price' ] * floatval( $payment_schedule[ 'scheduled_payment' ] )) / 100 ) ;
                            }
                            ?>
                        </td>
                        <td>
                            <?php echo $payment_schedule[ 'scheduled_date' ] ? _sumo_pp_get_date_to_display( _sumo_pp_get_date( $payment_schedule[ 'scheduled_date' ] ) ) : '--' ; ?>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
