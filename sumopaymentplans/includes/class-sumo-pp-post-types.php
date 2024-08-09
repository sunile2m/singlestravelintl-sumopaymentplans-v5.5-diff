<?php

if( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Post Types
 * 
 * Registers post types
 * 
 * @class SUMO_PP_Post_Types
 * @category Class
 */
class SUMO_PP_Post_Types {

    /**
     * Init SUMO_PP_Post_Types.
     */
    public static function init() {
        add_action( 'init' , __CLASS__ . '::register_post_types' ) ;
        add_action( 'init' , __CLASS__ . '::register_post_status' ) ;
    }

    /**
     * Register our custom post types.
     */
    public static function register_post_types() {

        //For Payments.
        register_post_type( 'sumo_pp_payments' , array(
            'labels'       => array(
                'name'               => _x( 'Payments' , 'general name' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'singular_name'      => _x( 'Payment' , 'singular name' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'menu_name'          => _x( 'Payments' , 'admin menu' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'name_admin_bar'     => _x( 'Payment' , 'add new on admin bar' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'add_new'            => _x( 'Add New' , 'Payment' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'add_new_item'       => __( 'Add New Payment' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'new_item'           => __( 'New Payment' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'edit_item'          => __( 'Edit Payment' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'view_item'          => __( 'View Payment' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'all_items'          => __( 'Payments' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'search_items'       => __( 'Search Payment' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'parent_item_colon'  => __( 'Parent Payments:' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'not_found'          => __( 'No Payment Found.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'not_found_in_trash' => __( 'No Payment found in Trash.' , SUMO_PP_PLUGIN_TEXT_DOMAIN )
            ) ,
            'description'  => __( 'This is where store payments are stored.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'public'       => false ,
            'show_ui'      => true ,
            'show_in_menu' => SUMO_PP_PLUGIN_TEXT_DOMAIN ,
            'rewrite'      => false ,
            'has_archive'  => false ,
            'supports'     => false ,
            'capabilities' => array(
                'edit_post'          => 'manage_woocommerce' ,
                'edit_posts'         => 'manage_woocommerce' ,
                'edit_others_posts'  => 'manage_woocommerce' ,
                'publish_posts'      => 'manage_woocommerce' ,
                'read_post'          => 'manage_woocommerce' ,
                'read_private_posts' => 'manage_woocommerce' ,
                'delete_post'        => 'manage_woocommerce' ,
                'delete_posts'       => true ,
                'create_posts'       => 'do_not_allow'
            ) ,
        ) ) ;

        //For Payment Plans.
        register_post_type( 'sumo_payment_plans' , array(
            'labels'              => array(
                'name'               => _x( 'Payment Plans' , 'general name' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'singular_name'      => _x( 'Payment Plan' , 'singular name' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'menu_name'          => _x( 'Payment Plans' , 'admin menu' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'name_admin_bar'     => _x( 'Payment Plan' , 'add new on admin bar' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'add_new'            => _x( 'Add New' , 'Payment Plan' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'add_new_item'       => __( 'Add New Payment Plan' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'new_item'           => __( 'New Payment Plan' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'edit_item'          => __( 'Edit Payment Plan' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'view_item'          => __( 'View Payment Plan' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'all_items'          => __( 'Payment Plans' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'search_items'       => __( 'Search Payment Plan' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'parent_item_colon'  => __( 'Parent Payment Plans:' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'not_found'          => __( 'No Payment Plan Found.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'not_found_in_trash' => __( 'No Payment Plan found in Trash.' , SUMO_PP_PLUGIN_TEXT_DOMAIN )
            ) ,
            'description'         => __( 'This is where payment plans are stored.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'public'              => false ,
            'show_ui'             => true ,
            'capability_type'     => 'sumo_payment_plans' ,
            'publicly_queryable'  => false ,
            'exclude_from_search' => true ,
            'show_in_menu'        => SUMO_PP_PLUGIN_TEXT_DOMAIN ,
            'show_in_admin_bar'   => false ,
            'show_in_nav_menus'   => false ,
            'rewrite'             => false ,
            'hierarchical'        => false ,
            'query_var'           => false ,
            'supports'            => array( 'title' ) ,
            'has_archive'         => false ,
            'capabilities'        => array(
                'edit_post'          => 'manage_woocommerce' ,
                'edit_posts'         => 'manage_woocommerce' ,
                'edit_others_posts'  => 'manage_woocommerce' ,
                'publish_posts'      => 'manage_woocommerce' ,
                'read_post'          => 'manage_woocommerce' ,
                'read_private_posts' => 'manage_woocommerce' ,
                'delete_post'        => 'manage_woocommerce' ,
                'delete_posts'       => true ,
            )
        ) ) ;

        //For Payment Cron Jobs
        register_post_type( 'sumo_pp_cron_jobs' , array(
            'labels'       => array(
                'name'               => _x( 'Cron Jobs' , 'general name' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'singular_name'      => _x( 'Cron Jobs' , 'singular name' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'menu_name'          => _x( 'Cron Jobs' , 'admin menu' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'name_admin_bar'     => _x( 'Cron Jobs' , 'add new on admin bar' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'add_new'            => _x( 'Add New' , 'Payment Plans' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'add_new_item'       => __( 'Add New Cron Job' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'new_item'           => __( 'New Cron Job' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'edit_item'          => __( 'Edit Cron Job' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'view_item'          => __( 'View Cron Job' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'all_items'          => __( 'Scheduled Cron Jobs' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'search_items'       => __( 'Search Cron Job' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'parent_item_colon'  => __( 'Parent Cron Jobs:' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'not_found'          => __( 'No Cron Job Found.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'not_found_in_trash' => __( 'No Cron Job found in Trash.' , SUMO_PP_PLUGIN_TEXT_DOMAIN )
            ) ,
            'description'  => __( 'This is where payment cron jobs are stored.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'public'       => false ,
            'show_ui'      => apply_filters( 'sumopaymentplans_show_cron_jobs_post_type_ui' , false ) ,
            'show_in_menu' => SUMO_PP_PLUGIN_TEXT_DOMAIN ,
            'rewrite'      => false ,
            'has_archive'  => false ,
            'supports'     => false ,
            'capabilities' => array(
                'edit_post'          => 'manage_woocommerce' ,
                'edit_posts'         => 'manage_woocommerce' ,
                'edit_others_posts'  => 'manage_woocommerce' ,
                'publish_posts'      => 'manage_woocommerce' ,
                'read_post'          => 'manage_woocommerce' ,
                'read_private_posts' => 'manage_woocommerce' ,
                'delete_post'        => 'manage_woocommerce' ,
                'delete_posts'       => true ,
                'create_posts'       => 'do_not_allow'
            ) ,
        ) ) ;

        //For Master Log.
        register_post_type( 'sumo_pp_masterlog' , array(
            'labels'       => array(
                'name'               => _x( 'Master Log' , 'general name' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'singular_name'      => _x( 'Master Log' , 'singular name' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'menu_name'          => _x( 'Master Log' , 'admin menu' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'name_admin_bar'     => _x( 'Master Log' , 'add new on admin bar' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'add_new'            => _x( 'Add New' , 'payment plans' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'add_new_item'       => __( 'Add New Log' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'new_item'           => __( 'New Log' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'edit_item'          => __( 'Edit Log' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'view_item'          => __( 'View Log' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'all_items'          => __( 'Master Log' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'search_items'       => __( 'Search Log' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'parent_item_colon'  => __( 'Parent Log:' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'not_found'          => __( 'No Logs Found.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'not_found_in_trash' => __( 'No Logs found in Trash.' , SUMO_PP_PLUGIN_TEXT_DOMAIN )
            ) ,
            'description'  => __( 'This is where payment transaction logs are stored.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'public'       => false ,
            'show_ui'      => true ,
            'show_in_menu' => SUMO_PP_PLUGIN_TEXT_DOMAIN ,
            'rewrite'      => false ,
            'has_archive'  => false ,
            'supports'     => false ,
            'capabilities' => array(
                'edit_post'          => 'manage_woocommerce' ,
                'edit_posts'         => 'manage_woocommerce' ,
                'edit_others_posts'  => 'manage_woocommerce' ,
                'publish_posts'      => 'manage_woocommerce' ,
                'read_post'          => 'manage_woocommerce' ,
                'read_private_posts' => 'manage_woocommerce' ,
                'delete_post'        => 'manage_woocommerce' ,
                'delete_posts'       => true ,
                'create_posts'       => 'do_not_allow'
            )
        ) ) ;
    }

    /**
     * Register our custom post statuses
     */
    public static function register_post_status() {
        $payment_statuses = _sumo_pp_get_payment_statuses() ;

        foreach( $payment_statuses as $payment_status => $payment_status_display_name ) {

            register_post_status( $payment_status , array(
                'label'                     => $payment_status_display_name ,
                'public'                    => true ,
                'exclude_from_search'       => false ,
                'show_in_admin_status_list' => true ,
                'show_in_admin_all_list'    => true ,
                'label_count'               => _n_noop( $payment_status_display_name . ' <span class="count">(%s)</span>' , $payment_status_display_name . ' <span class="count">(%s)</span>' ) ,
            ) ) ;
        }
    }

}

SUMO_PP_Post_Types::init() ;
