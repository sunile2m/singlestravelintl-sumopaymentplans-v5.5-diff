<table class="<?php echo SUMO_PP_PLUGIN_PREFIX . 'payment_plans' ; ?>">
    <?php
    foreach( $plans as $col => $plan ) {
        $plan = ( array ) $plan ;
        ?>
        <tr>
            <?php
            foreach( $plan as $row => $plan_id ) {
                $plan_props = SUMO_PP_Payment_Plan_Manager::get_props( $plan_id ) ;
                ?>
                <td>
                    <input type="radio" value="<?php echo absint( $plan_id ) ; ?>" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'chosen_payment_plan' ; ?>" <?php echo 0 === $col && 0 === $row ? 'checked="checked"' : '' ?>/>
                    <?php if( 'yes' === get_option( SUMO_PP_PLUGIN_PREFIX . 'payment_plan_add_to_cart_via_href' ) ) { ?>
                        <a href="<?php echo esc_url_raw( add_query_arg( array( SUMO_PP_PLUGIN_PREFIX . 'payment_type' => 'payment-plans' , SUMO_PP_PLUGIN_PREFIX . 'chosen_payment_plan' => absint( $plan_id ) ) , wc_get_product( $props[ 'product_id' ] )->add_to_cart_url() ) ) ; ?>"><?php echo get_the_title( $plan_id ) ; ?></a>
                        <?php
                        if( 'product' !== get_option( SUMO_PP_PLUGIN_PREFIX . 'after_hyperlink_clicked_redirect_to' , 'product' ) && ! empty( $_GET[ SUMO_PP_PLUGIN_PREFIX . 'payment_type' ] ) && 'payment-plans' === $_GET[ SUMO_PP_PLUGIN_PREFIX . 'payment_type' ] ) {
                            wp_safe_redirect( wc_get_page_permalink( get_option( SUMO_PP_PLUGIN_PREFIX . 'after_hyperlink_clicked_redirect_to' , 'product' ) ) ) ;
                            exit ;
                        }
                        ?>
                    <?php } else { ?>
                        <strong><?php echo get_the_title( $plan_id ) ; ?></strong>
                    <?php } ?>
                    <p class="<?php echo SUMO_PP_PLUGIN_PREFIX . 'initial_payable' ; ?>">
                        <?php
                        if( 'fixed-price' === $plan_props[ 'plan_price_type' ] ) {
                            $initial_payable = floatval( $plan_props[ 'initial_payment' ] ) ;
                        } else {
                            $initial_payable = ($props[ 'product_price' ] * floatval( $plan_props[ 'initial_payment' ] )) / 100 ;
                        }
                        ?>
                        <?php printf( __( '<strong>Initial Payable:</strong> %s<br>' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , self::get_formatted_price( $initial_payable ) ) ; ?>
                    </p>    
                    <p class="<?php echo SUMO_PP_PLUGIN_PREFIX . 'total_payable' ; ?>">
                        <?php printf( __( '<strong>Total Payable:</strong> %s<br>' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , self::get_formatted_price( self::get_prop( 'total_payable' , array( 'product_props' => $props , 'plan_props' => $plan_props ) ) ) ) ; ?>
                    </p>
                    <?php do_action( 'sumopaymentplans_after_total_payable_html' , $props , $plan_props ) ; ?>
                    <p class="<?php echo SUMO_PP_PLUGIN_PREFIX . 'plan_description' ; ?>">
                        <?php echo get_post_meta( $plan_id , '_plan_description' , true ) ; ?>
                    </p>
                    <?php
                    if( 'after' === $plan_props[ 'pay_balance_type' ] && 'after_admin_approval' === get_option( SUMO_PP_PLUGIN_PREFIX . 'activate_payments' , 'auto' ) ) {
                        //Do not display plan information since scheduled date is not available for this plan.
                    } else if( ! empty( $plan_props[ 'payment_schedules' ] ) ) {
                        ?>
                        <div class="<?php echo SUMO_PP_PLUGIN_PREFIX . 'plan_view_more' ; ?>">
                            <p>
                                <a href="#"><?php _e( 'View more' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></a>
                            </p>
                            <?php
                            include 'html-payment-plan-view-more.php' ;
                            ?>
                        </div>
                        <?php
                    }
                    ?>
                </td>
                <?php
            }
            ?>
        </tr>
        <?php
    }
    ?>
</table>