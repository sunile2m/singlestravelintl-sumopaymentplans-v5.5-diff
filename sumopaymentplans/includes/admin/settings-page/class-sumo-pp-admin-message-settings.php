<?php

/**
 * Message Tab.
 * 
 * @class SUMO_PP_Messages_Settings
 * @category Class
 */
class SUMO_PP_Messages_Settings extends SUMO_PP_Abstract_Settings {

    /**
     * SUMO_PP_Messages_Settings constructor.
     */
    public function __construct() {

        $this->id            = 'messages' ;
        $this->label         = __( 'Messages' , $this->text_domain ) ;
        $this->custom_fields = array(
            'get_shortcodes_and_its_usage' ,
                ) ;
        $this->settings      = $this->get_settings() ;
        $this->init() ;
    }

    /**
     * Get settings array.
     * @return array
     */
    public function get_settings() {
        global $current_section ;

        return apply_filters( 'sumopaymentplans_get_' . $this->id . '_settings' , array(
            array( 'type' => $this->get_custom_field_type( 'get_shortcodes_and_its_usage' ) ) ,
            array(
                'name' => __( 'Shop Page Message Settings' , $this->text_domain ) ,
                'type' => 'title' ,
                'id'   => $this->prefix . 'shop_message_settings'
            ) ,
            array(
                'name'    => __( 'Add to Cart Label' , $this->text_domain ) ,
                'id'      => $this->prefix . 'add_to_cart_label' ,
                'newids'  => $this->prefix . 'add_to_cart_label' ,
                'type'    => 'text' ,
                'std'     => __( 'View More' , $this->text_domain ) ,
                'default' => __( 'View More' , $this->text_domain ) ,
            ) ,
            array( 'type' => 'sectionend' , 'id' => $this->prefix . 'shop_message_settings' ) ,
            array(
                'name' => __( 'Single Product Page Message Settings' , $this->text_domain ) ,
                'type' => 'title' ,
                'id'   => $this->prefix . 'single_product_message_settings'
            ) ,
            array(
                'name'    => __( 'Pay in Full' , $this->text_domain ) ,
                'id'      => $this->prefix . 'pay_in_full_label' ,
                'newids'  => $this->prefix . 'pay_in_full_label' ,
                'type'    => 'text' ,
                'std'     => __( 'Pay in Full' , $this->text_domain ) ,
                'default' => __( 'Pay in Full' , $this->text_domain ) ,
            ) ,
            array(
                'name'    => __( 'Pay a Deposit Amount' , $this->text_domain ) ,
                'id'      => $this->prefix . 'pay_a_deposit_amount_label' ,
                'newids'  => $this->prefix . 'pay_a_deposit_amount_label' ,
                'type'    => 'text' ,
                'std'     => __( 'Pay a Deposit Amount' , $this->text_domain ) ,
                'default' => __( 'Pay a Deposit Amount' , $this->text_domain ) ,
            ) ,
            array(
                'name'    => __( 'Pay with Payment Plans' , $this->text_domain ) ,
                'id'      => $this->prefix . 'pay_with_payment_plans_label' ,
                'newids'  => $this->prefix . 'pay_with_payment_plans_label' ,
                'type'    => 'text' ,
                'std'     => __( 'Pay with Payment Plans' , $this->text_domain ) ,
                'default' => __( 'Pay with Payment Plans' , $this->text_domain ) ,
            ) ,
            array( 'type' => 'sectionend' , 'id' => $this->prefix . 'single_product_message_settings' ) ,
            array(
                'name' => __( 'Cart And Checkout Page Message Settings' , $this->text_domain ) ,
                'type' => 'title' ,
                'id'   => $this->prefix . 'cart_message_settings'
            ) ,
            array(
                'name'    => __( 'Payment Plan' , $this->text_domain ) ,
                'id'      => $this->prefix . 'payment_plan_label' ,
                'newids'  => $this->prefix . 'payment_plan_label' ,
                'type'    => 'textarea' ,
                'std'     => __( '<p><strong>Payment Plan:</strong> <br>[sumo_pp_payment_plan_name]</p>' , $this->text_domain ) ,
                'default' => __( '<p><strong>Payment Plan:</strong> <br>[sumo_pp_payment_plan_name]</p>' , $this->text_domain ) ,
            ) ,
            array(
                'name'    => __( 'Payment Plan Description' , $this->text_domain ) ,
                'id'      => $this->prefix . 'payment_plan_desc_label' ,
                'newids'  => $this->prefix . 'payment_plan_desc_label' ,
                'type'    => 'textarea' ,
                'std'     => __( '<small style="color:#777;">[sumo_pp_payment_plan_desc]</small>' , $this->text_domain ) ,
                'default' => __( '<small style="color:#777;">[sumo_pp_payment_plan_desc]</small>' , $this->text_domain ) ,
            ) ,
            array(
                'name'    => __( 'Total Payable' , $this->text_domain ) ,
                'id'      => $this->prefix . 'total_payable_label' ,
                'newids'  => $this->prefix . 'total_payable_label' ,
                'type'    => 'textarea' ,
                'std'     => __( '<br><small style="color:#777;">Total <strong>[sumo_pp_total_payable]</strong> payable</small>' , $this->text_domain ) ,
                'default' => __( '<br><small style="color:#777;">Total <strong>[sumo_pp_total_payable]</strong> payable</small>' , $this->text_domain ) ,
            ) ,
            array(
                'name'    => __( 'Next Installment Amount' , $this->text_domain ) ,
                'id'      => $this->prefix . 'next_installment_amount_label' ,
                'newids'  => $this->prefix . 'next_installment_amount_label' ,
                'type'    => 'textarea' ,
                'std'     => __( '<br><small style="color:#777;">Next Installment Amount: <strong>[sumo_pp_next_installment_amount]</strong></small>' , $this->text_domain ) ,
                'default' => __( '<br><small style="color:#777;">Next Installment Amount: <strong>[sumo_pp_next_installment_amount]</strong></small>' , $this->text_domain ) ,
            ) ,
            array(
                'name'    => __( 'Next Payment Date' , $this->text_domain ) ,
                'id'      => $this->prefix . 'next_payment_date_label' ,
                'newids'  => $this->prefix . 'next_payment_date_label' ,
                'type'    => 'textarea' ,
                'std'     => __( '<br><small style="color:#777;">Next Payment Date: <strong>[sumo_pp_next_payment_date]</strong></small>' , $this->text_domain ) ,
                'default' => __( '<br><small style="color:#777;">Next Payment Date: <strong>[sumo_pp_next_payment_date]</strong></small>' , $this->text_domain ) ,
            ) ,
            array(
                'name'    => __( 'First Payment On' , $this->text_domain ) ,
                'id'      => $this->prefix . 'first_payment_on_label' ,
                'newids'  => $this->prefix . 'first_payment_on_label' ,
                'type'    => 'textarea' ,
                'std'     => __( '<br><small style="color:#777;">First Payment On: <strong>[sumo_pp_next_payment_date]</strong></small>' , $this->text_domain ) ,
                'default' => __( '<br><small style="color:#777;">First Payment On: <strong>[sumo_pp_next_payment_date]</strong></small>' , $this->text_domain ) ,
            ) ,
            array(
                'name'    => __( 'Balance Payment Due Date' , $this->text_domain ) ,
                'id'      => $this->prefix . 'balance_payment_due_date_label' ,
                'newids'  => $this->prefix . 'balance_payment_due_date_label' ,
                'type'    => 'textarea' ,
                'std'     => __( '<br><small style="color:#777;">Balance Payment Due Date: <strong>[sumo_pp_next_payment_date]</strong></small>' , $this->text_domain ) ,
                'default' => __( '<br><small style="color:#777;">Balance Payment Due Date: <strong>[sumo_pp_next_payment_date]</strong></small>' , $this->text_domain ) ,
            ) ,
            array(
                'name'    => __( 'Balance Payable' , $this->text_domain ) ,
                'id'      => $this->prefix . 'balance_payable_label' ,
                'newids'  => $this->prefix . 'balance_payable_label' ,
                'type'    => 'textarea' ,
                'std'     => __( '<p><small style="color:#777;">Balance <strong>[sumo_pp_balance_payable]</strong> payable</small></p>' , $this->text_domain ) ,
                'default' => __( '<p><small style="color:#777;">Balance <strong>[sumo_pp_balance_payable]</strong> payable</small></p>' , $this->text_domain ) ,
            ) ,
            array(
                'name'     => __( 'Balance Payable Amount' , $this->text_domain ) ,
                'id'       => $this->prefix . 'balance_payable_amount_label' ,
                'newids'   => $this->prefix . 'balance_payable_amount_label' ,
                'type'     => 'textarea' ,
                'std'      => __( 'Balance Payable Amount' , $this->text_domain ) ,
                'default'  => __( 'Balance Payable Amount' , $this->text_domain ) ,
                'desc_tip' => __( 'To display label under "Cart Totals"' , $this->text_domain ) ,
            ) ,
            array( 'type' => 'sectionend' , 'id' => $this->prefix . 'cart_message_settings' ) ,
                ) ) ;
    }

