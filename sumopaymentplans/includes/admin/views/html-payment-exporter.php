<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ;
}
?>
<div class="wrap woocommerce">
    <h1><?php esc_html_e( 'Export Payments' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></h1>
    <div class="sumo-pp-payment-exporter-wrapper">
        <form class="sumo-pp-payment-exporter">
            <header>
                <h2><?php esc_html_e( 'Export Payments to a CSV file' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></h2>
                <p><?php esc_html_e( 'This tool allows you to generate and download a CSV file containing a list of all payments.' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></p>
            </header>
            <section>
                <table class="form-table exporter-options">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="payment-statuses-exporter"><?php esc_html_e( 'Which payment statuses should be exported?' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></label>
                            </th>
                            <td>
                                <select name="payment_statuses[]" class="wc-enhanced-select" multiple="multiple">
                                    <?php
                                    foreach ( _sumo_pp_get_payment_statuses() as $status => $label ) {
                                        echo '<option value="' . $status . '">' . $label . '</option>' ;
                                    }
                                    ?>                                           
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="payment-products-exporter"><?php esc_html_e( 'Which payment products should be exported?' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></label>
                            </th>
                            <td>
                                <?php
                                _sumo_pp_wc_search_field( array (
                                    'class'       => 'wc-product-search' ,
                                    'name'        => 'payment_products' ,
                                    'action'      => 'woocommerce_json_search_products_and_variations' ,
                                    'placeholder' => __( 'Search for a payment product&hellip;' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                                ) ) ;
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="payment-types-exporter"><?php esc_html_e( 'Which payment types should be exported?' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></label>
                            </th>
                            <td>
                                <select name="payment_types[]" class="wc-enhanced-select" multiple="multiple">
                                    <?php
                                    foreach ( array ( 'pay-in-deposit' => __( 'Pay in deposit' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , 'payment-plans' => __( 'Payment plans' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ) as $payment_type => $payment_label ) {
                                        echo '<option value="' . $payment_type . '">' . $payment_label . '</option>' ;
                                    }
                                    ?>                                           
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="payment-plans-exporter"><?php esc_html_e( 'Which payment plans should be exported?' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></label>
                            </th>
                            <td>
                                <?php
                                _sumo_pp_wc_search_field( array (
                                    'class'       => 'wc-product-search' ,
                                    'name'        => 'payment_plans' ,
                                    'action'      => '_sumo_pp_json_search_payment_plans' ,
                                    'placeholder' => __( 'Search for a payment plan&hellip;' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                                ) ) ;
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="payment-buyers-exporter"><?php esc_html_e( 'Which payment buyers should be exported?' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></label>
                            </th>
                            <td>
                                <?php
                                _sumo_pp_wc_search_field( array (
                                    'class'       => 'wc-product-search' ,
                                    'name'        => 'payment_buyers' ,
                                    'action'      => '_sumo_pp_json_search_customers_by_email' ,
                                    'placeholder' => __( 'Search for a buyer email&hellip;' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ,
                                ) ) ;
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="payment-from-to-date-exporter"><?php esc_html_e( 'Date Range' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></label>
                            </th>
                            <td>
                                <input type="text" name="payment_from_date" placeholder="<?php esc_html_e( 'Select From Date' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?>"  value="">
                                <input type="text" name="payment_to_date" placeholder="<?php esc_html_e( 'Select To Date' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ?>"  value="">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>
            <div class="export-actions">
                <input type="hidden" id="exported_data" value=""/>
                <input type="button" class="exporter-button button button-primary" value="<?php esc_attr_e( 'Generate CSV' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?>">
            </div>
        </form>
    </div>
</div>