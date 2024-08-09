<?php
if( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle Admin menus, post types and settings.
 * 
 * @class SUMO_PP_Admin_Settings
 * @category Class
 */
class SUMO_PP_Admin_Settings {

    /**
     * Setting pages.
     *
     * @var array
     */
    private static $settings = array() ;

    /**
     * Init SUMO_PP_Admin_Settings.
     */
    public static function init() {
        add_action( 'sumopaymentplans_reset_options' , __CLASS__ . '::reset_options' ) ;
        add_filter( 'woocommerce_account_settings' , __CLASS__ . '::add_wc_account_settings' ) ;
    }

    /**
     * Include the settings page classes.
     */
    public static function get_settings_pages() {
        if( empty( self::$settings ) ) {

            self::$settings[] = include( 'settings-page/class-sumo-pp-admin-general-settings.php' ) ;
            self::$settings[] = include( 'settings-page/class-sumo-pp-admin-order-payment-plan-settings.php' ) ;
            self::$settings[] = include( 'settings-page/class-sumo-pp-admin-message-settings.php' ) ;
            self::$settings[] = include( 'settings-page/class-sumo-pp-admin-advance-settings.php' ) ;
            self::$settings[] = include( 'settings-page/class-sumo-pp-admin-bulk-action-settings.php' ) ;
            self::$settings[] = include( 'settings-page/class-sumo-pp-admin-help-settings.php' ) ;
        }

        return self::$settings ;
    }

    /**
     * Settings page.
     *
     * Handles the display of the main SUMO Payment Plans settings page in admin.
     */
    public static function output() {
        global $current_section , $current_tab ;

        do_action( 'sumopaymentplans_settings_start' ) ;

        $current_tab     = ( empty( $_GET[ 'tab' ] ) ) ? 'general' : sanitize_text_field( urldecode( $_GET[ 'tab' ] ) ) ;
        $current_section = ( empty( $_REQUEST[ 'section' ] ) ) ? '' : sanitize_text_field( urldecode( $_REQUEST[ 'section' ] ) ) ;

        // Include settings pages
        self::get_settings_pages() ;

        do_action( 'sumopaymentplans_add_options_' . $current_tab ) ;
        do_action( 'sumopaymentplans_add_options' ) ;

        if( $current_section ) {
            do_action( 'sumopaymentplans_add_options_' . $current_tab . '_' . $current_section ) ;
        }

        if( ! empty( $_POST[ 'save' ] ) ) {
            if( empty( $_REQUEST[ '_wpnonce' ] ) || ! wp_verify_nonce( $_REQUEST[ '_wpnonce' ] , 'sumo-payment-plans-settings' ) )
                die( __( 'Action failed. Please refresh the page and retry.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ) ;

            // Save settings if data has been posted
            do_action( 'sumopaymentplans_update_options_' . $current_tab ) ;
            do_action( 'sumopaymentplans_update_options' ) ;

            if( $current_section ) {
                do_action( 'sumopaymentplans_update_options_' . $current_tab . '_' . $current_section ) ;
            }

            wp_safe_redirect( esc_url_raw( add_query_arg( array( 'saved' => 'true' ) ) ) ) ;
            exit ;
        }
        if( ! empty( $_POST[ 'reset' ] ) || ! empty( $_POST[ 'reset_all' ] ) ) {
            if( empty( $_REQUEST[ '_wpnonce' ] ) || ! wp_verify_nonce( $_REQUEST[ '_wpnonce' ] , 'sumo-payment-plans-reset-settings' ) )
                die( __( 'Action failed. Please refresh the page and retry.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ) ;

            do_action( 'sumopaymentplans_reset_options_' . $current_tab ) ;

            if( ! empty( $_POST[ 'reset_all' ] ) ) {
                do_action( 'sumopaymentplans_reset_options' ) ;
            }
            if( $current_section ) {
                do_action( 'sumopaymentplans_reset_options_' . $current_tab . '_' . $current_section ) ;
            }

            wp_safe_redirect( esc_url_raw( add_query_arg( array( 'saved' => 'true' ) ) ) ) ;
            exit ;
        }
        // Get any returned messages
        $error   = ( empty( $_GET[ 'wc_error' ] ) ) ? '' : urldecode( stripslashes( $_GET[ 'wc_error' ] ) ) ;
        $message = ( empty( $_GET[ 'wc_message' ] ) ) ? '' : urldecode( stripslashes( $_GET[ 'wc_message' ] ) ) ;

        if( $error || $message ) {
            if( $error ) {
                echo '<div id="message" class="error fade"><p><strong>' . esc_html( $error ) . '</strong></p></div>' ;
            } else {
                echo '<div id="message" class="updated fade"><p><strong>' . esc_html( $message ) . '</strong></p></div>' ;
            }
        } elseif( ! empty( $_GET[ 'saved' ] ) ) {
            echo '<div id="message" class="updated fade"><p><strong>' . __( 'Your settings have been saved.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) . '</strong></p></div>' ;
        }
        ?>
        <div class="wrap woocommerce">
            <form method="post" id="mainform" action="" enctype="multipart/form-data">
                <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
                <h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
                    <?php
                    $tabs = apply_filters( 'sumopaymentplans_settings_tabs_array' , array() ) ;

                    foreach( $tabs as $name => $label ) {
                        echo '<a href="' . admin_url( 'admin.php?page=sumo_pp_settings&tab=' . $name ) . '" class="nav-tab ' . ( $current_tab == $name ? 'nav-tab-active' : '' ) . '">' . $label . '</a>' ;
                    }
                    do_action( 'sumopaymentplans_settings_tabs' ) ;
                    ?>
                </h2>
                <?php
                switch( $current_tab ) :
                    default :
                        do_action( 'sumopaymentplans_sections_' . $current_tab ) ;
                        do_action( 'sumopaymentplans_settings_' . $current_tab ) ;
                        break ;
                endswitch ;
                ?>
                <?php if( apply_filters( 'sumopaymentplans_submit_' . $current_tab , true ) ) : ?>
                    <p class="submit">
                        <?php if( ! isset( $GLOBALS[ 'hide_save_button' ] ) ) : ?>
                            <input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?>" />
                        <?php endif ; ?>
                        <input type="hidden" name="subtab" id="last_tab" />
                        <?php wp_nonce_field( 'sumo-payment-plans-settings' ) ; ?>
                    </p>
                <?php endif ; ?>
            </form>
            <?php if( apply_filters( 'sumopaymentplans_reset_' . $current_tab , true ) ) : ?>
                <form method="post" id="reset_mainform" action="" enctype="multipart/form-data" style="float: left; margin-top: -52px; margin-left: 159px;">
                    <input name="reset" class="button-secondary" type="submit" value="<?php _e( 'Reset' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?>"/>
                    <input name="reset_all" class="button-secondary" type="submit" value="<?php _e( 'Reset All' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?>"/>
                    <?php wp_nonce_field( 'sumo-payment-plans-reset-settings' ) ; ?>
                </form>    
            <?php endif ; ?>
        </div>
        <?php
    }

    /**
     * Default options.
     *
     * Sets up the default options used on the settings page.
     */
    public static function save_default_options( $reset_all = false ) {

        if( empty( self::$settings ) ) {
            self::get_settings_pages() ;
        }

        foreach( self::$settings as $tab ) {
            if( ! isset( $tab->settings ) || ! is_array( $tab->settings ) ) {
                continue ;
            }

            $tab->add_options( $reset_all ) ;
        }
    }

    /**
     * Reset All settings
     */
    public static function reset_options() {

        self::save_default_options( true ) ;
    }

    /**
     * Add privacy setings under WooCommerce Privacy
     * @param array $settings
     * @return array
     */
    public static function add_wc_account_settings( $settings ) {
        $original_settings = $settings ;

        if( ! empty( $original_settings ) ) {
            $new_settings = array() ;

            foreach( $original_settings as $pos => $setting ) {
                if( ! isset( $setting[ 'id' ] ) ) {
                    continue ;
                }

                switch( $setting[ 'id' ] ) {
                    case 'woocommerce_erasure_request_removes_order_data':
                        $new_settings[ $pos + 1 ] = array(
                            'title'         => __( 'Account erasure requests' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                            'desc'          => __( 'Remove personal data from SUMO Payment Plans and its related Orders' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                            /* Translators: %s URL to erasure request screen. */
                            'desc_tip'      => sprintf( __( 'When handling an <a href="%s">account erasure request</a>, should personal data within SUMO Payment Plans be retained or removed?' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , esc_url( admin_url( 'tools.php?page=remove_personal_data' ) ) ) ,
                            'id'            => SUMO_PP_PLUGIN_PREFIX . 'erasure_request_removes_payment_data' ,
                            'type'          => 'checkbox' ,
                            'default'       => 'no' ,
                            'checkboxgroup' => '' ,
                            'autoload'      => false ,
                                ) ;
                        break ;
                }
            }
            if( ! empty( $new_settings ) ) {
                foreach( $new_settings as $pos => $new_setting ) {
                    array_splice( $settings , $pos , 0 , array( $new_setting ) ) ;
                }
            }
        }
        return $settings ;
    }

}

SUMO_PP_Admin_Settings::init() ;
