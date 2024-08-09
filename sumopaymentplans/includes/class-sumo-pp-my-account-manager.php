<?php
if( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Manage payments in My account page
 * 
 * @class SUMO_PP_My_Account_Manager
 * @category Class
 */
class SUMO_PP_My_Account_Manager {

    public static $template_base = SUMO_PP_PLUGIN_TEMPLATE_PATH ;

    /**
     * The single instance of the class.
     */
    protected static $instance = null ;

    /**
     * Create instance for SUMO_PP_My_Account_Manager.
     */
    public static function instance() {
        if( is_null( self::$instance ) ) {
            self::$instance = new self() ;
        }
        return self::$instance ;
    }

    /**
     * Construct SUMO_PP_My_Account_Manager.
     */
    public function __construct() {
        //Compatible with Woocommerce v2.6.x and above
        add_filter( 'woocommerce_account_menu_items' , __CLASS__ . '::set_my_account_menu_items' ) ;
        add_action( 'woocommerce_account_sumo-pp-my-payments_endpoint' , __CLASS__ . '::my_payments' ) ;
        add_action( 'woocommerce_account_sumo-pp-view-payment_endpoint' , __CLASS__ . '::view_payment' ) ;
        add_action( 'sumopaymentplans_my_payments_sumo-pp-view-payment_endpoint' , __CLASS__ . '::view_payment' ) ;
        add_shortcode( 'sumo_pp_my_payments' , __CLASS__ . '::my_payments' , 10 , 3 ) ;

        //Compatible up to Woocommerce v2.5.x
        add_action( 'woocommerce_before_my_account' , array( __CLASS__ , 'bkd_cmptble_my_payments' ) ) ;
        add_filter( 'wc_get_template' , array( __CLASS__ , 'bkd_cmptble_view_payment' ) , 10 , 5 ) ;

        //May be do some restrictions in Pay for Order page
        if( isset( $_GET[ 'pay_for_order' ] ) ) {
            add_filter( 'woocommerce_product_is_in_stock' , __CLASS__ . '::prevent_from_outofstock_product' , 20 , 2 ) ;
        }
    }

    /**
     * Get my payments.
     */
    public static function get_payments() {
        global $wp ;

        try {
            $payments = _sumo_pp()->query->get( array(
                'type'       => 'sumo_pp_payments' ,
                'status'     => array_keys( _sumo_pp_get_payment_statuses() ) ,
                'meta_key'   => '_customer_id' ,
                'meta_value' => get_current_user_id() ,
                    ) ) ;

            if( empty( $payments ) ) {
                throw new Exception( __( "You don't have any payment." , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ) ;
            }
            ?>
            <p style="display:inline-table">
                <?php _e( 'Search:' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?>
                <input id="filter" type="text" style="width: 40%"/>&nbsp;
                <?php _e( 'Page Size:' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?>
                <input id="change-page-size" type="number" min="5" step="5" value="5" style="width: 25%"/>
            </p>
            <table class="shop_table shop_table_responsive my_account_orders <?php echo SUMO_PP_PLUGIN_PREFIX . 'footable' ; ?>" data-filter="#filter" data-page-size="5" data-page-previous-text="prev" data-filter-text-only="true" data-page-next-text="next" style="width:100%">
                <thead>
                    <tr>
                        <th class="<?php echo SUMO_PP_PLUGIN_PREFIX . '-payment-number' ; ?>"><span class="nobr"><?php _e( 'Payment Number' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></span></th>
                        <th class="<?php echo SUMO_PP_PLUGIN_PREFIX . '-product-title' ; ?>"><span class="nobr"><?php _e( 'Product Title' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></span></th>
                        <th class="<?php echo SUMO_PP_PLUGIN_PREFIX . '-payment-plan' ; ?>"><span class="nobr"><?php _e( 'Payment Plan' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></span></th>
                        <th class="<?php echo SUMO_PP_PLUGIN_PREFIX . '-payment-status' ; ?>"><span class="nobr"><?php _e( 'Payment Status' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></span></th>
                        <th data-sort-ignore="true">&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach( $payments as $payment_id ) :
                        $payment                                  = _sumo_pp_get_payment( $payment_id ) ;
                        $wp->query_vars[ 'sumo-pp-view-payment' ] = $payment->id ;
                        ?>
                        <tr class="<?php echo SUMO_PP_PLUGIN_PREFIX . '-data' ; ?>">
                            <td class="<?php echo SUMO_PP_PLUGIN_PREFIX . '-payment-number' ; ?>" data-title="<?php _e( 'Payment Number' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?>">
                                <?php
                                echo '<a href="' . $payment->get_view_endpoint_url() . '">#' . $payment->get_payment_number() . '</a>' ;
                                ?>
                            </td>
                            <td class="<?php echo SUMO_PP_PLUGIN_PREFIX . '-product-title' ; ?>" data-title="<?php _e( 'Product Title' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?>">
                                <?php
                                echo $payment->get_formatted_product_name() ;
                                ?>
                            </td>
                            <td class="<?php echo SUMO_PP_PLUGIN_PREFIX . '-payment-plan' ; ?>" data-title="<?php _e( 'Payment Plan' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?>">
                                <?php
                                if( 'payment-plans' === $payment->get_payment_type() ) {
                                    echo $payment->get_plan()->post_title ;
                                } else {
                                    echo 'N/A' ;
                                }
                                ?>
                            </td>
                            <td class="<?php echo SUMO_PP_PLUGIN_PREFIX . '-payment-status' ; ?>" data-title="<?php _e( 'Payment Status' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?>">
                                <?php
                                if( $payment->has_status( 'await_cancl' ) ) {
                                    $payment_statuses = _sumo_pp_get_payment_statuses() ;
                                    printf( '<mark class="%s"/>%s</mark>' , SUMO_PP_PLUGIN_PREFIX . 'overdue' , esc_attr( $payment_statuses[ SUMO_PP_PLUGIN_PREFIX . 'overdue' ] ) ) ;
                                } else {
                                    printf( '<mark class="%s"/>%s</mark>' , $payment->get_status( true ) , esc_attr( $payment->get_status_label() ) ) ;
                                }
                                ?>
                            </td>
                            <td class="<?php echo SUMO_PP_PLUGIN_PREFIX . '-view-payment' ; ?>">
                                <a href="<?php echo $payment->get_view_endpoint_url() ; ?>" class="button view" data-action="view"><?php _e( 'View' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></a>
                            </td>
                        </tr>
                    <?php endforeach ; ?>
                </tbody>
            </table>
            <div class="pagination pagination-centered"></div>
            <?php
        } catch( Exception $e ) {
            ?>
            <div class="<?php echo SUMO_PP_PLUGIN_PREFIX . '-payment-not-found' ; ?> woocommerce-Message woocommerce-Message--info woocommerce-info">
                <p>
                    <?php echo $e->getMessage() ; ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Set our menus under My account menu items
     * @param array $items
     * @return array
     */
    public static function set_my_account_menu_items( $items ) {
        $menu     = array(
            'sumo-pp-my-payments' => apply_filters( 'sumopaymentplans_my_payments_title' , __( 'My Payments' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ) ,
                ) ;
        $position = 2 ;

        $items = array_slice( $items , 0 , $position ) + $menu + array_slice( $items , $position , count( $items ) - 1 ) ;

        return $items ;
    }

    /**
     * Output my payments table.
     */
    public static function my_payments( $atts = '' , $content = '' , $tag = '' ) {
        global $wp ;

        if( 'sumo_pp_my_payments' === $tag ) {
            if( ! empty( $wp->query_vars ) ) {
                foreach( $wp->query_vars as $key => $value ) {
                    // Ignore pagename param.
                    if( 'pagename' === $key ) {
                        continue ;
                    }

                    if( has_action( 'sumopaymentplans_my_payments_' . $key . '_endpoint' ) ) {
                        do_action( 'sumopaymentplans_my_payments_' . $key . '_endpoint' , $value ) ;
                        return ;
                    }
                }
            }
        }

        echo self::get_payments() ;
    }

    /**
     * Output Payment content.
     * @param int $payment_id
     */
    public static function view_payment( $payment_id ) {

        if( $payment = _sumo_pp_get_payment( $payment_id ) ) {
            _sumo_pp_get_template( 'view-payment.php' , array(
                'payment_id' => $payment->id ,
                'payment'    => $payment ,
            ) ) ;
        } else {
            // No endpoint found? Default to dashboard.
            wc_get_template( 'myaccount/dashboard.php' , array(
                'current_user' => get_user_by( 'id' , get_current_user_id() ) ,
            ) ) ;
        }
    }

    /**
     * Output my payments table up to Woocommerce v2.5.x
     */
    public static function bkd_cmptble_my_payments() {

        if( _sumo_pp_is_wc_version( '<' , '2.6' ) ) {
            echo '<h2>' . apply_filters( 'sumopaymentplans_my_payments_title' , __( 'My Payments' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ) . '</h2>' ;
            echo self::get_payments() ;
        }
    }

    /**
     * Output payment content up to Woocommerce v2.5.x
     * @global object $wp
     * @param string $located
     * @param string $template_name
     * @param array $args
     * @param string $template_path
     * @param string $default_path
     * @return string
     */
    public static function bkd_cmptble_view_payment( $located , $template_name , $args , $template_path , $default_path ) {
        global $wp ;

        if( _sumo_pp_is_wc_version( '<' , '2.6' ) && isset( $_GET[ 'payment-id' ] ) && _sumo_pp_get_payment( $_GET[ 'payment-id' ] ) ) {

            $wp->query_vars[ 'sumo-pp-view-payment' ] = absint( $_GET[ 'payment-id' ] ) ;

            return self::$template_base . 'view-payment.php' ;
        }
        return $located ;
    }

    public static function prevent_from_outofstock_product( $is_in_stock , $product ) {
        if( ! $is_in_stock ) {
            if( $balance_payable_order = _sumo_pp_get_balance_payable_order_in_pay_for_order_page() ) {
                return true ;
            }
        }
        return $is_in_stock ;
    }

}
