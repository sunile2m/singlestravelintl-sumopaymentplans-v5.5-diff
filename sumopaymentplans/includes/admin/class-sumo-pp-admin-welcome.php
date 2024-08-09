<?php

class SUMO_PP_Admin_Welcome {

    protected static $welcome_page = 'sumopaymentplans-welcome-page' ;

    public static function init() {
        add_action( 'admin_menu' , __CLASS__ . '::add_welcome_page' ) ;
        add_action( 'admin_init' , __CLASS__ . '::redirect' ) ;
        add_action( 'admin_head' , __CLASS__ . '::remove_welcome_page' ) ;
    }

    public static function get_page_url() {
        return add_query_arg( array( 'page' => self::$welcome_page ) , admin_url( 'admin.php' ) ) ;
    }

    public static function load() {
        set_transient( SUMO_PP_PLUGIN_PREFIX . 'welcome_screen' , true , 30 ) ;
    }

    public static function add_welcome_page() {
        add_dashboard_page( 'Welcome To SUMO Payment Plans' , 'Welcome To SUMO Payment Plans' , 'read' , self::$welcome_page , 'SUMO_PP_Admin_Welcome::render' ) ;
    }

    public static function render() {
        ob_start() ;
        _sumo_pp_get_template( 'sumo-pp-welcome-page.php' ) ;
        ob_get_contents() ;
    }

    public static function redirect() {
        if( ! get_transient( SUMO_PP_PLUGIN_PREFIX . 'welcome_screen' ) ) {
            return ;
        }

        delete_transient( SUMO_PP_PLUGIN_PREFIX . 'welcome_screen' ) ;
        wp_safe_redirect( self::get_page_url() ) ;
    }

    public static function remove_welcome_page() {
        remove_submenu_page( 'index.php' , self::$welcome_page ) ;
    }

}
