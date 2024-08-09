/* global sumo_pp_single_product_page */

jQuery( function( $ ) {

    if ( typeof sumo_pp_single_product_page === 'undefined' ) {
        return false ;
    }

    var is_blocked = function( $node ) {
        return $node.is( '.processing' ) || $node.parents( '.processing' ).length ;
    } ;

    /**
     * Block a node visually for processing.
     *
     * @param {JQuery Object} $node
     */
    var block = function( $node ) {
        if ( ! is_blocked( $node ) ) {
            $node.addClass( 'processing' ).block( {
                message : null ,
                overlayCSS : {
                    background : '#fff' ,
                    opacity : 0.6
                }
            } ) ;
        }
    } ;

    /**
     * Unblock a node after processing is complete.
     *
     * @param {JQuery Object} $node
     */
    var unblock = function( $node ) {
        $node.removeClass( 'processing' ).unblock() ;
    } ;

    var single_product = {

        /**
         * Init single product
         */
        init : function() {

            console.log("single_product - init");

            $( 'div._sumo_pp_wc_booking_deposit_fields' ).insertBefore( '.single_add_to_cart_button' ) ;

            $( document ).on( 'found_variation.wc-variation-form' , { variationForm : this } , this.onFoundVariation ) ;
            $( document ).on( 'click.wc-variation-form' , '.reset_variations' , { variationForm : this } , this.onResetVariation ) ;
            $( document ).on( 'change' , '.variations select, input.variationsradio' , this.toggleVariations ) ;

            $( document ).on( 'change' , 'input[type=radio][name="_sumo_pp_payment_type"]' , this.togglePaymentType ) ;
            $( document ).on( 'wc_booking_form_changed' , this.bookingFormChanged ) ;
            $( document ).on( 'sumo_bookings_calculated_price' , this.bookingFormChanged ) ;
            $( document ).on( 'yith_wcbk_form_update_response' , this.bookingFormChanged ) ;
            $( document ).on( 'click' , 'div._sumo_pp_plan_view_more > p > a' , this.planInfoModal.show ) ;
            $( document ).on( 'click' , 'div._sumo_pp_modal-close > img' , this.planInfoModal.hide ) ;

            if ( 'yes' === sumo_pp_single_product_page.hide_product_price && 'payment-plans' === $( 'input[type=radio][name="_sumo_pp_payment_type"]' ).val() ) {
                $( 'div' ).find( 'p.price' ).slideUp( 'fast' ) ;
            }
        } ,
        getSingleAddToCartVariationData : function() {
            var $hidden_datas = $( 'form' ).find( '#_sumo_pp_single_variation_data' ).data() ;

            if ( 'undefined' !== typeof $hidden_datas ) {
                var beforeVariationData = '' ,
                        afterVariationData = '' ;

                $.each( $hidden_datas , function( context , data ) {
                    switch ( context ) {
                        case 'payment_type_fields_' + single_product.variation_id:
                            beforeVariationData += data ;
                            break ;
                    }
                } ) ;

                if ( '' !== beforeVariationData || '' !== afterVariationData ) {
                    if ( '' !== beforeVariationData ) {
                        $( 'span#_sumo_pp_before_single_variation' ).html( beforeVariationData ) ;
                    }
                    if ( '' !== afterVariationData ) {
                        $( 'span#_sumo_pp_after_single_variation' ).html( afterVariationData ) ;
                    }
                }

                if ( 'yes' === sumo_pp_single_product_page.hide_product_price && 'payment-plans' === $( 'div#_sumo_pp_payment_type_fields' ).find( 'p input[name="_sumo_pp_payment_type"][type="hidden"]' ).val() ) {
                    single_product.hideProductGetPrice() ;
                } else {
                    single_product.showProductGetPrice() ;
                }
            }
        } ,
        onFoundVariation : function( evt , variation ) {
            single_product.variation_id = variation.variation_id ;
            single_product.onResetVariation() ;

            if ( '' !== single_product.variation_id ) {
                single_product.getSingleAddToCartVariationData() ;
            }
        } ,
        toggleVariations : function() {

            console.log("toggleVariations");
            single_product.variation_id = $( 'input[name="variation_id"]' ).val() ;


            if ( '' !== single_product.variation_id ) {
                $.each( $( 'form' ).find( '#_sumo_pp_single_variations' ).data( 'variations' ) , function( index , variation_id ) {
                    if ( variation_id == single_product.variation_id ) {
                        single_product.getSingleAddToCartVariationData() ;
                    }
                } ) ;
            } else {
                single_product.onResetVariation() ;
            }
        } ,

        onResetVariation : function( evt , variation ) {
            $( 'span#_sumo_pp_before_single_variation, span#_sumo_pp_after_single_variation' ).html( '' ) ;
        } ,

        togglePaymentType : function( evt ) {

            console.log("togglePaymentType");

            $( 'div#_sumo_pp_plans_to_choose' ).slideUp( 'fast' ) ;
            $( 'div#_sumo_pp_amount_to_choose' ).slideUp( 'fast' ) ;
            
            single_product.showProductGetPrice() ;

            switch ( $( evt.currentTarget ).val() ) {
                case 'payment-plans':
                    $( 'div#_sumo_pp_plans_to_choose' ).slideDown( 'fast' ) ;

                    if ( 'yes' === sumo_pp_single_product_page.hide_product_price ) {
                        single_product.hideProductGetPrice() ;
                    }
                    break ;
                case 'pay-in-deposit':
                    $( 'div#_sumo_pp_amount_to_choose' ).slideDown( 'fast' ) ;
                    break ;
            }
        } ,
        showProductGetPrice : function() {

            console.log("showProductGetPrice");

            $( 'div' ).find( 'p.price' ).slideDown( 'fast' ) ;
            $( 'div.woocommerce-variation-price' ).find( 'span.price' ).slideDown( 'fast' ) ;
        } ,
        hideProductGetPrice : function() {

            console.log("hideProductGetPrice");

            $( 'div' ).find( 'p.price' ).slideUp( 'fast' ) ;
            $( 'div.woocommerce-variation-price' ).find( 'span.price' ).slideUp( 'fast' ) ;
        } ,
        bookingFormChanged : function() {

            $.blockUI.defaults.overlayCSS.cursor = 'wait' ;
            block( $( 'div' ).find( '#_sumo_pp_payment_type_fields' ) ) ;

            $.ajax( {
                type : 'POST' ,
                url : sumo_pp_single_product_page.wp_ajax_url ,
                data : {
                    action : '_sumo_pp_get_wc_booking_deposit_fields' ,
                    security : sumo_pp_single_product_page.get_wc_booking_deposit_fields_nonce ,
                    product : sumo_pp_single_product_page.product ,
                } ,
                success : function( data ) {
                    if ( 'undefined' !== typeof data.result && 'success' === data.result ) {
                        $( 'div#_sumo_pp_payment_type_fields' ).remove() ;
                        $( 'span#_sumo_pp_wc_booking_deposit_fields' ).html( data.html ) ;
                        $( 'span#_sumo_pp_wc_booking_deposit_fields' ).insertBefore( '.single_add_to_cart_button' ) ;
                    }
                } ,
                complete : function() {
                    unblock( $( 'div' ).find( '#_sumo_pp_payment_type_fields' ) ) ;
                }
            } ) ;
        } ,
        planInfoModal : {
            show : function( evt ) {
                evt.preventDefault() ;
                $( this ).closest( 'div' ).find( 'div._sumo_pp_modal' ).show() ;
            } ,
            hide : function() {
                $( this ).closest( 'div._sumo_pp_modal' ).hide() ;
            } ,
        } ,
    } ;

    single_product.init() ;
} ) ;