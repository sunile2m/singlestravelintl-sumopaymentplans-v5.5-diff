<?php
if( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Get SUMO Payment Plans templates.
 *
 * @param string $template_name
 * @param array $args (default: array())
 * @param string $template_path (default: 'SUMO_PP_PLUGIN_BASENAME_DIR')
 * @param string $default_path (default: SUMO_PP_PLUGIN_TEMPLATE_PATH)
 */
function _sumo_pp_get_template( $template_name , $args = array() , $template_path = SUMO_PP_PLUGIN_BASENAME_DIR , $default_path = SUMO_PP_PLUGIN_TEMPLATE_PATH ) {
    if( ! $template_name ) {
        return ;
    }

    wc_get_template( $template_name , $args , $template_path , $default_path ) ;
}

/**
 * Alter Payment Plans Email Template directory.
 * @param string $template_directory
 * @param string $template
 * @return string
 */
function _sumo_pp_alter_wc_template_directory( $template_directory , $template ) {
    $email_templates = array(
        'payment-schedule' ,
        'payment-plan-invoice' ,
        'payment-plan-success' ,
        'payment-plan-completed' ,
        'payment-plan-overdue' ,
        'deposit-balance-payment-invoice' ,
        'deposit-balance-payment-completed' ,
        'deposit-balance-payment-overdue' ,
        'payment-awaiting-cancel' ,
        'payment-cancelled' ,
            ) ;

    foreach( $email_templates as $template_name ) {
        if( in_array( $template , array(
                    "emails/sumo-pp-{$template_name}.php" ,
                    "emails/plain/sumo-pp-{$template_name}.php" ,
                ) ) ) {
            $template_directory = untrailingslashit( SUMO_PP_PLUGIN_BASENAME_DIR ) ;
            break ;
        }
    }

    return $template_directory ;
}

add_filter( 'woocommerce_template_directory' , '_sumo_pp_alter_wc_template_directory' , 10 , 2 ) ;

/**
 * Apply inline CSS.
 */
function _sumo_pp_style_inline() {
    global $wp ;

    $is_user_subscriptions_table = _sumo_pp_is_my_payments_page() || (is_callable( 'is_account_page' ) && is_account_page() && ((_sumo_pp_is_wc_version( '<' , '2.6' ) && isset( $_GET[ 'payment-id' ] )) || isset( $wp->query_vars[ 'sumo-pp-my-payments' ] ) || isset( $wp->query_vars[ 'sumo-pp-view-payment' ] ))) ;

    if( 'sumo_pp_payments' === get_post_type() || $is_user_subscriptions_table ) {
        echo '<style type="text/css">' ;
        ob_start() ;
        _sumo_pp_get_template( 'sumo-pp-dynamic-css.php' ) ;
        ob_get_contents() ;
        echo '</style>' ;
    }
}

add_action( 'admin_head' , '_sumo_pp_style_inline' ) ;
add_action( 'wp_head' , '_sumo_pp_style_inline' ) ;

/**
 * Display Payment Orders table
 * 
 * @param array $args
 * @param bool $echo
 * @return string echo table
 */
function _sumo_pp_get_payment_orders_table( $payment , $args = array() , $echo = true ) {
    $payment                 = _sumo_pp_get_payment( $payment ) ;
    $args                    = wp_parse_args( $args , array(
        'class'          => '' ,
        'id'             => '' ,
        'css'            => '' ,
        'custom_attr'    => '' ,
        'th_class'       => '' ,
        'th_css'         => '' ,
        'th_custom_attr' => '' ,
        'th_elements'    => array(
            'payments'                       => __( 'Payments' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'installment-amount'             => __( 'Balance Due' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'expected-payment-date'          => __( 'Due Date' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'modified-expected-payment-date' => __( 'Modified Due Date' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            //'actual-payment-date'            => __( 'Actual Payment Date' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'order-number'                   => __( 'Order Number' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        ) ,
        'page'           => 'frontend' ,
            ) ) ;
    $actual_payments_date    = $payment->get_prop( 'actual_payments_date' ) ;
    $scheduled_payments_date = $payment->get_prop( 'scheduled_payments_date' ) ;
    $modified_payment_dates  = $payment->get_prop( 'modified_expected_payment_dates' ) ;
    $initial_payment_order   = _sumo_pp_get_order( $payment->get_initial_payment_order_id() ) ;
    $balance_paid_orders     = $payment->get_balance_paid_orders() ;

    if( ! $payment->is_expected_payment_dates_modified() || $payment->has_status( 'completed' ) ) {
        unset( $args[ 'th_elements' ][ 'modified-expected-payment-date' ] ) ;
    }
    $column_keys = array_keys( $args[ 'th_elements' ] ) ;

    ob_start() ;
    ?>
    <table class="<?php echo esc_attr( $args[ 'class' ] ) ; ?>" <?php echo esc_attr( $args[ 'custom_attr' ] ) ; ?> style="<?php echo esc_attr( $args[ 'css' ] ) ; ?>">
        <thead>
            <tr>
                <?php foreach( $args[ 'th_elements' ] as $column_name ) : ?>
                    <th class="<?php echo esc_attr( $args[ 'th_class' ] ) ; ?>" <?php echo esc_attr( $args[ 'th_custom_attr' ] ) ; ?> style="<?php echo esc_attr( $args[ 'th_css' ] ) ; ?>"><?php echo $column_name ; ?></th>
                <?php endforeach ; ?>
            </tr>
        </thead>
        <tbody>
            <?php
            if( 'pay-in-deposit' === $payment->get_payment_type() ) {
                $balance_paid_order = isset( $balance_paid_orders[ 0 ] ) ? $balance_paid_orders[ 0 ] : 0 ;

                if( 'admin' === $args[ 'page' ] ) {
                    $url = admin_url( "post.php?post={$balance_paid_order}&action=edit" ) ;
                } else {
                    $url = wc_get_endpoint_url( 'view-order' , $balance_paid_order , wc_get_page_permalink( 'myaccount' ) ) ;
                }
                ?>
                <tr>
                    <?php if( in_array( 'payments' , $column_keys ) ) { ?>
                        <td>
                            <?php
                            if( 'order' === $payment->get_product_type() ) {
                                if( $balance_paid_order > 0 ) {
                                    //printf( __( '<a href="%s">Installment #1 of %s</a>' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $url , $payment->get_formatted_product_name( array( 'page' => $args[ 'page' ] ) ) ) ;
                                    printf( __( '<a href="%s">%s</a>' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $url , $payment->get_formatted_product_name( array( 'page' => $args[ 'page' ] ) ) ) ;
                                } else {
                                    //printf( __( 'Installment #1 of %s' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $payment->get_formatted_product_name( array( 'page' => $args[ 'page' ] ) ) ) ;
                                    printf( __( '%s' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $payment->get_formatted_product_name( array( 'page' => $args[ 'page' ] ) ) ) ;
                                }
                            } else {
                                if( $balance_paid_order > 0 ) {
                                    //printf( __( '<a href="%s">Installment #1 of %s</a>&nbsp;&nbsp;x%s' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $url , $payment->get_formatted_product_name( array( 'qty' => false , 'page' => $args[ 'page' ] ) ) , $payment->get_product_qty() ) ;
                                    printf( __( '<a href="%s">%s</a>&nbsp;' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $url , $payment->get_formatted_product_name( array( 'qty' => false , 'page' => $args[ 'page' ] ) ) , $payment->get_product_qty() ) ;
                                } else {
                                    //printf( __( 'Installment #1 of %s&nbsp;&nbsp;x%s' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $payment->get_formatted_product_name( array( 'qty' => false , 'page' => $args[ 'page' ] ) ) , $payment->get_product_qty() ) ;
                                    printf( __( '%s&nbsp;' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $payment->get_formatted_product_name( array( 'qty' => false , 'page' => $args[ 'page' ] ) ) , $payment->get_product_qty() ) ;
                                }
                            }
                            ?>
                        </td>
                    <?php } ?>
                    <?php if( in_array( 'installment-amount' , $column_keys ) ) { ?>
                        <td>
                            <?php
                            $installment_amount = wc_price( $payment->get_product_price() - $payment->get_down_payment( false ) , array( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) ) ;

                            if( 'order' === $payment->get_product_type() ) {
                                echo $installment_amount ;
                            } else {
                                echo "{$installment_amount}&nbsp;&nbsp;x{$payment->get_product_qty()}" ;
                            }
                            ?>
                        </td>
                    <?php } ?>
                    <?php if( in_array( 'expected-payment-date' , $column_keys ) ) { ?>
                        <td>
                            <?php
                            if( $next_payment_date = $payment->get_prop( 'next_payment_date' ) ) {
                                echo _sumo_pp_get_date_to_display( $next_payment_date ) ;
                            } else {
                                if( 'before' === $payment->get_pay_balance_type() ) {
                                    echo _sumo_pp_get_date_to_display( _sumo_pp_get_timestamp( $payment->get_pay_balance_before() ) ) ;
                                } else {
                                    if( ! $payment->has_status( 'await_aprvl' ) && $payment->get_pay_balance_after() > 0 ) {
                                        echo _sumo_pp_get_date_to_display( _sumo_pp_get_timestamp( "+{$payment->get_pay_balance_after()} days" , _sumo_pp_get_timestamp( $payment->get_prop( 'payment_start_date' ) ) ) ) ;
                                    } else {
                                        echo '--' ;
                                    }
                                }
                            }
                            ?>
                        </td>
                    <?php } ?>
                    <?php if( in_array( 'actual-payment-date' , $column_keys ) ) { ?>
                        <td>
                            <?php
                            if( ! empty( $actual_payments_date[ 0 ] ) ) {
                                echo _sumo_pp_get_date_to_display( $actual_payments_date[ 0 ] ) ;
                            } else {
                                echo '--' ;
                            }
                            ?>
                        </td>
                    <?php } ?>
                    <?php if( in_array( 'order-number' , $column_keys ) ) { ?>
                        <td>
                            <?php
                            if( $balance_paid_order > 0 ) {
                                printf( __( '<a href="%s">#%s</a><p>Paid</p>' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $url , $balance_paid_order ) ;
                            } else {
                                if( 'admin' !== $args[ 'page' ] && $payment->balance_payable_order_exists() ) {
                                    // printf( __( '<a class="button" href="%s">Pay for #%s</a>' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $payment->balance_payable_order->get_pay_url() , $payment->balance_payable_order->order_id ) ;
                                    printf( __( '<a class="button" href="%s">Pay Now</a>' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $payment->balance_payable_order->get_pay_url() , $payment->balance_payable_order->order_id ) ;
                                } else {
                                    echo '--' ;
                                }
                            }
                            ?>
                        </td>
                    <?php } ?>
                </tr>
                <?php
            } else {
                if( is_array( $payment->get_prop( 'payment_schedules' ) ) ) {
                    foreach( $payment->get_prop( 'payment_schedules' ) as $installment => $schedule ) {
                        if( ! isset( $schedule[ 'scheduled_payment' ] ) ) {
                            continue ;
                        }
                        $balance_paid_order = isset( $balance_paid_orders[ $installment ] ) ? $balance_paid_orders[ $installment ] : 0 ;

                        if( 'admin' === $args[ 'page' ] ) {
                            $url = admin_url( "post.php?post={$balance_paid_order}&action=edit" ) ;
                        } else {
                            $url = wc_get_endpoint_url( 'view-order' , $balance_paid_order , wc_get_page_permalink( 'myaccount' ) ) ;
                        }
                        ?>
                        <tr>
                            <?php if( in_array( 'payments' , $column_keys ) ) { ?>
                                <td>
                                    <?php
                                    $payment_count = $installment ;

                                    if( 'order' === $payment->get_product_type() ) {
                                        if( $balance_paid_order > 0 ) {
                                            printf( __( '<a href="%s">Installment #%s of %s</a>' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $url , ++ $payment_count , $payment->get_formatted_product_name( array( 'page' => $args[ 'page' ] ) ) ) ;
                                        } else {
                                            printf( __( 'Installment #%s of %s' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , ++ $payment_count , $payment->get_formatted_product_name( array( 'page' => $args[ 'page' ] ) ) ) ;
                                        }
                                    } else {
                                        if( $balance_paid_order > 0 ) {
                                            printf( __( '<a href="%s">Installment #%s of %s</a>&nbsp;&nbsp;x%s' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $url , ++ $payment_count , $payment->get_formatted_product_name( array( 'qty' => false , 'page' => $args[ 'page' ] ) ) , $payment->get_product_qty() ) ;
                                        } else {
                                            printf( __( 'Installment #%s of %s&nbsp;&nbsp;x%s' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , ++ $payment_count , $payment->get_formatted_product_name( array( 'qty' => false , 'page' => $args[ 'page' ] ) ) , $payment->get_product_qty() ) ;
                                        }
                                    }
                                    ?>
                                </td>
                            <?php } ?>
                            <?php if( in_array( 'installment-amount' , $column_keys ) ) { ?>
                                <td>
                                    <?php
                                    if( isset( $schedule[ 'scheduled_payment' ] ) ) {
                                        if( 'fixed-price' === $payment->get_plan_price_type() ) {
                                            $installment_amount = wc_price( $schedule[ 'scheduled_payment' ] , array( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) ) ;
                                        } else {
                                            $installment_amount = wc_price( ($payment->get_product_price() * floatval( $schedule[ 'scheduled_payment' ] ) ) / 100 , array( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) ) ;
                                        }
                                    } else {
                                        $installment_amount = wc_price( '0' , array( 'currency' => $initial_payment_order ? $initial_payment_order->get_currency() : '' ) ) ;
                                    }

                                    if( 'order' === $payment->get_product_type() ) {
                                        echo $installment_amount ;
                                    } else {
                                        echo "{$installment_amount}&nbsp;&nbsp;x{$payment->get_product_qty()}" ;
                                    }
                                    ?>
                                </td>
                            <?php } ?>
                            <?php if( in_array( 'expected-payment-date' , $column_keys ) ) { ?>
                                <td>
                                    <?php
                                    if( ! empty( $scheduled_payments_date[ $installment ] ) ) {
                                        echo _sumo_pp_get_date_to_display( $scheduled_payments_date[ $installment ] ) ;
                                    } else {
                                        echo '--' ;
                                    }
                                    ?>
                                </td>
                            <?php } ?>
                            <?php if( in_array( 'modified-expected-payment-date' , $column_keys ) ) { ?>
                                <td>
                                    <?php
                                    if( ! empty( $modified_payment_dates[ $installment ] ) ) {
                                        echo _sumo_pp_get_date_to_display( $modified_payment_dates[ $installment ] ) ;
                                    } else {
                                        echo '--' ;
                                    }
                                    ?>
                                </td>
                            <?php } ?>
                            <?php if( in_array( 'actual-payment-date' , $column_keys ) ) { ?>
                                <td>
                                    <?php
                                    if( ! empty( $actual_payments_date[ $installment ] ) ) {
                                        echo _sumo_pp_get_date_to_display( $actual_payments_date[ $installment ] ) ;
                                    } else {
                                        echo '--' ;
                                    }
                                    ?>
                                </td>
                            <?php } ?>
                            <?php if( in_array( 'order-number' , $column_keys ) ) { ?>
                                <td>
                                    <?php
                                    if( $balance_paid_order > 0 ) {
                                        printf( __( '<a href="%s">#%s</a><p>Paid</p>' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $url , $balance_paid_order ) ;
                                    } else {
                                        if( 'admin' !== $args[ 'page' ] && empty( $balance_payable_order ) && $payment->balance_payable_order_exists() ) {
                                            $balance_payable_order = $payment->balance_payable_order ;
                                            printf( __( '<a class="button" href="%s">Pay for #%s</a>' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $payment->balance_payable_order->get_pay_url() , $payment->balance_payable_order->order_id ) ;
                                        } else {
                                            echo '--' ;
                                        }
                                    }
                                    ?>
                                </td>
                            <?php } ?>
                        </tr>
                        <?php
                    }
                }
            }
            ?>
        </tbody>
    </table>
    <?php
    if( $echo ) {
        echo ob_get_clean() ;
    } else {
        return ob_get_clean() ;
    }
}