    /**
     * Custom type field.
     */
    public function get_shortcodes_and_its_usage() {
        $shortcodes = array(
            '[sumo_pp_payment_plan_name]'          => __( 'Use this shortcode to display payment plan name.' , $this->text_domain ) ,
            '[sumo_pp_payment_plan_desc]'          => __( 'Use this shortcode to display payment plan description.' , $this->text_domain ) ,
            '[sumo_pp_total_payable]'              => __( 'Use this shortcode to display total payable amount.' , $this->text_domain ) ,
            '[sumo_pp_balance_payable]'            => __( 'Use this shortcode to display balance payable amount.' , $this->text_domain ) ,
            '[sumo_pp_next_payment_date]'          => __( 'Use this shortcode to display next payment date.' , $this->text_domain ) ,
            '[sumo_pp_next_installment_amount]'    => __( 'Use this shortcode to display next installment_amount.' , $this->text_domain ) ,
            '[sumo_pp_current_installment_amount]' => __( 'Use this shortcode to display current installment_amount.' , $this->text_domain ) ,
                ) ;
        ?>
        <table class="widefat" data-sort="false">
            <thead>
                <tr>
                    <th><?php _e( 'Shortcode' , $this->text_domain ) ; ?></th>
                    <th><?php _e( 'Purpose' , $this->text_domain ) ; ?></th>
                </tr>
            </thead>
            <tbody>                
                <?php foreach( $shortcodes as $shortcode => $purpose ): ?>
                    <tr>
                        <td><?php echo $shortcode ; ?></td>
                        <td><?php echo $purpose ; ?></td>
                    </tr>
                <?php endforeach ; ?>
            </tbody>
        </table>
        <?php
    }

}

return new SUMO_PP_Messages_Settings() ;
