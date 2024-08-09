<div class="<?php echo $class ; ?>" id="<?php echo SUMO_PP_PLUGIN_PREFIX . 'payment_type_fields' ; ?>" <?php echo $hide_if_variation ? 'style="display:none;"' : '' ; ?>>
    <p>
        <?php if ( 'yes' !== $props[ 'force_deposit' ] ) { ?>
            <input type="radio" value="pay_in_full" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'payment_type' ; ?>" checked="checked"/>
            <?php echo get_option( SUMO_PP_PLUGIN_PREFIX . 'pay_in_full_label' ) ; ?>
            <input type="radio" value="payment-plans" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'payment_type' ; ?>"/>
            <?php echo get_option( SUMO_PP_PLUGIN_PREFIX . 'pay_with_payment_plans_label' ) ; ?>
        <?php } else { ?>
            <?php if ( apply_filters( 'sumopaymentplans_show_option_for_forced_payment_plan_label' , true , $props ) ) { ?>
                <input type="radio" value="payment-plans" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'payment_type' ; ?>" checked="checked"/>
            <?php } else { ?>
                <input type="hidden" value="payment-plans" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'payment_type' ; ?>"/>
            <?php } ?>
            <?php if ( apply_filters( 'sumopaymentplans_show_forced_payment_plan_label' , true , $props ) ) { ?>
                <?php echo get_option( SUMO_PP_PLUGIN_PREFIX . 'pay_with_payment_plans_label' ) ; ?>
            <?php } ?>
        <?php } ?>
    </p>                    
    <div id="<?php echo SUMO_PP_PLUGIN_PREFIX . 'plans_to_choose' ; ?>" <?php echo 'yes' === $props[ 'force_deposit' ] ? '' : 'style="display: none;"' ; ?>>
        <?php
        if ( is_array( $props[ 'selected_plans' ] ) ) {
            ksort( $props[ 'selected_plans' ] ) ;
            $plan_columns = array ( 'col_1' , 'col_2' ) ;
            $plans        = array () ;

            if ( ! empty( $props[ 'selected_plans' ][ $plan_columns[ 0 ] ] ) ) {
                $plan_size     = array_map( 'sizeof' , $props[ 'selected_plans' ] ) ;
                $max_plan_size = max( $plan_size ) ;

                for ( $i = 0 ; $i < $max_plan_size ; $i ++ ) {
                    foreach ( $plan_columns as $column ) {
                        if ( ! empty( $props[ 'selected_plans' ][ $column ][ $i ] ) ) {
                            $plans[ $i ][] = $props[ 'selected_plans' ][ $column ][ $i ] ;
                        }
                    }
                }
            }

            if ( empty( $plans ) ) {
                $plans = $props[ 'selected_plans' ] ;
            }

            include 'html-payment-plan-selector.php' ;
        }
        ?>
    </div>
</div>