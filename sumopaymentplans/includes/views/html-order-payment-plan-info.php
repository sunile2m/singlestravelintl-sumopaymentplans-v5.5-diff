<tr class="<?php echo SUMO_PP_PLUGIN_PREFIX . 'order_payment_plan_info' ; ?>">
    <th>
        <?php
        echo __( 'Payable Now' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) . '<p style="font-weight:normal;text-transform:none;">' . self::get_payment_info_to_display( self::$order_props ) . '</p>' ;
        ?>
    </th>
    <td style="vertical-align: top;">
        <?php
        echo wc_price( self::$order_props[ 'down_payment' ] ) . '<p>' . self::get_payment_info_to_display( self::$order_props , 'balance_payable' ) . '</p>' ;
        ?>
    </td>
</tr>