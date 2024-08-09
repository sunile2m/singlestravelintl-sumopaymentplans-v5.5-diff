<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ;
}
?>
<li rel="<?php echo absint( $note->id ) ; ?>" class="<?php echo isset( $note->meta[ 'comment_status' ] ) ? implode( $note->meta[ 'comment_status' ] ) : 'pending' ; ?>">
    <div class="note_content">
        <?php echo wpautop( wptexturize( wp_kses_post( $note->content ) ) ) ; ?>
    </div>
    <p class="meta">
        <abbr class="exact-date" title="<?php echo _sumo_pp_get_date_to_display( $note->date_created ) ; ?>"><?php echo _sumo_pp_get_date_to_display( $note->date_created ) ; ?></abbr>
        <?php printf( ' ' . __( 'by %s' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) , $note->added_by ) ; ?>
        <a href="#" class="delete_note"><?php _e( 'Delete note' , SUMO_PP_PLUGIN_TEXT_DOMAIN ) ; ?></a>
    </p>
</li>