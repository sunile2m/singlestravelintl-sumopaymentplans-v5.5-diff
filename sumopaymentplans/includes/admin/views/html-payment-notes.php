<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ;
}
?>
<ul class="<?php echo SUMO_PP_PLUGIN_PREFIX . 'payment_notes' ; ?>">
    <?php
    foreach ( $notes as $note ) {
        include( 'html-payment-note.php' ) ;
    }
    ?>
</ul>

<div class="<?php echo SUMO_PP_PLUGIN_PREFIX . 'add_payment_note' ; ?>">
    <h4>
        <?php _e( 'Add note' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?>
    </h4>
    <p>
        <textarea type="text" id="payment_note" class="input-text" cols="20" rows="3"></textarea>
    </p>
    <p>
        <a href="#" class="add_note button" data-id="<?php echo $post->ID ; ?>"><?php _e( 'Add' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></a>
    </p>
</div>
