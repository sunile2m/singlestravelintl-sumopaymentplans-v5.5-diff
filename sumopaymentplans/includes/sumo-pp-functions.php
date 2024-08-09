<?php
if( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

include_once('sumo-pp-conditional-functions.php') ;
include_once('sumo-pp-template-functions.php') ;
//include_once('sumo-pp-load-emails.php') ;
include_once('deprecated/sumo-pp-deprecated-functions.php') ;

function _sumo_pp_get_payment( $payment ) {
    $payment = new SUMO_PP_Payment( $payment ) ;

    if( $payment->exists() ) {
        return $payment ;
    }
    return false ;
}

function _sumo_pp_get_order( $order ) {
    $order = new SUMO_PP_Order( $order ) ;

    if( $order->order ) {
        return $order ;
    }
    return false ;
}

function _sumo_pp_get_payment_cron( $payment ) {
    if( $payment = _sumo_pp_get_payment( $payment ) ) {
        return new SUMO_PP_Cron_Job( $payment ) ;
    }
    return false ;
}

function _sumo_pp_get_payment_statuses() {

    $payment_statuses = array(
        SUMO_PP_PLUGIN_PREFIX . 'pending'     => __( 'Pending' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        SUMO_PP_PLUGIN_PREFIX . 'await_aprvl' => __( 'Awaiting Admin Approval' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        SUMO_PP_PLUGIN_PREFIX . 'in_progress' => __( 'In Progress' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        SUMO_PP_PLUGIN_PREFIX . 'completed'   => __( 'Completed' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        SUMO_PP_PLUGIN_PREFIX . 'overdue'     => __( 'Overdue' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        SUMO_PP_PLUGIN_PREFIX . 'failed'      => __( 'Failed' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        SUMO_PP_PLUGIN_PREFIX . 'await_cancl' => __( 'Awaiting Cancel By Admin' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        SUMO_PP_PLUGIN_PREFIX . 'cancelled'   => __( 'Cancelled' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            ) ;

    return $payment_statuses ;
}

/**
 * Get date. Format date/time as GMT/UTC
 * If parameters nothing is given then it returns the current date in Y-m-d H:i:s format.
 * 
 * @param int|string $time should be Date/Timestamp.
 * @param int $base_time
 * @param boolean $exclude_hh_mm_ss
 * @param string $format
 * @return string
 */
function _sumo_pp_get_date( $time = 0 , $base_time = 0 , $exclude_hh_mm_ss = false , $format = 'Y-m-d' ) {
    $timestamp = time() ;

    if( is_numeric( $time ) && $time ) {
        $timestamp = $time ;
    } else if( is_string( $time ) && $time ) {
        $timestamp = strtotime( $time ) ;

        if( is_numeric( $base_time ) && $base_time ) {
            $timestamp = strtotime( $time , $base_time ) ;
        }
    }

    if( ! $format ) {
        $format = 'Y-m-d' ;
    }

    if( $exclude_hh_mm_ss ) {
        return gmdate( "$format" , $timestamp ) ;
    }

    return gmdate( "{$format} H:i:s" , $timestamp ) ;
}

/**
 * Get Timestamp. Format date/time as GMT/UTC
 * If parameters nothing is given then it returns the current timestamp.
 * 
 * @param int|string $date should be Date/Timestamp 
 * @param int $base_time
 * @param boolean $exclude_hh_mm_ss
 * @return int
 */
function _sumo_pp_get_timestamp( $date = '' , $base_time = 0 , $exclude_hh_mm_ss = false ) {
    $formatted_date = _sumo_pp_get_date( $date , $base_time , $exclude_hh_mm_ss ) ;

    return strtotime( "{$formatted_date} UTC" ) ;
}

/**
 * Get formatted date for display purpose.
 * @param int|string $date
 * @return string
 */
function _sumo_pp_get_date_to_display( $date ) {

    $date                       = _sumo_pp_get_date( $date ) ;
    //$date_format                = 'd-m-Y' ;
    $date_format                = 'm-d-Y' ;
    //$time_format                = 'H:i:s' ;
    $time_format                = '' ;
    $wp_date_format             = '' !== get_option( 'date_format' ) ? get_option( 'date_format' ) : 'F j, Y' ;
    $wp_time_format             = '' !== get_option( 'time_format' ) ? get_option( 'time_format' ) : 'g:i a' ;
    $set_as_wp_date_time_format = 'wordpress' === get_option( SUMO_PP_PLUGIN_PREFIX . 'set_date_time_format_as' , 'default' ) ;

    if( $set_as_wp_date_time_format ) {
        $date_format = $wp_date_format ;
        $time_format = $wp_time_format ;
    }

    if( ! is_admin() && 'enable' !== get_option( SUMO_PP_PLUGIN_PREFIX . 'show_time_in_frontend' , 'enable' ) ) {
        $time_format = '' ;
    }

    if( '' === $time_format ) {
        return date_i18n( "{$date_format}" , strtotime( $date ) ) ;
    }
    return date_i18n( "{$date_format} {$time_format}" , strtotime( $date ) ) ;
}

/**
 * Format the Date difference from Future date to Curent date.
 * @param int|string $future_date
 * @return string
 */
function _sumo_pp_get_date_difference( $future_date = null ) {
    if( ! $future_date ) {
        return '' ;
    }

    $now = new DateTime() ;

    if( is_numeric( $future_date ) && $future_date <= _sumo_pp_get_timestamp() ) {
        $interval    = abs( wp_next_scheduled( 'sumopaymentplans_cron_interval' ) - _sumo_pp_get_timestamp() ) ;
        $future_date = wp_next_scheduled( 'sumopaymentplans_cron_interval' ) ;

        //Elapse Time
        if( $interval < 2 || ($interval > 290 && $interval <= 300) ) {
            return '<b>now</b>' ;
        }
    }

    if( is_string( $future_date ) ) {
        $future_date = new DateTime( $future_date ) ;
    } elseif( is_numeric( $future_date ) ) {
        $future_date = new DateTime( date( 'Y-m-d H:i:s' , $future_date ) ) ;
    }

    if( $future_date ) {
        $interval = $future_date->diff( $now ) ;

        return $interval->format( '<b>%a</b> day(s), <b>%H</b> hour(s), <b>%I</b> minute(s), <b>%S</b> second(s)' ) ;
    }
    return 'now' ;
}

/**
 * Get multiple reminder intervals
 * @param string Mail template ID
 * @return array
 */
function _sumo_pp_get_reminder_intervals( $template_id ) {
    $intervals = '' ;
    $prefix    = SUMO_PP_PLUGIN_PREFIX ;

    switch( $template_id ) {
        case 'payment_plan_invoice':
        case 'deposit_balance_payment_invoice':
            $intervals = get_option( "{$prefix}notify_invoice_before" , '3,2,1' ) ;
            break ;
        case 'payment_plan_overdue':
        case 'deposit_balance_payment_overdue':
            $intervals = get_option( "{$prefix}notify_overdue_before" , '1' ) ;
            break ;
    }
    return array_map( 'absint' , explode( ',' , $intervals ) ) ;
}

function _sumo_pp_get_overdue_time_till( $from = 0 ) {
    $specified_overdue_days = absint( get_option( SUMO_PP_PLUGIN_PREFIX . 'specified_overdue_days' , '0' ) ) ;

    if( $specified_overdue_days > 0 ) {
        return _sumo_pp_get_timestamp( "+{$specified_overdue_days} days" , _sumo_pp_get_timestamp( $from ) ) ;
    }
    return 0 ;
}

function _sumo_pp_get_payment_plan_names( $args = array() ) {
    $plan_names    = array() ;
    $payment_plans = _sumo_pp()->query->get( wp_parse_args( $args , array(
        'type'   => 'sumo_payment_plans' ,
        'status' => 'publish' ,
        'return' => 'posts' ,
            ) ) ) ;

    if( $payment_plans ) {
        foreach( $payment_plans as $plan ) {
            $plan_names[ $plan->ID ] = $plan->post_title ;
        }
    }
    return $plan_names ;
}

function _sumo_pp_get_duration_options() {
    return array(
        'days'   => __( 'Day(s)' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        'weeks'  => __( 'Week(s)' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        'months' => __( 'Month(s)' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        'years'  => __( 'Year(s)' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            ) ;
}

function _sumo_pp_get_month_options() {
    return array(
        1  => __( 'January' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        2  => __( 'February' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        3  => __( 'March' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        4  => __( 'April' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        5  => __( 'May' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        6  => __( 'June' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        7  => __( 'July' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        8  => __( 'August' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        9  => __( 'September' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        10 => __( 'October' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        11 => __( 'November' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        12 => __( 'December' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            ) ;
}

function _sumo_pp_get_posts( $args = array() ) {
    return _sumo_pp()->query->get( $args ) ;
}

function _sumo_pp_get_active_payment_gateways() {
    $payment_gateways   = array() ;
    $available_gateways = WC()->payment_gateways->get_available_payment_gateways() ;

    foreach( $available_gateways as $key => $value ) {
        $payment_gateways[ $key ] = $value->title ;
    }
    return $payment_gateways ;
}

/**
 * Get WP User roles
 * @global object $wp_roles
 * @param bool $include_guest
 * @return array
 */
function _sumo_pp_get_user_roles( $include_guest = false ) {
    global $wp_roles ;

    $user_role_key  = array() ;
    $user_role_name = array() ;

    foreach( $wp_roles->roles as $_user_role_key => $user_role ) {
        $user_role_key[]  = $_user_role_key ;
        $user_role_name[] = $user_role[ 'name' ] ;
    }
    $user_roles = array_combine( ( array ) $user_role_key , ( array ) $user_role_name ) ;

    if( $include_guest ) {
        $user_roles = array_merge( $user_roles , array( 'guest' => 'Guest' ) ) ;
    }

    return $user_roles ;
}

function _sumo_pp_get_product_categories() {
    $categories   = array() ;
    $categoryid   = array() ;
    $categoryname = array() ;

    $listcategories = get_terms( 'product_cat' ) ;

    if( is_array( $listcategories ) ) {
        foreach( $listcategories as $category ) {
            $categoryname[] = $category->name ;
            $categoryid[]   = $category->term_id ;
        }
    }

    if( $categoryid && $categoryname ) {
        $categories = array_combine( ( array ) $categoryid , ( array ) $categoryname ) ;
    }
    return $categories ;
}

/**
 * Get payment interval cycle in days.
 * @return int
 */
function _sumo_pp_get_payment_cycle_in_days( $payment_length = null , $payment_period = null , $next_payment_date = null ) {

    if( ! is_null( $next_payment_date ) ) {
        $current_time      = _sumo_pp_get_timestamp() ;
        $next_payment_time = _sumo_pp_get_timestamp( $next_payment_date ) ;
        $payment_cycle     = absint( $next_payment_time - $current_time ) ;
    } else {
        $payment_length = absint( $payment_length ) ;

        switch( $payment_period ) {
            case 'years':
                $payment_cycle = 31556926 * $payment_length ;
                break ;
            case 'months':
                $payment_cycle = 2629743 * $payment_length ;
                break ;
            case 'weeks':
                $payment_cycle = 604800 * $payment_length ;
                break ;
            default :
                $payment_cycle = 86400 * $payment_length ;
                break ;
        }
    }
    return ceil( $payment_cycle / 86400 ) ;
}

/**
 * Get balance payable order from Pay for Order page
 * @global object $wp
 * @return int
 */
function _sumo_pp_get_balance_payable_order_in_pay_for_order_page() {
    global $wp ;

    if( ! isset( $_GET[ 'pay_for_order' ] ) || ! isset( $_GET[ 'key' ] ) ) {
        return 0 ;
    }
    if( _sumo_pp_is_balance_payment_order( $wp->query_vars[ 'order-pay' ] ) ) {
        return $wp->query_vars[ 'order-pay' ] ;
    }
    return 0 ;
}

function _sumo_pp_get_shortcodes_from_cart_r_checkout( $values ) {
    $shortcodes = array(
        '[sumo_pp_next_payment_date]'          => '' ,
        '[sumo_pp_next_installment_amount]'    => '' ,
        '[sumo_pp_current_installment_amount]' => '' ,
        '[sumo_pp_payment_plan_name]'          => '' ,
        '[sumo_pp_payment_plan_desc]'          => '' ,
            ) ;

    $shortcodes[ '[sumo_pp_total_payable]' ]   = wc_price( $values[ 'total_payable_amount' ] ) ;
    $shortcodes[ '[sumo_pp_balance_payable]' ] = wc_price( $values[ 'remaining_payable_amount' ] ) ;

    if( isset( $values[ 'payment_product_props' ][ 'payment_type' ] ) ) {
        $values[ 'payment_type' ] = $values[ 'payment_product_props' ][ 'payment_type' ] ;
    }

    if( is_numeric( $values[ 'down_payment' ] ) ) {
        $shortcodes[ '[sumo_pp_current_installment_amount]' ] = wc_price( $values[ 'down_payment' ] * $values[ 'product_qty' ] ) ;
    }

    if( 'payment-plans' === $values[ 'payment_type' ] ) {
        if( ! isset( $values[ 'next_installment_amount' ] ) && isset( $values[ 'payment_product_props' ] ) ) {
            $shortcodes[ '[sumo_pp_next_installment_amount]' ] = wc_price( SUMO_PP_Payment_Plan_Manager::get_prop( 'next_installment_amount' , array(
                        'props'         => $values[ 'payment_plan_props' ] ,
                        'product_price' => $values[ 'payment_product_props' ][ 'product_price' ] ,
                        'qty'           => $values[ 'product_qty' ] ,
                    ) ) ) ;
        } else {
            $shortcodes[ '[sumo_pp_next_installment_amount]' ] = wc_price( $values[ 'next_installment_amount' ] ) ;
        }
    }

    if( $values[ 'next_payment_date' ] ) {
        $shortcodes[ '[sumo_pp_next_payment_date]' ] = _sumo_pp_get_date_to_display( $values[ 'next_payment_date' ] ) ;
    } else if( 'after_admin_approval' === $values[ 'activate_payment' ] ) {
        $shortcodes[ '[sumo_pp_next_payment_date]' ] = __( 'After Admin Approval' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ;
    }

    if( 'payment-plans' === $values[ 'payment_type' ] ) {
        $shortcodes[ '[sumo_pp_payment_plan_name]' ] = get_the_title( $values[ 'payment_plan_props' ][ 'plan_id' ] ) ;
        $shortcodes[ '[sumo_pp_payment_plan_desc]' ] = $values[ 'payment_plan_props' ][ 'plan_description' ] ;
    }

    return array(
        'find'    => array_keys( $shortcodes ) ,
        'replace' => array_values( $shortcodes ) ,
        'content' => $shortcodes ,
            ) ;
}

/**
 * Display WC sh fieldearc with respect to products and variations/customer
 * 
 * @param array $args
 * @param bool $echo
 * @return string echo search field
 */
function _sumo_pp_wc_search_field( $args = array() , $echo = true ) {

    $args = wp_parse_args( $args , array(
        'class'       => '' ,
        'id'          => '' ,
        'name'        => '' ,
        'type'        => '' ,
        'action'      => '' ,
        'title'       => '' ,
        'placeholder' => '' ,
        'css'         => 'width: 50%;' ,
        'multiple'    => true ,
        'allow_clear' => true ,
        'selected'    => true ,
        'options'     => array()
            ) ) ;

    ob_start() ;
    if( '' !== $args[ 'title' ] ) {
        ?>
        <tr valign="top">
            <th class="titledesc" scope="row">
                <label for="<?php echo esc_attr( $args[ 'id' ] ) ; ?>"><?php echo esc_attr( $args[ 'title' ] ) ; ?></label>
            </th>
            <td class="forminp forminp-select">
                <?php
            }
            if( _sumo_pp_is_wc_version( '<=' , '2.2' ) ) {
                ?><select <?php echo $args[ 'multiple' ] ? 'multiple="multiple"' : '' ?> name="<?php echo esc_attr( '' !== $args[ 'name' ] ? $args[ 'name' ] : $args[ 'id' ]  ) ; ?>[]" id="<?php echo esc_attr( $args[ 'id' ] ) ; ?>" class="<?php echo esc_attr( $args[ 'id' ] ) ; ?>" data-placeholder="<?php echo esc_attr( $args[ 'placeholder' ] ) ; ?>" style="<?php echo esc_attr( $args[ 'css' ] ) ; ?>"><?php
                    if( is_array( $args[ 'options' ] ) ) {
                        foreach( $args[ 'options' ] as $id ) {
                            $option_value = '' ;

                            switch( $args[ 'type' ] ) {
                                case 'product':
                                    if( $product = wc_get_product( $id ) ) {
                                        $option_value = wp_kses_post( $product->get_formatted_name() ) ;
                                    }
                                    break ;
                                case 'customer':
                                    if( $user = get_user_by( 'id' , $id ) ) {
                                        $option_value = esc_html( esc_html( $user->display_name ) . '(#' . absint( $user->ID ) . ' &ndash; ' . esc_html( $user->user_email ) . ')' ) ;
                                    }
                                    break ;
                                default :
                                    if( $post = get_post( $id ) ) {
                                        $option_value = wp_kses_post( $post->post_title ) ;
                                    }
                                    break ;
                            }
                            if( $option_value ) {
                                ?>
                                <option value="<?php echo esc_attr( $id ) ; ?>" <?php echo $args[ 'selected' ] ? 'selected="selected"' : '' ?>><?php echo $option_value ; ?></option>
                                <?php
                            }
                        }
                    }
                    ?></select><?php } else if( _sumo_pp_is_wc_version( '<' , '3.0' ) ) {
                    ?>
                <input type="hidden" name="<?php echo esc_attr( '' !== $args[ 'name' ] ? $args[ 'name' ] : $args[ 'id' ]  ) ; ?>" id="<?php echo esc_attr( $args[ 'id' ] ) ; ?>" class="<?php echo esc_attr( $args[ 'class' ] ) ; ?>" data-action="<?php echo esc_attr( $args[ 'action' ] ) ; ?>" data-placeholder="<?php echo esc_attr( $args[ 'placeholder' ] ) ; ?>" <?php echo $args[ 'multiple' ] ? 'data-multiple="true"' : '' ?> <?php echo $args[ 'allow_clear' ] ? 'data-allow_clear="true"' : '' ?> style="<?php echo esc_attr( $args[ 'css' ] ) ; ?>" <?php if( $args[ 'selected' ] ) { ?> data-selected="<?php
                    $json_ids = array() ;

                    if( is_array( $args[ 'options' ] ) ) {
                        foreach( $args[ 'options' ] as $id ) {
                            switch( $args[ 'type' ] ) {
                                case 'product':
                                    if( $product = wc_get_product( $id ) ) {
                                        $json_ids[ $id ] = wp_kses_post( $product->get_formatted_name() ) ;
                                    }
                                    break ;
                                case 'customer':
                                    if( $user = get_user_by( 'id' , $id ) ) {
                                        $json_ids[ $id ] = esc_html( $user->display_name ) . ' (#' . absint( $user->ID ) . ' &ndash; ' . esc_html( $user->user_email ) . ')' ;
                                    }
                                    break ;
                                default :
                                    if( $post = get_post( $id ) ) {
                                        $json_ids[ $id ] = wp_kses_post( $post->post_title ) ;
                                    }
                                    break ;
                            }
                        }
                    }
                    echo esc_attr( json_encode( $json_ids ) ) ;
                    ?>" value="<?php
                           echo implode( ',' , array_keys( $json_ids ) ) ;
                       }
                       ?>"/><?php } else {
                       ?>
                <select <?php echo $args[ 'multiple' ] ? 'multiple="multiple"' : '' ?> name="<?php echo esc_attr( '' !== $args[ 'name' ] ? $args[ 'name' ] : $args[ 'id' ]  ) ; ?>[]" id="<?php echo esc_attr( $args[ 'id' ] ) ; ?>" class="<?php echo esc_attr( $args[ 'class' ] ) ; ?>" data-action="<?php echo esc_attr( $args[ 'action' ] ) ; ?>" data-placeholder="<?php echo esc_attr( $args[ 'placeholder' ] ) ; ?>" <?php echo $args[ 'allow_clear' ] ? 'data-allow_clear="true"' : '' ?> style="<?php echo esc_attr( $args[ 'css' ] ) ; ?>"><?php
                    if( is_array( $args[ 'options' ] ) ) {
                        foreach( $args[ 'options' ] as $id ) {
                            $option_value = '' ;

                            switch( $args[ 'type' ] ) {
                                case 'product':
                                    if( $product = wc_get_product( $id ) ) {
                                        $option_value = wp_kses_post( $product->get_formatted_name() ) ;
                                    }
                                    break ;
                                case 'customer':
                                    if( $user = get_user_by( 'id' , $id ) ) {
                                        $option_value = esc_html( esc_html( $user->display_name ) . '(#' . absint( $user->ID ) . ' &ndash; ' . esc_html( $user->user_email ) . ')' ) ;
                                    }
                                    break ;
                                default :
                                    if( $post = get_post( $id ) ) {
                                        $option_value = wp_kses_post( $post->post_title ) ;
                                    }
                                    break ;
                            }
                            if( $option_value ) {
                                ?><option value="<?php echo esc_attr( $id ) ; ?>" <?php echo $args[ 'selected' ] ? 'selected="selected"' : '' ?>><?php echo $option_value ; ?></option><?php
                            }
                        }
                    }
                    ?></select><?php
            }
            if( '' !== $args[ 'title' ] ) {
                ?>
            </td>
        </tr>
        <?php
    }
    if( $echo ) {
        echo ob_get_clean() ;
    } else {
        return ob_get_clean() ;
    }
}
