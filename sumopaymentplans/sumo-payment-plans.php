<?php

/**
 * Plugin Name: SUMO Payment Plans
 * Plugin URI:
 * Description: SUMO Payment Plans is a Comprehensive WooCommerce Payment Plan plugin using which you can configure multiple Payment Plans like Deposits with Balance Payment, Fixed Amount Installments, Variable Amount Installments, Down Payments with Installments, etc in your WooCommerce Shop.
 * Version: 5.5
 * Author: Fantastic Plugins
 * Author URI: http://fantasticplugins.com
 */
if( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/** Initiate Payment Plans class.
 * 
 * @class SUMOPaymentPlans
 * @category Class
 */
final class SUMOPaymentPlans {

    /**
     * Payment Plans version.
     * 
     * @var string 
     */
    public $version = '5.5' ;

    /**
     * Payment Plans prefix.
     * 
     * @var string 
     */
    public $prefix = '_sumo_pp_' ;

    /**
     * Payment Plans Text domain.
     * 
     * @var string 
     */
    public $text_domain = 'sumopaymentplans' ;

    /**
     * Get Query instance.
     * @var SUMO_PP_Query object 
     */
    public $query ;

    /**
     * The single instance of the class.
     */
    protected static $instance = null ;

    /**
     * SUMOPaymentPlans constructor.
     */
    public function __construct() {

        //Prevent fatal error by load the files when you might call init hook.
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' ) ;

        if( ! $this->is_woocommerce_active() ) {
            return ;  // Return to stop the existing function to be call 
        }

        $this->define_constants() ;
        $this->include_files() ;
        $this->init_hooks() ;
    }

    /**
     * Main SUMOPaymentPlans Instance.
     * Ensures only one instance of SUMOPaymentPlans is loaded or can be loaded.
     * 
     * @return SUMOPaymentPlans - Main instance.
     */
    public static function instance() {
        if( is_null( self::$instance ) ) {
            self::$instance = new self() ;
        }
        return self::$instance ;
    }

    /**
     * Check WooCommerce Plugin is Active.
     * @return boolean
     */
    public function is_woocommerce_active() {
        //Prevent Header Problem.
        add_action( 'init' , array( $this , 'prevent_header_already_sent_problem' ) , 1 ) ;
        //Display warning if woocommerce is not active.
        add_action( 'init' , array( $this , 'woocommerce_dependency_warning_message' ) ) ;

        if( is_multisite() && is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) && is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            return true ;
        } else if( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            return true ;
        }
        return false ;
    }

    /**
     * Prevent header problem while plugin activates.
     */
    public function prevent_header_already_sent_problem() {
        ob_start() ;
    }

    public function woocommerce_dependency_warning_message() {
        if( ! $this->is_woocommerce_active() && is_admin() ) {
            $error = "<div class='error'><p> SUMO Payment Plans Plugin requires WooCommerce Plugin should be Active !!! </p></div>" ;
            echo $error ;
        }
        return ;
    }

    /**
     * Define constants.
     */
    private function define_constants() {
        $this->define( 'SUMO_PP_PLUGIN_FILE' , __FILE__ ) ;
        $this->define( 'SUMO_PP_PLUGIN_BASENAME' , plugin_basename( SUMO_PP_PLUGIN_FILE ) ) ;
        $this->define( 'SUMO_PP_PLUGIN_BASENAME_DIR' , dirname( SUMO_PP_PLUGIN_BASENAME ) . '/' ) ;
        $this->define( 'SUMO_PP_PLUGIN_DIR' , plugin_dir_path( SUMO_PP_PLUGIN_FILE ) ) ;
        $this->define( 'SUMO_PP_PLUGIN_TEMPLATE_PATH' , SUMO_PP_PLUGIN_DIR . 'templates/' ) ;
        $this->define( 'SUMO_PP_PLUGIN_URL' , untrailingslashit( plugins_url( '/' , SUMO_PP_PLUGIN_FILE ) ) ) ;
        $this->define( 'SUMO_PP_PLUGIN_VERSION' , $this->version ) ;
        $this->define( 'SUMO_PP_PLUGIN_PREFIX' , $this->prefix ) ;
        $this->define( 'SUMO_PP_PLUGIN_TEXT_DOMAIN' , $this->text_domain ) ;
        $this->define( 'SUMO_PP_PLUGIN_CRON_INTERVAL' , 300 ) ; //in seconds
    }

    /**
     * Define constant if not already set.
     *
     * @param string      $name  Constant name.
     * @param string|bool $value Constant value.
     */
    private function define( $name , $value ) {
        if( ! defined( $name ) ) {
            define( $name , $value ) ;
        }
    }

    /**
     * Include required core files used in admin and on the frontend.
     */
    private function include_files() {

        //Class autoloader.
        include_once('includes/class-sumo-pp-autoload.php') ;

        //Abstract classes.
        include_once('includes/abstracts/abstract-sumo-pp-settings.php') ;
        include_once('includes/abstracts/abstract-sumo-pp-payment.php') ;
        include_once('includes/abstracts/abstract-sumo-pp-cron-job.php') ;

        //Core functions.
        include_once('includes/sumo-pp-functions.php') ;

        //Init Query
        $this->query = new SUMO_PP_Query() ;

        //Core classes.
        include_once('includes/class-sumo-pp-post-types.php') ;
        include_once('includes/class-sumo-pp-comments.php') ;
        include_once('includes/class-sumo-pp-ajax.php') ;
        include_once('includes/class-sumo-pp-enqueues.php') ;
        include_once('includes/privacy/class-sumo-pp-privacy.php') ;

        // Libraries
        if( ! class_exists( 'ActionScheduler' ) ) {
            include_once('includes/lib/action-scheduler/action-scheduler.php') ;
        }

        if( is_admin() ) {
            include_once('includes/admin/class-sumo-pp-admin.php') ;
        }

        $this->load_class_instances() ;
    }

    /**
     * Load our class instances
     */
    private function load_class_instances() {

        SUMO_PP_Payment_Plan_Manager::instance() ;
        SUMO_PP_Product_Manager::instance() ;
        SUMO_PP_Order_Payment_Plan::instance() ;
        SUMO_PP_Order_Manager::instance() ;
        SUMO_PP_Order_Item_Manager::instance() ;

        if( ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) ) {
            SUMO_PP_Cart_Manager::instance() ;
            SUMO_PP_Checkout_Manager::instance() ;
            SUMO_PP_My_Account_Manager::instance() ;
        }
    }

    /**
     * Hook into actions and filters.
     */
    private function init_hooks() {
        register_activation_hook( SUMO_PP_PLUGIN_FILE , array( $this , 'init_upon_activation' ) ) ;
        register_deactivation_hook( SUMO_PP_PLUGIN_FILE , array( $this , 'init_upon_deactivation' ) ) ;
        add_action( 'plugins_loaded' , array( $this , 'set_language_to_translate' ) ) ;
        add_action( 'init' , array( $this , 'init' ) , 0 ) ;
        add_filter( 'cron_schedules' , array( $this , 'cron_schedules' ) , 9999 ) ;
    }

    /**
     *  Fire upon activating SUMO Payment Plans
     */
    public function init_upon_activation() {
        SUMO_PP_Admin_Welcome::load() ;
    }

    /**
     * Fire upon deactivating SUMO Payment Plans
     */
    public function init_upon_deactivation() {
        wp_clear_scheduled_hook( 'sumopaymentplans_cron_interval' ) ;
    }

    /**
     *  Load language files. 
     */
    public function set_language_to_translate() {
        load_plugin_textdomain( $this->text_domain , false , SUMO_PP_PLUGIN_BASENAME_DIR . 'languages' ) ;
    }

    /**
     * Schedule Cron interval for recurrence
     * @param array $schedules
     * @return array
     */
    public function cron_schedules( $schedules ) {
        $schedules[ 'sumopaymentplans_cron_interval' ] = array(
            'interval' => SUMO_PP_PLUGIN_CRON_INTERVAL ,
            'display'  => sprintf( __( 'Every %d Minutes' , $this->text_domain ) , SUMO_PP_PLUGIN_CRON_INTERVAL / 60 )
                ) ;

        return $schedules ;
    }

    /**
     * Init SUMOPaymentPlans when WordPress Initialises. 
     */
    public function init() {
        $this->update_plugin_version() ;

        //Init Admin welcome page
        SUMO_PP_Admin_Welcome::init() ;

        //Init backgound process
        SUMO_PP_Background_Updater::init() ;

        $this->other_plugin_support_includes() ;
    }

    /**
     * Check SUMO Payment Plans version and run updater
     */
    private function update_plugin_version() {
        if( $this->version !== get_option( $this->prefix . 'version' ) ) {
            delete_option( $this->prefix . 'version' ) ;
            add_option( $this->prefix . 'version' , $this->version ) ;

            include_once('includes/admin/class-sumo-pp-admin-settings.php') ;
            SUMO_PP_Admin_Settings::save_default_options() ;
        }
    }

    /**
     * Include classes for plugin support.
     */
    private function other_plugin_support_includes() {
        if( class_exists( 'WC_Bookings' ) ) {
            include_once( 'includes/compatibilities/class-sumo-pp-wc-bookings.php' ) ;
        }
        if( class_exists( 'YITH_WCBK' ) ) {
            include_once( 'includes/compatibilities/class-sumo-pp-yith-wc-bookings.php' ) ;
        }
        if( class_exists( 'SUMO_Bookings' ) ) {
            include_once( 'includes/compatibilities/class-sumo-pp-sumo-bookings.php' ) ;
        }
        if( class_exists( 'SUMOPreOrders' ) ) {
            include_once( 'includes/compatibilities/class-sumo-pp-sumo-preorders.php' ) ;
        }
        if( class_exists( 'Tribe__Tickets__Main' ) ) {
            include_once( 'includes/compatibilities/class-sumo-pp-event-tickets.php' ) ;
        }
    }

}

/**
 * Main instance of SUMOPaymentPlans.
 * Returns the main instance of SUMOPaymentPlans.
 *
 * @return SUMOPaymentPlans
 */
function _sumo_pp() {
    return SUMOPaymentPlans::instance() ;
}

/**
 * Run SUMO Payment Plans
 */
_sumo_pp() ;
