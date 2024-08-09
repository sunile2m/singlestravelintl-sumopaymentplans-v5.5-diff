<?php

if( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * SUMO Payment Plans Admin
 * 
 * @class SUMO_PP_Admin
 * @category Class
 */
class SUMO_PP_Admin {

    /**
     * Init SUMO_PP_Admin.
     */
    public static function init() {
        add_action( 'init' , __CLASS__ . '::includes' ) ;
        add_action( 'admin_menu' , __CLASS__ . '::admin_menus' ) ;
        add_filter( 'plugin_row_meta' , __CLASS__ . '::plugin_row_meta' , 10 , 2 ) ;
        add_filter( 'plugin_action_links_' . SUMO_PP_PLUGIN_BASENAME , __CLASS__ . '::plugin_action_links' ) ;
    }

    /**
     * Include any classes we need within admin.
     */
    public static function includes() {
        include_once('class-sumo-pp-admin-post-types.php') ;
        include_once('class-sumo-pp-admin-meta-boxes.php') ;
        include_once('class-sumo-pp-admin-product.php') ;
        include_once('class-sumo-pp-admin-settings.php') ;
        include_once('class-sumo-pp-admin-payments-exporter.php') ;
    }

    /**
     * Show action links on the plugin screen.
     *
     * @param	mixed $links Plugin Action links
     * @return	array
     */
    public static function plugin_action_links( $links ) {
        $setting_page_link = '<a  href="' . admin_url( 'admin.php?page=sumo_pp_settings' ) . '">Settings</a>' ;
        array_unshift( $links , $setting_page_link ) ;
        return $links ;
    }

    /**
     * Show row meta on the plugin screen.
     *
     * @param	mixed $links Plugin Row Meta
     * @param	mixed $file  Plugin Base file
     * @return	array
     */
    public static function plugin_row_meta( $links , $file ) {
        if( SUMO_PP_PLUGIN_BASENAME == $file ) {
            $row_meta = array(
                'about'   => '<a href="' . esc_url( SUMO_PP_Admin_Welcome::get_page_url() ) . '" aria-label="' . esc_attr__( 'About' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) . '">' . esc_html__( 'About' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) . '</a>' ,
                'support' => '<a href="' . esc_url( 'http://fantasticplugins.com/support/' ) . '" aria-label="' . esc_attr__( 'Support' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) . '">' . esc_html__( 'Support' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) . '</a>' ,
                    ) ;

            return array_merge( $links , $row_meta ) ;
        }

        return ( array ) $links ;
    }

    /**
     * Add admin menu pages.
     */
    public static function admin_menus() {
        add_menu_page( __( 'SUMO Payment Plans' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , __( 'SUMO Payment Plans' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , 'manage_woocommerce' , SUMO_PP_PLUGIN_TEXT_DOMAIN , null , SUMO_PP_PLUGIN_URL . '/assets/images/payments.png' , '56.5' ) ;
        add_submenu_page( SUMO_PP_PLUGIN_TEXT_DOMAIN , __( 'Settings' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , __( 'Settings' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , 'manage_woocommerce' , 'sumo_pp_settings' , 'SUMO_PP_Admin_Settings::output' ) ;
        add_submenu_page( SUMO_PP_PLUGIN_TEXT_DOMAIN , __( 'Payment Export' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , __( 'Payment Export' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , 'manage_woocommerce' , SUMO_PP_Payments_Exporter::$exporter_page , 'SUMO_PP_Payments_Exporter::render_exporter_html_fields' ) ;
    }

}

SUMO_PP_Admin::init() ;
