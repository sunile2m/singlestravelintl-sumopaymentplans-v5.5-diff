<?php

if( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * SUMO Payment Plans data manager.
 * 
 * @class SUMO_PP_Data_Manager
 * @category Class
 */
class SUMO_PP_Data_Manager {

    protected static $payment_data_props = array(
        'product_id'               => null ,
        'product_qty'              => null ,
        'activate_payment'         => null ,
        'down_payment'             => null ,
        'base_price'               => null ,
        'next_payment_date'        => null ,
        'next_installment_amount'  => null ,
        'total_payable_amount'     => null ,
        'remaining_payable_amount' => null ,
        'payment_product_props'    => null ,
        'payment_plan_props'       => null ,
        'item_meta'                => null ,
            ) ;

    public static function get_default_props() {
        return array_map( '__return_null' , self::$payment_data_props ) ;
    }

    public static function get_payment_data( $args ) {
        $args = wp_parse_args( $args , array(
            'product_props'    => null ,
            'plan_props'       => null ,
            'deposited_amount' => null ,
            'base_price'       => null ,
            'calc_deposit'     => false ,
            'qty'              => 1 ,
            'item_meta'        => null ,
                ) ) ;

        self::$payment_data_props[ 'payment_product_props' ] = is_array( $args[ 'product_props' ] ) ? $args[ 'product_props' ] : SUMO_PP_Product_Manager::get_product_props( $args[ 'product_props' ] ) ;

        if(
                empty( self::$payment_data_props[ 'payment_product_props' ][ 'payment_type' ] ) ||
                ! in_array( self::$payment_data_props[ 'payment_product_props' ][ 'payment_type' ] , array( 'pay-in-deposit' , 'payment-plans' ) )
        ) {
            return self::get_default_props() ;
        }

        self::$payment_data_props[ 'payment_plan_props' ] = is_array( $args[ 'plan_props' ] ) ? $args[ 'plan_props' ] : SUMO_PP_Payment_Plan_Manager::get_props( $args[ 'plan_props' ] ) ;

        if(
                'payment-plans' === self::$payment_data_props[ 'payment_product_props' ][ 'payment_type' ] &&
                empty( self::$payment_data_props[ 'payment_plan_props' ][ 'plan_id' ] )
        ) {
            return self::get_default_props() ;
        }

        if( is_numeric( $args[ 'base_price' ] ) ) {
            self::$payment_data_props[ 'payment_product_props' ][ 'product_price' ] = self::$payment_data_props[ 'base_price' ]                               = floatval( $args[ 'base_price' ] ) ;
        }

        self::$payment_data_props[ 'product_id' ]       = self::$payment_data_props[ 'payment_product_props' ][ 'product_id' ] ;
        self::$payment_data_props[ 'activate_payment' ] = get_option( SUMO_PP_PLUGIN_PREFIX . 'activate_payments' , 'auto' ) ;
        self::$payment_data_props[ 'product_qty' ]      = absint( $args[ 'qty' ] ? $args[ 'qty' ] : 1  ) ;
        self::$payment_data_props[ 'item_meta' ]        = $args[ 'item_meta' ] ;

        unset( self::$payment_data_props[ 'item_meta' ][ 'sumopaymentplans' ] ) ;

        switch( self::$payment_data_props[ 'payment_product_props' ][ 'payment_type' ] ) {
            case 'payment-plans':
                self::$payment_data_props[ 'down_payment' ]             = SUMO_PP_Payment_Plan_Manager::get_prop( 'down_payment' , array(
                            'props'         => self::$payment_data_props[ 'payment_plan_props' ] ,
                            'product_price' => self::$payment_data_props[ 'payment_product_props' ][ 'product_price' ] ,
                            'qty'           => self::$payment_data_props[ 'product_qty' ] ,
                        ) ) ;
                self::$payment_data_props[ 'next_payment_date' ]        = SUMO_PP_Payment_Plan_Manager::get_prop( 'next_payment_on' , array(
                            'props'         => self::$payment_data_props[ 'payment_plan_props' ] ,
                            'product_price' => self::$payment_data_props[ 'payment_product_props' ][ 'product_price' ] ,
                            'qty'           => self::$payment_data_props[ 'product_qty' ] ,
                        ) ) ;
                self::$payment_data_props[ 'next_installment_amount' ]  = SUMO_PP_Payment_Plan_Manager::get_prop( 'next_installment_amount' , array(
                            'props'         => self::$payment_data_props[ 'payment_plan_props' ] ,
                            'product_price' => self::$payment_data_props[ 'payment_product_props' ][ 'product_price' ] ,
                            'qty'           => self::$payment_data_props[ 'product_qty' ] ,
                        ) ) ;
                self::$payment_data_props[ 'total_payable_amount' ]     = SUMO_PP_Payment_Plan_Manager::get_prop( 'total_payable' , array(
                            'props'         => self::$payment_data_props[ 'payment_plan_props' ] ,
                            'product_price' => self::$payment_data_props[ 'payment_product_props' ][ 'product_price' ] ,
                            'qty'           => self::$payment_data_props[ 'product_qty' ] ,
                        ) ) ;
                self::$payment_data_props[ 'remaining_payable_amount' ] = SUMO_PP_Payment_Plan_Manager::get_prop( 'balance_payable' , array(
                            'props'         => self::$payment_data_props[ 'payment_plan_props' ] ,
                            'product_price' => self::$payment_data_props[ 'payment_product_props' ][ 'product_price' ] ,
                            'qty'           => self::$payment_data_props[ 'product_qty' ] ,
                        ) ) ;
                break ;
            case 'pay-in-deposit':

                if( $args[ 'calc_deposit' ] ) {
                    if( 'pre-defined' === SUMO_PP_Product_Manager::get_prop( 'deposit_type' , array(
                                'product_props' => self::$payment_data_props[ 'payment_product_props' ] ,
                            ) )
                    ) {
                        $args[ 'deposited_amount' ] = floatval( SUMO_PP_Product_Manager::get_fixed_deposit_amount( self::$payment_data_props[ 'payment_product_props' ] ) ) ;
                        $args[ 'deposited_amount' ] *= self::$payment_data_props[ 'product_qty' ] ;
                    }
                }

                self::$payment_data_props[ 'down_payment' ]             = floatval( $args[ 'deposited_amount' ] ) ;
                self::$payment_data_props[ 'next_payment_date' ]        = SUMO_PP_Product_Manager::get_prop( 'next_payment_on' , array(
                            'product_props' => self::$payment_data_props[ 'payment_product_props' ] ,
                            'qty'           => self::$payment_data_props[ 'product_qty' ] ,
                        ) ) ;
                self::$payment_data_props[ 'total_payable_amount' ]     = SUMO_PP_Product_Manager::get_prop( 'total_payable' , array(
                            'product_props' => self::$payment_data_props[ 'payment_product_props' ] ,
                            'qty'           => self::$payment_data_props[ 'product_qty' ] ,
                        ) ) ;
                self::$payment_data_props[ 'remaining_payable_amount' ] = self::$payment_data_props[ 'next_installment_amount' ]  = SUMO_PP_Product_Manager::get_prop( 'balance_payable' , array(
                            'product_props'    => self::$payment_data_props[ 'payment_product_props' ] ,
                            'qty'              => self::$payment_data_props[ 'product_qty' ] ,
                            'deposited_amount' => self::$payment_data_props[ 'down_payment' ] ,
                        ) ) ;
                break ;
        }
        return self::$payment_data_props = wp_parse_args( self::$payment_data_props , self::get_default_props() ) ;
    }

}
