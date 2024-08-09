<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ;
}
?>
<div class="panel-wrap sumopaymentplans">
    <input name="post_title" type="hidden" value="<?php echo empty( $post->post_title ) ? __( 'Payment' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) : esc_attr( $post->post_title ) ; ?>" />
    <input name="post_status" type="hidden" value="<?php echo esc_attr( $post->post_status ) ; ?>" />
    <div id="order_data" class="panel">
        <h2 style="float: left;"><?php echo esc_html( sprintf( __( '%s #%s details' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , get_post_type_object( $post->post_type )->labels->singular_name , $payment->get_payment_number() ) ) ; ?></h2>
        <?php
        printf( '<mark class="%s"/>%s</mark>' , $payment->get_status( true ) , esc_attr( $payment->get_status_label() ) ) ;
        ?>                
        <p class="order_number" style="clear:both;"><?php
            if ( $initial_payment_order ) {
                if ( $payment_method = $initial_payment_order->get_payment_method() ) {
                    $payment_gateways = WC()->payment_gateways() ? WC()->payment_gateways->payment_gateways() : array () ;

                    printf( __( 'Payment via %s' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , ( isset( $payment_gateways[ $payment_method ] ) ? esc_html( $payment_gateways[ $payment_method ]->get_title() ) : esc_html( $payment_method ) ) ) ;

                    if ( $transaction_id = $initial_payment_order->order->get_transaction_id() ) {
                        if ( isset( $payment_gateways[ $payment_method ] ) && ( $url = $payment_gateways[ $payment_method ]->get_transaction_url( $initial_payment_order->order ) ) ) {
                            echo ' (<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $transaction_id ) . '</a>)' ;
                        } else {
                            echo ' (' . esc_html( $transaction_id ) . ')' ;
                        }
                    }
                    echo '. ' ;
                }

                if ( $ip_address = get_post_meta( $initial_payment_order->order_id , '_customer_ip_address' , true ) ) {
                    echo __( 'Customer IP' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) . ': ' . esc_html( $ip_address ) ;
                }
            }
            ?>
        </p>                
        <div class="order_data_column_container">
            <div class="order_data_column">
                <h4>
                    <?php _e( 'Initial Payment Amount' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?>
                </h4>
                <p class="form-field form-field-wide">
                    <?php
                    if ( 'pay-in-deposit' === $payment->get_payment_type() ) {
                        echo wc_price( $payment->get_down_payment( false ) , array ( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) ) . ' x' . $payment->get_product_qty() ;
                    } else {
                        if ( 'fixed-price' === $payment->get_plan_price_type() ) {
                            echo wc_price( $payment->get_prop( 'initial_payment' ) , array ( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) ) . ' x' . $payment->get_product_qty() ;
                        } else {
                            echo wc_price( (floatval( $payment->get_prop( 'initial_payment' ) ) * $payment->get_product_price() ) / 100 , array ( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) ) . ' x' . $payment->get_product_qty() ;
                        }
                    }
                    ?>
                </p><br>
                <h4>
                    <?php _e( 'Initial Payment Order' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?>
                </h4>
                <p class="form-field form-field-wide">
                    <?php
                    if ( $initial_payment_order ) {
                        _e( "<a href=post.php?post={$initial_payment_order->order_id}&action=edit>#{$initial_payment_order->order_id}</a>" ) ;
                    }
                    ?>
                </p><br>
                <h4>
                    <?php _e( 'General Details' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?>
                </h4>
                <p class="form-field form-field-wide"><label for="order_date"><?php _e( 'Payment Start date:' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></label>
                    <?php if ( $payment_start_date = $payment->get_prop( 'payment_start_date' ) ) { ?>
                        <input type="text" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'payment_start_date' ; ?>" value="<?php echo _sumo_pp_get_date_to_display( $payment_start_date ) ; ?>" readonly/>                                
                        <?php
                    } else {
                        echo '<b>' . __( 'Not Yet Started !!' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) . '</b>' ;
                    }
                    ?>
                </p>
                <p class="form-field form-field-wide"><label for="order_date"><?php _e( 'Payment End date:' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></label>
                    <?php
                    if ( $payment_end_date = $payment->get_prop( 'payment_end_date' ) ) {
                        ?>
                        <input type="text" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'payment_end_date' ; ?>" value="<?php echo _sumo_pp_get_date_to_display( $payment_end_date ) ; ?>" readonly/>
                        <?php
                    } else {
                        switch ( $payment->get_payment_type() ) {
                            case 'pay-in-deposit':
                                echo '<b>--</b>' ;
                                break ;
                            case 'payment-plans':
                                if ( $payment->has_status( array ( 'in_progress' , 'overdue' ) ) ) {
                                    echo '<b>' . __( 'Never Ends !!' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) . '</b>' ;
                                } else if ( $payment->has_status( array ( 'failed' , 'cancelled' , 'completed' ) ) ) {
                                    echo '<b>' . __( 'Payment Ended !!' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) . '</b>' ;
                                } else {
                                    echo '<b>--</b>' ;
                                }
                                break ;
                        }
                    }
                    ?>
                </p>
                <p class="form-field form-field-wide"><label for="order_date"><?php _e( 'Next Payment date:' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></label>
                    <?php
                    if ( $next_payment_date = $payment->get_prop( 'next_payment_date' ) ) {
                        ?>
                        <input type="text" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'next_payment_date' ; ?>" value="<?php echo _sumo_pp_get_date_to_display( $next_payment_date ) ; ?>" readonly/>
                        <?php
                    } else {
                        echo '<b>--</b>' ;
                    }
                    ?>
                </p>
                <p class="form-field form-field-wide">
                    <?php
                    if ( $payment->has_status( array ( 'pending' , 'in_progress' , 'overdue' , 'await_aprvl' , 'await_cancl' ) ) ) {
                        ?>
                        <label for="order_status"><?php _e( 'Payment Status:' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></label>
                        <select class="wc-enhanced-select" id="payment_status" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'payment_status' ; ?>">
                            <option><?php echo $payment->get_status_label() ; ?></option>
                            <optgroup label="<?php _e( 'Change to' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?>">
                                <?php
                                $payment_statuses = _sumo_pp_get_payment_statuses() ;
                                $statuses         = array ( SUMO_PP_PLUGIN_PREFIX . 'cancelled' => $payment_statuses[ SUMO_PP_PLUGIN_PREFIX . 'cancelled' ] ) ;

                                if ( $payment->has_status( 'await_aprvl' ) ) {
                                    $statuses = array_merge( array ( SUMO_PP_PLUGIN_PREFIX . 'in_progress' => __( 'Activate Payment' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ) , $statuses ) ;
                                }

                                if ( is_array( $statuses ) && $statuses ) {
                                    foreach ( $statuses as $status => $status_name ) {
                                        echo '<option value="' . esc_attr( $status ) . '" ' . selected( $status , $payment->get_status( true ) , false ) . '>' . esc_html( $status_name ) . '</option>' ;
                                    }
                                }
                                ?>
                            </optgroup>
                        </select>
                        <?php
                    } else {
                        echo '<b>' . __( 'This Payment cannot be changed to any other status !!' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) . '</b>' ;
                    }
                    ?>
                </p>
                <p class="form-field form-field-wide">
                    <label for="customer_user"><?php _e( 'Customer:' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></label>
                    <input type="text" required name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'customer_email' ; ?>" placeholder="<?php esc_attr_e( 'Customer Email Address' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?>" value="<?php echo $payment->get_customer_email() ; ?>" data-allow_clear="true" />
                </p>
                <?php
                if ( $balance_payable_order && ! $balance_payable_order->has_status( array ( 'completed' , 'processing' ) ) ) :
                    ?>
                    <div class="view_next_payable_order" style="text-align:right;">
                        <a href="#"><?php _e( 'View Next Payable Order' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></a>
                        <p style="font-weight: bolder;display: none;">
                            <a href="<?php echo admin_url( "post.php?post={$balance_payable_order->order_id}&action=edit" ) ; ?>" title="Order #<?php echo $balance_payable_order->order_id ; ?>">#<?php echo $balance_payable_order->order_id ; ?></a>
                        </p>
                    </div>
                    <?php
                endif ;
                ?>
                <p class="form-field form-field-wide">
                    <label for="customer_user"><?php printf( __( 'Next Installment Amount: (%s)' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , get_woocommerce_currency_symbol( $initial_payment_order ? $initial_payment_order->get_currency() : ''  ) ) ?></label>
                    <input type="text" name="<?php echo SUMO_PP_PLUGIN_PREFIX . 'next_installment_amount' ; ?>" value="<?php echo wc_format_decimal( $payment->get_prop( 'next_installment_amount' ) , '' ) ; ?>" data-allow_clear="true" readonly/>
                </p>
            </div>
            <div class="order_data_column">
                <h4>
                    <?php _e( 'Billing Details' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?>
                </h4>
                <div class="address">
                    <?php
                    if ( $initial_payment_order && $initial_payment_order->order->get_formatted_billing_address() ) {
                        echo '<p><strong>' . __( 'Address' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) . ':</strong>' . wp_kses( $initial_payment_order->order->get_formatted_billing_address() , array ( 'br' => array () ) ) . '</p>' ;
                    } else {
                        echo '<p class="none_set"><strong>' . __( 'Address' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) . ':</strong> ' . __( 'No billing address set.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) . '</p>' ;
                    }
                    ?>
                </div>
            </div>
            <div class="order_data_column">
                <h4>
                    <?php _e( 'Shipping Details' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?>
                </h4>
                <div class="address">
                    <?php
                    if ( $initial_payment_order && $initial_payment_order->order->get_formatted_shipping_address() ) {
                        echo '<p><strong>' . __( 'Address' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) . ':</strong>' . wp_kses( $initial_payment_order->order->get_formatted_shipping_address() , array ( 'br' => array () ) ) . '</p>' ;
                    } else {
                        echo '<p class="none_set"><strong>' . __( 'Address' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) . ':</strong> ' . __( 'No shipping address set.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) . '</p>' ;
                    }
                    ?>
                </div>
            </div>                    
        </div>
        <div class="clear"></div>
    </div>
</div>