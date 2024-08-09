<table class="shop_table <?php echo SUMO_PP_PLUGIN_PREFIX . 'order_payment_type_fields' ; ?>">
    <tr>
        <td>
            <?php if ( 'yes' === self::$get_options[ 'force_deposit' ] ) { ?>
                <input type="checkbox" id="<?php echo SUMO_PP_PLUGIN_PREFIX . 'enable_order_payment_plan' ; ?>" value="1" checked="checked" readonly="readonly" onclick="return false ;"/>
            <?php } else { ?>
                <input type="checkbox" id="<?php echo SUMO_PP_PLUGIN_PREFIX . 'enable_order_payment_plan' ; ?>" value="1"/>
            <?php } ?>
            <label><?php echo self::$get_options[ 'labels' ][ 'enable' ] ; ?></label>
        </td>
    </tr>   
    <tr>
        <?php if ( 'pay-in-deposit' === self::$get_options[ 'payment_type' ] ) { ?>
            <td>                        
                <label><?php echo self::$get_options[ 'labels' ][ 'deposit_amount' ] ; ?></label>
                <input type="hidden" value="pay-in-deposit" id="<?php echo SUMO_PP_PLUGIN_PREFIX . 'payment_type' ; ?>"/>
            </td>
            <td id="<?php echo SUMO_PP_PLUGIN_PREFIX . 'amount_to_choose' ; ?>">
                <?php if ( 'user-defined' === self::$get_options[ 'deposit_type' ] ) { ?>
                    <?php
                    $deposit_amount_range = self::get_user_defined_deposit_amount_range() ;

                    if ( $deposit_amount_range[ 'max' ] ) {
                        printf( __( 'Enter your Deposit Amount between %s and %s' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , wc_price( $deposit_amount_range[ 'min' ] ) , wc_price( $deposit_amount_range[ 'max' ] ) ) ;
                        ?>
                        <input type="number" min="<?php echo floatval( $deposit_amount_range[ 'min' ] ) ; ?>" max="<?php echo floatval( $deposit_amount_range[ 'max' ] ) ; ?>" step="0.01" class="input-text" id="<?php echo SUMO_PP_PLUGIN_PREFIX . 'deposited_amount' ; ?>"/>
                        <?php
                    } else {
                        printf( __( 'Enter a deposit amount not less than %s' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , wc_price( $deposit_amount_range[ 'min' ] ) ) ;
                        ?>
                        <input type="number" min="<?php echo floatval( $deposit_amount_range[ 'min' ] ) ; ?>" step="0.01" class="input-text" id="<?php echo SUMO_PP_PLUGIN_PREFIX . 'deposited_amount' ; ?>"/>
                        <?php
                    }
                } else {
                    ?>
                    <?php echo wc_price( self::get_fixed_deposit_amount() ) ; ?>
                    <input type="hidden" value="<?php echo self::get_fixed_deposit_amount() ; ?>" id="<?php echo SUMO_PP_PLUGIN_PREFIX . 'deposited_amount' ; ?>"/>
                <?php } ?>
            </td>
        <?php } else { ?>
            <td>                       
                <label><?php echo self::$get_options[ 'labels' ][ 'payment_plans' ] ; ?></label>
                <input type="hidden" value="payment-plans" id="<?php echo SUMO_PP_PLUGIN_PREFIX . 'payment_type' ; ?>"/>
            </td>                    
            <td id="<?php echo SUMO_PP_PLUGIN_PREFIX . 'plans_to_choose' ; ?>">
                <?php
                $i = 1 ;
                if ( is_array( self::$get_options[ 'selected_plans' ] ) ) {
                    foreach ( self::$get_options[ 'selected_plans' ] as $col => $plans ) {
                        if ( is_array( $plans ) ) {
                            foreach ( $plans as $row => $plan_id ) {
                                ?>
                                <p>
                                    <input type="radio" value="<?php echo absint( $plan_id ) ; ?>" id="<?php echo SUMO_PP_PLUGIN_PREFIX . 'chosen_payment_plan' ; ?>" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'chosen_payment_plan' ; ?>" <?php echo 1 === $i ? 'checked="checked"' : '' ?>/>
                                    <strong><?php echo get_the_title( $plan_id ) ; ?></strong><br>
                                    <?php echo get_post_meta( $plan_id , '_plan_description' , true ) ; ?>
                                </p>  
                                <?php
                            }
                        } else {
                            $plan_id = $plans ;
                            ?>
                            <p>
                                <input type="radio" value="<?php echo absint( $plan_id ) ; ?>" id="<?php echo SUMO_PP_PLUGIN_PREFIX . 'chosen_payment_plan' ; ?>" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'chosen_payment_plan' ; ?>" <?php echo 1 === $i ? 'checked="checked"' : '' ?>/>
                                <strong><?php echo get_the_title( $plan_id ) ; ?></strong><br>
                                <?php echo get_post_meta( $plan_id , '_plan_description' , true ) ; ?>
                            </p>  
                            <?php
                        }
                    }
                }
                ?>
            </td>
        <?php } ?>
    </tr>
</table>