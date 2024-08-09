<?php
if( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Product Admin. 
 * 
 * @class SUMO_PP_Admin_Product
 * @category Class
 */
class SUMO_PP_Admin_Product {

    protected static $payment_fields = array(
        'enable_sumopaymentplans'        => 'checkbox' ,
        'payment_type'                   => 'select' ,
        'apply_global_settings'          => 'checkbox' ,
        'force_deposit'                  => 'checkbox' ,
        'deposit_type'                   => 'select' ,
        'deposit_price_type'             => 'select' ,
        'pay_balance_type'               => 'select' ,
        'pay_balance_after'              => 'number' ,
        'pay_balance_before'             => 'datepicker' ,
        'pay_balance_before_booked_date' => 'number' ,
        'set_expired_deposit_payment_as' => 'select' ,
        'fixed_deposit_price'            => 'price' ,
        'fixed_deposit_percent'          => 'text' ,
        'user_defined_deposit_type'      => 'select' ,
        'min_user_defined_deposit_price' => 'price' ,
        'max_user_defined_deposit_price' => 'price' ,
        'min_deposit'                    => 'number' ,
        'max_deposit'                    => 'number' ,
        'selected_plans'                 => 'select' ,
            ) ;

    /**
     * Init Payment Plans Product Settings.
     */
    public static function init() {
        add_action( 'woocommerce_product_options_general_product_data' , __CLASS__ . '::get_product_settings' ) ;
        add_action( 'woocommerce_product_after_variable_attributes' , __CLASS__ . '::get_variation_product_settings' , 10 , 3 ) ;
        add_action( 'woocommerce_process_product_meta' , __CLASS__ . '::save_product_data' ) ;
        add_action( 'woocommerce_save_product_variation' , __CLASS__ . '::save_variation_data' , 10 , 2 ) ;
    }

    public static function get_payment_fields() {
        return self::$payment_fields ;
    }

    /**
     * Get payment plans product setting fields.
     */
    public static function get_product_settings() {
        global $post ;

        $product = wc_get_product( $post ) ;

        if( ! $product || in_array( $product->get_type() , array( 'variable' ) ) ) {
            return ;
        }

        woocommerce_wp_checkbox( array(
            'label'    => __( 'Enable SUMO Payment Plans' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'       => SUMO_PP_PLUGIN_PREFIX . 'enable_sumopaymentplans' ,
            'desc_tip' => __( 'Enabling this option allows you to configure the product to accept product booking by paying a deposit amount / purchase the product by choosing from the available payment plans' , SUMO_PP_PLUGIN_TEXT_DOMAIN )
        ) ) ;
        woocommerce_wp_select( array(
            'label'         => __( 'Payment Type' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'            => SUMO_PP_PLUGIN_PREFIX . 'payment_type' ,
            'wrapper_class' => SUMO_PP_PLUGIN_PREFIX . 'fields' ,
            'options'       => array(
                'pay-in-deposit' => __( 'Pay a Deposit Amount' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'payment-plans'  => __( 'Pay with Payment Plans' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            ) ,
        ) ) ;
        woocommerce_wp_checkbox( array(
            'label'         => __( 'Apply Global Level Settings' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'            => SUMO_PP_PLUGIN_PREFIX . 'apply_global_settings' ,
            'wrapper_class' => SUMO_PP_PLUGIN_PREFIX . 'fields' ,
            'desc_tip'      => __( 'When enabled, the settings for SUMO Payment Plans will apply from global level' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        ) ) ;
        woocommerce_wp_checkbox( array(
            'label'         => __( 'Force Deposit/Payment Plans' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'            => SUMO_PP_PLUGIN_PREFIX . 'force_deposit' ,
            'wrapper_class' => SUMO_PP_PLUGIN_PREFIX . 'fields' ,
            'desc_tip'      => __( 'When enabled, the user will be forced to pay a deposit amount' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        ) ) ;
        woocommerce_wp_select( array(
            'label'         => __( 'Deposit Type' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'            => SUMO_PP_PLUGIN_PREFIX . 'deposit_type' ,
            'wrapper_class' => SUMO_PP_PLUGIN_PREFIX . 'fields' ,
            'options'       => array(
                'pre-defined'  => __( 'Predefined Deposit Amount' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'user-defined' => __( 'User Defined Deposit Amount' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            ) ,
        ) ) ;
        woocommerce_wp_select( array(
            'label'         => __( 'Deposit Price Type' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'            => SUMO_PP_PLUGIN_PREFIX . 'deposit_price_type' ,
            'wrapper_class' => SUMO_PP_PLUGIN_PREFIX . 'fields' ,
            'options'       => array(
                'fixed-price'              => __( 'Fixed Price' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'percent-of-product-price' => __( 'Percentage of Product Price' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            ) ,
        ) ) ;
        woocommerce_wp_text_input( array(
            'label'         => __( 'Deposit Amount' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'            => SUMO_PP_PLUGIN_PREFIX . 'fixed_deposit_price' ,
            'wrapper_class' => SUMO_PP_PLUGIN_PREFIX . 'fields' ,
            'style'         => 'width:20%;' ,
            'data_type'     => 'price' ,
        ) ) ;
        woocommerce_wp_text_input( array(
            'label'             => __( 'Deposit Percentage' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'                => SUMO_PP_PLUGIN_PREFIX . 'fixed_deposit_percent' ,
            'wrapper_class'     => SUMO_PP_PLUGIN_PREFIX . 'fields' ,
            'style'             => 'width:20%;' ,
            'type'              => 'number' ,
            'custom_attributes' => array(
                'step' => '0.01' ,
            ) ,
        ) ) ;
        woocommerce_wp_select( array(
            'label'         => __( 'User Defined Deposit Type' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'            => SUMO_PP_PLUGIN_PREFIX . 'user_defined_deposit_type' ,
            'wrapper_class' => SUMO_PP_PLUGIN_PREFIX . 'fields' ,
            'options'       => array(
                'percent-of-product-price' => __( 'Percentage of Product Price' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'fixed-price'              => __( 'Fixed Price' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            ) ,
        ) ) ;
        woocommerce_wp_text_input( array(
            'label'         => __( 'Minimum Deposit Price' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'            => SUMO_PP_PLUGIN_PREFIX . 'min_user_defined_deposit_price' ,
            'wrapper_class' => SUMO_PP_PLUGIN_PREFIX . 'fields' ,
            'style'         => 'width:20%;' ,
            'data_type'     => 'price' ,
        ) ) ;
        woocommerce_wp_text_input( array(
            'label'         => __( 'Maximum Deposit Price' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'            => SUMO_PP_PLUGIN_PREFIX . 'max_user_defined_deposit_price' ,
            'wrapper_class' => SUMO_PP_PLUGIN_PREFIX . 'fields' ,
            'style'         => 'width:20%;' ,
            'data_type'     => 'price' ,
        ) ) ;
        woocommerce_wp_text_input( array(
            'label'             => __( 'Minimum Deposit(%)' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'                => SUMO_PP_PLUGIN_PREFIX . 'min_deposit' ,
            'wrapper_class'     => SUMO_PP_PLUGIN_PREFIX . 'fields' ,
            'style'             => 'width:20%;' ,
            'type'              => 'number' ,
            'custom_attributes' => array(
                'min'  => '0.01' ,
                'max'  => '99.99' ,
                'step' => '0.01' ,
            ) ,
        ) ) ;
        woocommerce_wp_text_input( array(
            'label'             => __( 'Maximum Deposit(%)' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'                => SUMO_PP_PLUGIN_PREFIX . 'max_deposit' ,
            'wrapper_class'     => SUMO_PP_PLUGIN_PREFIX . 'fields' ,
            'style'             => 'width:20%;' ,
            'type'              => 'number' ,
            'custom_attributes' => array(
                'min'  => '0.01' ,
                'max'  => '99.99' ,
                'step' => '0.01' ,
            ) ,
        ) ) ;
        ?>        
        <p class="aram1 form-field <?php echo SUMO_PP_PLUGIN_PREFIX . 'pay_balance_type_field' . ' ' . SUMO_PP_PLUGIN_PREFIX . 'fields' ; ?>">
            <label for="<?php echo SUMO_PP_PLUGIN_PREFIX . 'pay_balance_type' ; ?>"><?php _e( 'Deposit Balance Payment Due Date' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></label>
            <select id="<?php echo SUMO_PP_PLUGIN_PREFIX . "pay_balance_type" ; ?>" name="<?php echo SUMO_PP_PLUGIN_PREFIX . "pay_balance_type" ; ?>">
                <option value="after" <?php selected( true , 'after' === get_post_meta( $post->ID , SUMO_PP_PLUGIN_PREFIX . 'pay_balance_type' , true ) ) ?>><?php _e( 'After' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></option> 
                <option value="before" <?php selected( true , 'before' === get_post_meta( $post->ID , SUMO_PP_PLUGIN_PREFIX . 'pay_balance_type' , true ) ) ?>><?php _e( 'Before' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></option>
            </select>
            <span>
                <input type="number" id="<?php echo SUMO_PP_PLUGIN_PREFIX . "pay_balance_after" ; ?>" name="<?php echo SUMO_PP_PLUGIN_PREFIX . "pay_balance_after" ; ?>" value="<?php echo '' === get_post_meta( $post->ID , SUMO_PP_PLUGIN_PREFIX . 'balance_payment_due' , true ) ? get_post_meta( $post->ID , SUMO_PP_PLUGIN_PREFIX . 'pay_balance_after' , true ) : get_post_meta( $post->ID , SUMO_PP_PLUGIN_PREFIX . 'balance_payment_due' , true ) ; ?>" style="width:20%;">
                <span class="description"><?php _e( 'day(s) from the date of deposit payment' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></span>
            </span>
            <span>
                <input type="text" NOplaceholder="<?php esc_attr_e( 'YYYY-MM-DD' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?>" id="<?php echo SUMO_PP_PLUGIN_PREFIX . "pay_balance_before" ; ?>" name="<?php echo SUMO_PP_PLUGIN_PREFIX . "pay_balance_before" ; ?>" value="<?php echo get_post_meta( $post->ID , SUMO_PP_PLUGIN_PREFIX . 'pay_balance_before' , true ) ; ?>" value="ARAM" style="width:20%;">
            </span>
            <?php if( class_exists( 'SUMO_Bookings' ) ) { ?>
                <span>
                    <input type="number" min="0" id="<?php echo SUMO_PP_PLUGIN_PREFIX . "pay_balance_before_booked_date" ; ?>" name="<?php echo SUMO_PP_PLUGIN_PREFIX . "pay_balance_before_booked_date" ; ?>" value="<?php echo get_post_meta( $post->ID , SUMO_PP_PLUGIN_PREFIX . 'pay_balance_before_booked_date' , true ) ; ?>" style="width:20%;display: none;">
                    <span class="description"><?php _e( 'day(s) of booking start date' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></span>
                </span>
            <?php } ?>
        </p>
        <p class="form-field <?php echo SUMO_PP_PLUGIN_PREFIX . 'set_expired_deposit_payment_as_field' . ' ' . SUMO_PP_PLUGIN_PREFIX . 'fields' ; ?>">
            <label for="<?php echo SUMO_PP_PLUGIN_PREFIX . 'set_expired_deposit_payment_as' ; ?>"><?php _e( 'After Balance Payment Due Date' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></label>
            <select id="<?php echo SUMO_PP_PLUGIN_PREFIX . "set_expired_deposit_payment_as" ; ?>" name="<?php echo SUMO_PP_PLUGIN_PREFIX . "set_expired_deposit_payment_as" ; ?>">
                <option value="normal" <?php selected( true , 'normal' === get_post_meta( $post->ID , SUMO_PP_PLUGIN_PREFIX . 'set_expired_deposit_payment_as' , true ) ) ?>><?php _e( 'Disable SUMO Payment Plans' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></option> 
                <option value="out-of-stock" <?php selected( true , 'out-of-stock' === get_post_meta( $post->ID , SUMO_PP_PLUGIN_PREFIX . 'set_expired_deposit_payment_as' , true ) ) ?>><?php _e( 'Set Product as Out of Stock' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></option>
            </select>
        </p>
        <p class="form-field <?php echo SUMO_PP_PLUGIN_PREFIX . 'selected_plans_field' . ' ' . SUMO_PP_PLUGIN_PREFIX . 'fields' ; ?>">
            <label for="<?php echo SUMO_PP_PLUGIN_PREFIX . 'selected_plans' ; ?>"><?php _e( 'Select Plans' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></label>
            <span class="<?php echo SUMO_PP_PLUGIN_PREFIX . 'add_plans' ; ?>">
                <span class="woocommerce-help-tip" data-tip="<?php _e( 'Select the layout as per your theme preference' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?>"></span>
                <a href="#" class="button" id="<?php echo SUMO_PP_PLUGIN_PREFIX . 'add_col_1_plan' ; ?>"><?php _e( 'Add Row for Column 1' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></a>
                <a href="#" class="button" id="<?php echo SUMO_PP_PLUGIN_PREFIX . 'add_col_2_plan' ; ?>"><?php _e( 'Add Row for Column 2' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></a>
                <span class="spinner"></span>
            </span>
        </p>
        <style>
            table._sumo_pp_footable{
                margin-bottom:10px !important;
            }
            table._sumo_pp_footable td, table._sumo_pp_footable td:last-child{
                border-top:none !important;
                border-left:none !important;
                border-right:none !important;
                border-bottom:1px solid #ccc !important;
            }
            table._sumo_pp_footable td select, table._sumo_pp_footable td span{
                width:100% !important;
            }
            table._sumo_pp_footable td span{
                padding-right:0px !important;
            }
            table._sumo_pp_footable td .select2-container--default  .select2-selection--single .select2-selection__arrow{
                width:auto !important;
                right:8px;
            }
            table._sumo_pp_footable td .select2-container--default  .select2-selection--single .select2-selection__arrow b{
                width:auto !important;
                text-align:right;
            }
            table._sumo_pp_footable td .select2-selection__clear{
                width:auto !important;
                margin-right:20%;

            }
            table._sumo_pp_footable td a{
                float:right !important;

            }
        </style>
        <?php
        $selected_plans     = get_post_meta( $post->ID , SUMO_PP_PLUGIN_PREFIX . 'selected_plans' , true ) ;
        $selected_plans     = $bkw_selected_plans = is_array( $selected_plans ) && ! empty( $selected_plans ) ? $selected_plans : array( 'col_1' => array() , 'col_2' => array() ) ;

        if( ! isset( $bkw_selected_plans[ 'col_1' ] ) ) {
            $selected_plans = array( 'col_1' => array() , 'col_2' => array() ) ;

            foreach( $bkw_selected_plans as $row_id => $selected_plan ) {
                $selected_plans[ 'col_1' ][] = ! empty( $selected_plan ) ? ( array ) $selected_plan : array() ;
            }
        }

        foreach( $selected_plans as $column_id => $selected_datas ) {
            $inline_style = 'col_1' === $column_id ? 'float:left;margin-left:3px;' : 'float:right;margin-right:3px;' ;
            $inline_style.='width:49%;clear:none;' ;
            ?>
            <table class="widefat wc_input_table wc_gateways sortable <?php echo SUMO_PP_PLUGIN_PREFIX . 'footable ' . SUMO_PP_PLUGIN_PREFIX . "selected_col_{$column_id}_plans " . SUMO_PP_PLUGIN_PREFIX . 'selected_plans ' . SUMO_PP_PLUGIN_PREFIX . 'fields' ; ?>" style="<?php echo $inline_style ; ?>">
                <tbody class="selected_plans">
                    <?php
                    if( is_array( $selected_datas ) && ! empty( $selected_datas ) ) {
                        foreach( $selected_datas as $row_id => $selected_data ) {
                            $selected_plan_field = '<tr><td class="sort" width="1%"></td><td>' ;
                            $selected_plan_field .= _sumo_pp_wc_search_field( array(
                                'class'       => 'wc-product-search' ,
                                'action'      => '_sumo_pp_json_search_payment_plans' ,
                                'id'          => "selected_{$column_id}_payment_plan_{$row_id}" ,
                                'name'        => "_sumo_pp_selected_plans[{$column_id}][{$row_id}]" ,
                                'type'        => 'payment_plans' ,
                                'multiple'    => false ,
                                'options'     => ( array ) $selected_data ,
                                'placeholder' => __( 'Search for a payment plan&hellip;' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                                    ) , false ) ;
                            $selected_plan_field .= '</td><td>' ;
                            $selected_plan_field .= '<a href="#" class="remove_row button">X</a>' ;
                            $selected_plan_field .= '</td></tr>' ;
                            echo $selected_plan_field ;
                        }
                    }
                    ?>
                </tbody>
            </table>
            <?php
        }
    }

    /**
     * Get payment plans variation product setting fields.
     * @param int $loop
     * @param mixed $variation_data
     * @param object $variation The Variation post ID
     */
    public static function get_variation_product_settings( $loop , $variation_data , $variation ) {

        woocommerce_wp_checkbox( array(
            'label'    => __( 'Enable SUMO Payment Plans' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'       => SUMO_PP_PLUGIN_PREFIX . "enable_sumopaymentplans{$loop}" ,
            'name'     => SUMO_PP_PLUGIN_PREFIX . "enable_sumopaymentplans[{$loop}]" ,
            'value'    => get_post_meta( $variation->ID , SUMO_PP_PLUGIN_PREFIX . 'enable_sumopaymentplans' , true ) ,
            'desc_tip' => __( 'Enabling this option allows you to configure the product to accept product booking by paying a deposit amount / purchase the product by choosing from the available payment plans' , SUMO_PP_PLUGIN_TEXT_DOMAIN )
        ) ) ;
        woocommerce_wp_select( array(
            'label'         => __( 'Payment Type' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'            => SUMO_PP_PLUGIN_PREFIX . "payment_type{$loop}" ,
            'name'          => SUMO_PP_PLUGIN_PREFIX . "payment_type[{$loop}]" ,
            'wrapper_class' => SUMO_PP_PLUGIN_PREFIX . "fields{$loop}" ,
            'options'       => array(
                'pay-in-deposit' => __( 'Pay a Deposit Amount' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'payment-plans'  => __( 'Pay with Payment Plans' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            ) ,
            'value'         => get_post_meta( $variation->ID , SUMO_PP_PLUGIN_PREFIX . 'payment_type' , true ) ,
        ) ) ;
        woocommerce_wp_checkbox( array(
            'label'         => __( 'Apply Global Level Settings' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'            => SUMO_PP_PLUGIN_PREFIX . "apply_global_settings{$loop}" ,
            'name'          => SUMO_PP_PLUGIN_PREFIX . "apply_global_settings[{$loop}]" ,
            'wrapper_class' => SUMO_PP_PLUGIN_PREFIX . "fields{$loop}" ,
            'value'         => get_post_meta( $variation->ID , SUMO_PP_PLUGIN_PREFIX . 'apply_global_settings' , true ) ,
            'desc_tip'      => __( 'When enabled, the settings for SUMO Payment Plans will apply from global level' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        ) ) ;
        woocommerce_wp_checkbox( array(
            'label'         => __( 'Force Deposit/Payment Plans' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'            => SUMO_PP_PLUGIN_PREFIX . "force_deposit{$loop}" ,
            'name'          => SUMO_PP_PLUGIN_PREFIX . "force_deposit[{$loop}]" ,
            'wrapper_class' => SUMO_PP_PLUGIN_PREFIX . "fields{$loop}" ,
            'value'         => get_post_meta( $variation->ID , SUMO_PP_PLUGIN_PREFIX . 'force_deposit' , true ) ,
            'desc_tip'      => __( 'When enabled, the user will be forced to pay a deposit amount' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
        ) ) ;
        woocommerce_wp_select( array(
            'label'         => __( 'Deposit Type' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'            => SUMO_PP_PLUGIN_PREFIX . "deposit_type{$loop}" ,
            'name'          => SUMO_PP_PLUGIN_PREFIX . "deposit_type[{$loop}]" ,
            'wrapper_class' => SUMO_PP_PLUGIN_PREFIX . "fields{$loop}" ,
            'options'       => array(
                'pre-defined'  => __( 'Predefined Deposit Amount' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'user-defined' => __( 'User Defined Deposit Amount' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            ) ,
            'value'         => get_post_meta( $variation->ID , SUMO_PP_PLUGIN_PREFIX . 'deposit_type' , true ) ,
        ) ) ;
        woocommerce_wp_select( array(
            'label'         => __( 'Deposit Price Type' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'            => SUMO_PP_PLUGIN_PREFIX . "deposit_price_type{$loop}" ,
            'name'          => SUMO_PP_PLUGIN_PREFIX . "deposit_price_type[{$loop}]" ,
            'wrapper_class' => SUMO_PP_PLUGIN_PREFIX . "fields{$loop}" ,
            'options'       => array(
                'fixed-price'              => __( 'Fixed Price' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'percent-of-product-price' => __( 'Percentage of Product Price' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            ) ,
            'value'         => get_post_meta( $variation->ID , SUMO_PP_PLUGIN_PREFIX . 'deposit_price_type' , true ) ,
        ) ) ;
        woocommerce_wp_text_input( array(
            'label'         => __( 'Deposit Amount' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'            => SUMO_PP_PLUGIN_PREFIX . "fixed_deposit_price{$loop}" ,
            'name'          => SUMO_PP_PLUGIN_PREFIX . "fixed_deposit_price[{$loop}]" ,
            'wrapper_class' => SUMO_PP_PLUGIN_PREFIX . "fields{$loop}" ,
            'style'         => 'width:20%;' ,
            'data_type'     => 'price' ,
            'value'         => get_post_meta( $variation->ID , SUMO_PP_PLUGIN_PREFIX . 'fixed_deposit_price' , true ) ,
        ) ) ;
        woocommerce_wp_text_input( array(
            'label'             => __( 'Deposit Percentage' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'                => SUMO_PP_PLUGIN_PREFIX . "fixed_deposit_percent{$loop}" ,
            'name'              => SUMO_PP_PLUGIN_PREFIX . "fixed_deposit_percent[{$loop}]" ,
            'wrapper_class'     => SUMO_PP_PLUGIN_PREFIX . "fields{$loop}" ,
            'style'             => 'width:20%;' ,
            'type'              => 'number' ,
            'custom_attributes' => array(
                'step' => '0.01' ,
            ) ,
            'value'             => get_post_meta( $variation->ID , SUMO_PP_PLUGIN_PREFIX . 'fixed_deposit_percent' , true ) ,
        ) ) ;
        woocommerce_wp_select( array(
            'label'         => __( 'User Defined Deposit Type' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'            => SUMO_PP_PLUGIN_PREFIX . "user_defined_deposit_type{$loop}" ,
            'name'          => SUMO_PP_PLUGIN_PREFIX . "user_defined_deposit_type[{$loop}]" ,
            'wrapper_class' => SUMO_PP_PLUGIN_PREFIX . "fields{$loop}" ,
            'options'       => array(
                'fixed-price'              => __( 'Fixed Price' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                'percent-of-product-price' => __( 'Percentage of Product Price' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            ) ,
            'value'         => get_post_meta( $variation->ID , SUMO_PP_PLUGIN_PREFIX . 'user_defined_deposit_type' , true ) ,
        ) ) ;
        woocommerce_wp_text_input( array(
            'label'         => __( 'Minimum Deposit Price' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'            => SUMO_PP_PLUGIN_PREFIX . "min_user_defined_deposit_price{$loop}" ,
            'name'          => SUMO_PP_PLUGIN_PREFIX . "min_user_defined_deposit_price[{$loop}]" ,
            'wrapper_class' => SUMO_PP_PLUGIN_PREFIX . "fields{$loop}" ,
            'style'         => 'width:20%;' ,
            'data_type'     => 'price' ,
            'value'         => get_post_meta( $variation->ID , SUMO_PP_PLUGIN_PREFIX . 'min_user_defined_deposit_price' , true ) ,
        ) ) ;
        woocommerce_wp_text_input( array(
            'label'         => __( 'Maximum Deposit Price' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'            => SUMO_PP_PLUGIN_PREFIX . "max_user_defined_deposit_price{$loop}" ,
            'name'          => SUMO_PP_PLUGIN_PREFIX . "max_user_defined_deposit_price[{$loop}]" ,
            'wrapper_class' => SUMO_PP_PLUGIN_PREFIX . "fields{$loop}" ,
            'style'         => 'width:20%;' ,
            'data_type'     => 'price' ,
            'value'         => get_post_meta( $variation->ID , SUMO_PP_PLUGIN_PREFIX . 'max_user_defined_deposit_price' , true ) ,
        ) ) ;
        woocommerce_wp_text_input( array(
            'label'             => __( 'Minimum Deposit(%)' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'                => SUMO_PP_PLUGIN_PREFIX . "min_deposit{$loop}" ,
            'name'              => SUMO_PP_PLUGIN_PREFIX . "min_deposit[{$loop}]" ,
            'wrapper_class'     => SUMO_PP_PLUGIN_PREFIX . "fields{$loop}" ,
            'style'             => 'width:20%;' ,
            'type'              => 'number' ,
            'custom_attributes' => array(
                'min'  => '0.01' ,
                'max'  => '99.99' ,
                'step' => '0.01' ,
            ) ,
            'value'             => get_post_meta( $variation->ID , SUMO_PP_PLUGIN_PREFIX . 'min_deposit' , true ) ,
        ) ) ;
        woocommerce_wp_text_input( array(
            'label'             => __( 'Maximum Deposit(%)' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
            'id'                => SUMO_PP_PLUGIN_PREFIX . "max_deposit{$loop}" ,
            'name'              => SUMO_PP_PLUGIN_PREFIX . "max_deposit[{$loop}]" ,
            'wrapper_class'     => SUMO_PP_PLUGIN_PREFIX . "fields{$loop}" ,
            'style'             => 'width:20%;' ,
            'type'              => 'number' ,
            'custom_attributes' => array(
                'min'  => '0.01' ,
                'max'  => '99.99' ,
                'step' => '0.01' ,
            ) ,
            'value'             => get_post_meta( $variation->ID , SUMO_PP_PLUGIN_PREFIX . 'max_deposit' , true ) ,
        ) ) ;
        ?>       
        <p class="aram2 form-field <?php echo SUMO_PP_PLUGIN_PREFIX . 'pay_balance_type_field' . ' ' . SUMO_PP_PLUGIN_PREFIX . "fields{$loop}" ; ?>">
            <label for="<?php echo SUMO_PP_PLUGIN_PREFIX . 'pay_balance_type' ; ?>"><?php _e( 'Deposit Balance Payment Due Date' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></label>
            <select id="<?php echo SUMO_PP_PLUGIN_PREFIX . "pay_balance_type{$loop}" ; ?>" name="<?php echo SUMO_PP_PLUGIN_PREFIX . "pay_balance_type[{$loop}]" ; ?>">
                
                <option value="before" <?php selected( true , 'before' === get_post_meta( $variation->ID , SUMO_PP_PLUGIN_PREFIX . 'pay_balance_type' , true ) ) ?>><?php _e( 'Before' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></option>

                <!-- <option value="after" <?php selected( true , 'after' === get_post_meta( $variation->ID , SUMO_PP_PLUGIN_PREFIX . 'pay_balance_type' , true ) ) ?>><?php _e( 'After' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></option> -->
                
            </select>
            
            <!--
            <span style="display:none !important;">
                <input type="number" id="<?php echo SUMO_PP_PLUGIN_PREFIX . "pay_balance_after{$loop}" ; ?>" name="<?php echo SUMO_PP_PLUGIN_PREFIX . "pay_balance_after[{$loop}]" ; ?>" value="<?php echo '' === get_post_meta( $variation->ID , SUMO_PP_PLUGIN_PREFIX . 'balance_payment_due' , true ) ? get_post_meta( $variation->ID , SUMO_PP_PLUGIN_PREFIX . 'pay_balance_after' , true ) : get_post_meta( $variation->ID , SUMO_PP_PLUGIN_PREFIX . 'balance_payment_due' , true ) ; ?>" style="width:20%;">
                <span class="description"><?php _e( 'day(s) from the date of deposit payment' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></span>
            </span>
            -->


<?php
// calculate the duedate

global $post ;

$product = wc_get_product( $post ) ;
$productid = $product->get_id();

$tripdate = get_field("start_date", $productid);
$paydate = strtotime($tripdate . ' -128 days');

$paydate = date("Y-m-d", $paydate);

?>

            <span style="display:inline !important;">
                <input type="text" NOplaceholder="<?php esc_attr_e( 'YYYY-MM-DD' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?>" id="<?php echo SUMO_PP_PLUGIN_PREFIX . "pay_balance_before{$loop}" ; ?>" name="<?php echo SUMO_PP_PLUGIN_PREFIX . "pay_balance_before[{$loop}]" ; ?>" 

                <?php
                $thepaydate = get_post_meta( $variation->ID , SUMO_PP_PLUGIN_PREFIX . 'pay_balance_before' , true );
                if($thepaydate == "") {
                    $thepaydate = $paydate;
                }
                ?>

                value="<?php echo $thepaydate; ?>" style="width:20%;">

            </span>
        </p>
        <p class="form-field <?php echo SUMO_PP_PLUGIN_PREFIX . 'set_expired_deposit_payment_as_field' . ' ' . SUMO_PP_PLUGIN_PREFIX . "fields{$loop}" ; ?>">
            <label for="<?php echo SUMO_PP_PLUGIN_PREFIX . 'set_expired_deposit_payment_as' ; ?>"><?php _e( 'After Balance Payment Due Date' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></label>
            <select id="<?php echo SUMO_PP_PLUGIN_PREFIX . "set_expired_deposit_payment_as{$loop}" ; ?>" name="<?php echo SUMO_PP_PLUGIN_PREFIX . "set_expired_deposit_payment_as[{$loop}]" ; ?>">
                <option value="normal" <?php selected( true , 'normal' === get_post_meta( $variation->ID , SUMO_PP_PLUGIN_PREFIX . 'set_expired_deposit_payment_as' , true ) ) ?>><?php _e( 'Disable SUMO Payment Plans' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></option> 
                <option value="out-of-stock" <?php selected( true , 'out-of-stock' === get_post_meta( $variation->ID , SUMO_PP_PLUGIN_PREFIX . 'set_expired_deposit_payment_as' , true ) ) ?>><?php _e( 'Set Product as Out of Stock' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?></option>
            </select>
        </p>
        <p class="form-field <?php echo SUMO_PP_PLUGIN_PREFIX . 'selected_plans_field' . ' ' . SUMO_PP_PLUGIN_PREFIX . "fields{$loop}" ; ?>">
            <label for="<?php echo SUMO_PP_PLUGIN_PREFIX . 'selected_plans' ; ?>"><?php _e( 'Select Plans' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></label>
            <span class="<?php echo SUMO_PP_PLUGIN_PREFIX . "add_plans{$loop}" ; ?>">
                <span class="woocommerce-help-tip" data-tip="<?php _e( 'Select the layout as per your theme preference' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?>"></span>
                <a href="#" class="button" id="<?php echo SUMO_PP_PLUGIN_PREFIX . "add_col_1_plan{$loop}" ; ?>"><?php _e( 'Add Row for Column 1' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></a>
                <a href="#" class="button" id="<?php echo SUMO_PP_PLUGIN_PREFIX . "add_col_2_plan{$loop}" ; ?>"><?php _e( 'Add Row for Column 2' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></a>
                <span class="spinner"></span>
            </span>
        </p>
        <style>
            table._sumo_pp_footable{
                margin-bottom:10px !important;
            }
            table._sumo_pp_footable td, table._sumo_pp_footable td:last-child{
                border-top:none !important;
                border-left:none !important;
                border-right:none !important;
                border-bottom:1px solid #ccc !important;
            }
            table._sumo_pp_footable td select, table._sumo_pp_footable td span{
                width:100% !important;
            }
            table._sumo_pp_footable td span{
                padding-right:0px !important;
            }
            table._sumo_pp_footable td .select2-container--default  .select2-selection--single .select2-selection__arrow{
                width:auto !important;
                right:8px;
            }
            table._sumo_pp_footable td .select2-container--default  .select2-selection--single .select2-selection__arrow b{
                width:auto !important;
                text-align:right;
            }
            table._sumo_pp_footable td .select2-selection__clear{
                width:auto !important;
                margin-right:20%;

            }
            table._sumo_pp_footable td a{
                float:right !important;

            }
        </style>
        <?php
        $selected_plans     = get_post_meta( $variation->ID , SUMO_PP_PLUGIN_PREFIX . 'selected_plans' , true ) ;
        $selected_plans     = $bkw_selected_plans = is_array( $selected_plans ) && ! empty( $selected_plans ) ? $selected_plans : array( 'col_1' => array() , 'col_2' => array() ) ;

        if( ! isset( $bkw_selected_plans[ 'col_1' ] ) ) {
            $selected_plans = array( 'col_1' => array() , 'col_2' => array() ) ;

            foreach( $bkw_selected_plans as $row_id => $selected_plan ) {
                $selected_plans[ 'col_1' ][] = ! empty( $selected_plan ) ? ( array ) $selected_plan : array() ;
            }
        }

        foreach( $selected_plans as $column_id => $selected_datas ) {
            $inline_style = 'col_1' === $column_id ? 'float:left;margin-left:3px;' : 'float:right;margin-right:3px;' ;
            $inline_style.='width:49%;clear:none;padding:0px;' ;
            ?>
            <table class="widefat wc_input_table wc_gateways sortable <?php echo SUMO_PP_PLUGIN_PREFIX . "footable " . SUMO_PP_PLUGIN_PREFIX . "selected_col_{$column_id}_plans{$loop} " . SUMO_PP_PLUGIN_PREFIX . "selected_plans{$loop} " . SUMO_PP_PLUGIN_PREFIX . "fields{$loop}" ; ?>" style="<?php echo $inline_style ; ?>">
                <tbody class="selected_plans">
                    <?php
                    if( is_array( $selected_datas ) && ! empty( $selected_datas ) ) {
                        foreach( $selected_datas as $row_id => $selected_data ) {
                            $selected_plan_field = '<tr><td class="sort" width="1%"></td><td>' ;
                            $selected_plan_field .= _sumo_pp_wc_search_field( array(
                                'class'       => 'wc-product-search' ,
                                'action'      => '_sumo_pp_json_search_payment_plans' ,
                                'id'          => "selected_{$column_id}_payment_plan_{$row_id}{$loop}" ,
                                'name'        => "_sumo_pp_selected_plans[{$loop}][{$column_id}][{$row_id}]" ,
                                'type'        => 'payment_plans' ,
                                'multiple'    => false ,
                                'options'     => ( array ) $selected_data ,
                                'placeholder' => __( 'Search for a payment plan&hellip;' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                                    ) , false ) ;
                            $selected_plan_field .= '</td><td>' ;
                            $selected_plan_field .= '<a href="#" class="remove_row button">X</a>' ;
                            $selected_plan_field .= '</td></tr>' ;
                            echo $selected_plan_field ;
                        }
                    }
                    ?>
                </tbody>
            </table>
            <?php
        }
    }

    /**
     * Save payment plans product data.
     * @param int $product_id The Product post ID
     */
    public static function save_product_data( $product_id ) {
        self::save_meta( $product_id ) ;
    }

    /**
     * Save payment plans variation product data.
     * @param int $variation_id The Variation post ID
     * @param int $loop
     */
    public static function save_variation_data( $variation_id , $loop ) {
        self::save_meta( $variation_id , $loop ) ;
    }

    /**
     * Save payment plans product meta's.
     * @param int $product_id The Product post ID
     * @param int $loop The Variation loop
     */
    public static function save_meta( $product_id , $loop = '' , $props = array() ) {
        $props = ! empty( $props ) ? $props : $_POST ;

        foreach( self::$payment_fields as $field_name => $type ) {
            $meta_key         = SUMO_PP_PLUGIN_PREFIX . $field_name ;
            $posted_meta_data = isset( $props[ "$meta_key" ] ) ? $props[ "$meta_key" ] : '' ;

            if( is_numeric( $loop ) ) {
                if( 'checkbox' === $type ) {
                    delete_post_meta( $product_id , "$meta_key" ) ;
                }
                if( isset( $posted_meta_data[ $loop ] ) ) {
                    if( 'price' === $type ) {
                        $posted_meta_data[ $loop ] = wc_format_decimal( $posted_meta_data[ $loop ] ) ;
                    }
                    if( 'selected_plans' === $field_name && is_array( $posted_meta_data[ $loop ] ) ) {
                        foreach( array( 'col_1' , 'col_2' ) as $column_id ) {
                            $posted_meta_data[ $loop ][ $column_id ] = ! empty( $posted_meta_data[ $loop ][ $column_id ] ) && is_array( $posted_meta_data[ $loop ][ $column_id ] ) ? array_map( 'implode' , (array_values( $posted_meta_data[ $loop ][ $column_id ] ) ) ) : array() ;
                        }
                    }

                    update_post_meta( $product_id , "$meta_key" , wc_clean( $posted_meta_data[ $loop ] ) ) ;

                    //backward compatible
                    if( $posted_meta_data[ $loop ] && 'pay_balance_after' === $field_name ) {
                        delete_post_meta( $product_id , SUMO_PP_PLUGIN_PREFIX . 'balance_payment_due' ) ;
                    }
                }
            } else {
                if( 'price' === $type ) {
                    $posted_meta_data = wc_format_decimal( $posted_meta_data ) ;
                }
                if( 'selected_plans' === $field_name && is_array( $posted_meta_data ) ) {
                    foreach( array( 'col_1' , 'col_2' ) as $column_id ) {
                        if( ! empty( $posted_meta_data[ $column_id ] ) && is_array( $posted_meta_data[ $column_id ] ) ) {
                            $plans = array_values( $posted_meta_data[ $column_id ] ) ;

                            if( ! empty( $plans[ 0 ] ) ) {
                                if( is_array( $plans[ 0 ] ) ) {
                                    $posted_meta_data[ $column_id ] = array_map( 'implode' , $plans ) ;
                                } else {
                                    $posted_meta_data[ $column_id ] = $plans ;
                                }
                            }
                        }
                    }
                }
                update_post_meta( $product_id , "$meta_key" , wc_clean( $posted_meta_data ) ) ;

                //backward compatible
                if( $posted_meta_data && 'pay_balance_after' === $field_name ) {
                    delete_post_meta( $product_id , SUMO_PP_PLUGIN_PREFIX . 'balance_payment_due' ) ;
                }
            }
        }
    }

}

SUMO_PP_Admin_Product::init() ;
