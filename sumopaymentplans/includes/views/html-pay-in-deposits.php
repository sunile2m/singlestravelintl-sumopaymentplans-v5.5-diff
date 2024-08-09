<div class="<?php echo $class ; ?>" id="<?php echo SUMO_PP_PLUGIN_PREFIX . 'payment_type_fields' ; ?>" <?php echo $hide_if_variation ? 'style="display:none;"' : '' ; ?>>
    <p>
        <?php if ( 'yes' !== $props[ 'force_deposit' ] ) { ?>
            <input type="radio" value="pay_in_full" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'payment_type' ; ?>" checked="checked"/>
            <?php echo get_option( SUMO_PP_PLUGIN_PREFIX . 'pay_in_full_label' ) ; ?>
        <?php } ?>
        <input type="radio" value="pay-in-deposit" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'payment_type' ; ?>" <?php echo 'yes' === $props[ 'force_deposit' ] ? 'checked="checked"' : '' ; ?>/>
        <?php echo get_option( SUMO_PP_PLUGIN_PREFIX . 'pay_a_deposit_amount_label' ) ; ?>
        <?php do_action( 'sumopaymentplans_after_deposit_field_label' , $props ) ; ?>
    </p>
    <div id="<?php echo SUMO_PP_PLUGIN_PREFIX . 'amount_to_choose' ; ?>" <?php echo 'yes' === $props[ 'force_deposit' ] ? '' : 'style="display: none;"' ; ?>>
        <?php if ( 'user-defined' === $props[ 'deposit_type' ] ) { ?>
            <p>
                <label for="<?php echo SUMO_PP_PLUGIN_PREFIX . 'deposited_amount' ; ?>">
                    <?php
                    $deposit_amount_range = self::get_user_defined_deposit_amount_range( $props ) ;
                    printf( __( 'Enter your Deposit Amount between %s and %s' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , self::get_formatted_price( $deposit_amount_range[ 'min' ] ) , self::get_formatted_price( $deposit_amount_range[ 'max' ] ) ) ;
                    ?>
                </label>
                <input type="number" min="<?php echo floatval( $deposit_amount_range[ 'min' ] ) ; ?>" max="<?php echo floatval( $deposit_amount_range[ 'max' ] ) ; ?>" step="0.01" class="input-text" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'deposited_amount' ; ?>"/>
            </p>
        <?php } else { ?>
            <p>
                <?php echo self::get_formatted_price( self::get_fixed_deposit_amount( $props ) ) ; ?>
                <input type="hidden" value="<?php echo self::get_fixed_deposit_amount( $props ) ; ?>" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'deposited_amount' ; ?>"/>
            </p>
        <?php } ?>
    </div>
</div>