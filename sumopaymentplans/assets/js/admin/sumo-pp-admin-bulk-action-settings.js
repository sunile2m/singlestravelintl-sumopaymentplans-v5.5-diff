/* global sumo_pp_admin_bulk_action_settings, ajaxurl */

jQuery( function( $ ) {

    // sumo_pp_admin_bulk_action_settings is required to continue, ensure the object exists
    if( typeof sumo_pp_admin_bulk_action_settings === 'undefined' ) {
        return false ;
    }

    var toggle_events = {
        /**
         * Perform Bulk Action Toggle events.
         */
        init : function( ) {

            this.triggerOnPageLoad( ) ;

            $( document ).on( 'change' , '#get_product_select_type' , this.toggleProductOrCategory ) ;
            $( document ).on( 'change' , '#enable_sumopaymentplans' , this.toggle_payment_settings ) ;
            $( document ).on( 'change' , '#payment_type' , this.toggle_payment_type ) ;
            $( document ).on( 'change' , '#apply_global_settings' , this.toggle_global_settings ) ;
            $( document ).on( 'change' , '#deposit_type' , this.toggle_deposit_type ) ;
            $( document ).on( 'change' , '#deposit_price_type' , this.toggle_deposit_price_type ) ;
            $( document ).on( 'change' , '#pay_balance_type' , this.toggle_pay_balance_type ) ;
            $( document ).on( 'change' , '#user_defined_deposit_type' , this.toggle_user_defined_deposit_type ) ;

        } ,
        triggerOnPageLoad : function() {
            $( '#get_selected_categories' ).select2( ) ;
            $( '#pay_balance_before' ).datepicker( {
                minDate : 0 ,
                changeMonth : true ,
                dateFormat : 'yy-mm-dd' ,
                numberOfMonths : 1 ,
                showButtonPanel : true ,
                defaultDate : '' ,
                showOn : 'focus' ,
                buttonImageOnly : true
            } ) ;

            this.getProductOrCategoryType( $( '#get_product_select_type' ).val( ) ) ;
            this.get_payment_settings( $( '#enable_sumopaymentplans' ).is( ':checked' ) ) ;

        } ,
        toggleProductOrCategory : function( evt ) {
            var $type = $( evt.currentTarget ).val( ) ;

            toggle_events.getProductOrCategoryType( $type ) ;
        } ,
        toggle_payment_settings : function( evt ) {
            var $payment_settings = $( evt.currentTarget ).is( ':checked' ) ;

            toggle_events.get_payment_settings( $payment_settings ) ;
        } ,
        toggle_payment_type : function( evt ) {
            var $payment_type = $( evt.currentTarget ).val() ;

            toggle_events.get_payment_type( $payment_type ) ;
        } ,
        toggle_global_settings : function( evt ) {
            var $apply_global_settings = $( evt.currentTarget ).is( ':checked' ) ;

            toggle_events.get_global_settings( $apply_global_settings ) ;
        } ,
        toggle_deposit_type : function( evt ) {
            var $deposit_type = $( evt.currentTarget ).val() ;

            toggle_events.get_deposit_type( $deposit_type ) ;
        } ,
        toggle_deposit_price_type : function( evt ) {
            var $deposit_price_type = $( evt.currentTarget ).val() ;

            toggle_events.get_deposit_price_type( $deposit_price_type ) ;
        } ,
        toggle_pay_balance_type : function( evt ) {
            var $pay_balance_type = $( evt.currentTarget ).val() ;

            toggle_events.get_pay_balance( $pay_balance_type ) ;
        } ,
        toggle_user_defined_deposit_type : function( evt ) {
            var $user_defined_deposit_type = $( evt.currentTarget ).val() ;

            toggle_events.get_user_defined_deposit_type( $user_defined_deposit_type ) ;
        } ,
        getProductOrCategoryType : function( $type ) {
            $type = $type || '' ;

            $( '#get_selected_products' ).closest( 'tr' ).hide( ) ;
            $( '#get_selected_categories' ).closest( 'tr' ).hide( ) ;

            switch( $type ) {
                case 'selected-products':
                    $( '#get_selected_products' ).closest( 'tr' ).show( ) ;
                    $( '#get_selected_categories' ).closest( 'tr' ).hide( ) ;
                    break ;
                case 'selected-categories':
                    $( '#get_selected_products' ).closest( 'tr' ).hide( ) ;
                    $( '#get_selected_categories' ).closest( 'tr' ).show( ) ;
                    break ;
            }
        } ,
        get_payment_settings : function( $payment_settings ) {
            $payment_settings = $payment_settings || false ;

            if( true === $payment_settings ) {
                $( 'table' ).find( 'tr.bulk-fields-wrapper' ).show() ;
                toggle_events.get_payment_type( $( '#payment_type' ).val() ) ;
            } else {
                $( 'table' ).find( 'tr.bulk-fields-wrapper' ).hide() ;
                paymentPlansSelector.hide() ;
            }
        } ,
        get_payment_type : function( $payment_type , $do_not_apply_gobal ) {
            $payment_type = $payment_type || 'payment-plans' ;
            $do_not_apply_gobal = $do_not_apply_gobal || false ;

            $( '#deposit_type' ).closest( 'tr' ).hide() ;
            $( '#deposit_price_type' ).closest( 'tr' ).hide() ;
            $( '#pay_balance_type' ).closest( 'tr' ).hide() ;
            $( '#set_expired_deposit_payment_as' ).closest( 'tr' ).hide() ;
            $( '#fixed_deposit_price' ).closest( 'tr' ).hide() ;
            $( '#fixed_deposit_percent' ).closest( 'tr' ).hide() ;
            $( '#user_defined_deposit_type' ).closest( 'tr' ).hide() ;
            $( '#min_user_defined_deposit_price' ).closest( 'tr' ).hide() ;
            $( '#max_user_defined_deposit_price' ).closest( 'tr' ).hide() ;
            $( '#min_deposit' ).closest( 'tr' ).hide() ;
            $( '#max_deposit' ).closest( 'tr' ).hide() ;
            paymentPlansSelector.show() ;

            if( 'pay-in-deposit' === $payment_type ) {
                $( '#deposit_type' ).closest( 'tr' ).show() ;
                $( '#deposit_price_type' ).closest( 'tr' ).show() ;
                $( '#pay_balance_type' ).closest( 'tr' ).show() ;
                $( '#fixed_deposit_price' ).closest( 'tr' ).show() ;
                $( '#fixed_deposit_percent' ).closest( 'tr' ).show() ;
                $( '#user_defined_deposit_type' ).closest( 'tr' ).show() ;
                toggle_events.get_user_defined_deposit_type( $( '#user_defined_deposit_type' ).val() ) ;
                toggle_events.get_deposit_type( $( '#deposit_type' ).val() ) ;
                paymentPlansSelector.hide() ;
            }
            if( false === $do_not_apply_gobal ) {
                toggle_events.get_global_settings( $( '#apply_global_settings' ).is( ':checked' ) ) ;
            }
        } ,
        get_global_settings : function( $apply_global_settings ) {
            $apply_global_settings = $apply_global_settings || false ;

            if( true === $apply_global_settings ) {
                $( '#force_deposit' ).closest( 'tr' ).hide() ;
                $( '#deposit_type' ).closest( 'tr' ).hide() ;
                $( '#deposit_price_type' ).closest( 'tr' ).hide() ;
                $( '#pay_balance_type' ).closest( 'tr' ).hide() ;
                $( '#set_expired_deposit_payment_as' ).closest( 'tr' ).hide() ;
                $( '#fixed_deposit_price' ).closest( 'tr' ).hide() ;
                $( '#fixed_deposit_percent' ).closest( 'tr' ).hide() ;
                $( '#user_defined_deposit_type' ).closest( 'tr' ).hide() ;
                $( '#min_user_defined_deposit_price' ).closest( 'tr' ).hide() ;
                $( '#max_user_defined_deposit_price' ).closest( 'tr' ).hide() ;
                $( '#min_deposit' ).closest( 'tr' ).hide() ;
                $( '#max_deposit' ).closest( 'tr' ).hide() ;
                paymentPlansSelector.hide() ;
            } else {
                $( '#force_deposit' ).closest( 'tr' ).show() ;
                toggle_events.get_payment_type( $( '#payment_type' ).val() , true ) ;
            }
        } ,
        get_deposit_type : function( $deposit_type ) {
            $deposit_type = $deposit_type || 'user-defined' ;

            $( '#deposit_price_type' ).closest( 'tr' ).hide() ;
            $( '#fixed_deposit_price' ).closest( 'tr' ).hide() ;
            $( '#fixed_deposit_percent' ).closest( 'tr' ).hide() ;
            $( '#user_defined_deposit_type' ).closest( 'tr' ).show() ;
            toggle_events.get_user_defined_deposit_type( $( '#user_defined_deposit_type' ).val() ) ;

            if( 'pre-defined' === $deposit_type ) {
                $( '#deposit_price_type' ).closest( 'tr' ).show() ;
                $( '#fixed_deposit_price' ).closest( 'tr' ).show() ;
                $( '#fixed_deposit_percent' ).closest( 'tr' ).show() ;
                $( '#user_defined_deposit_type' ).closest( 'tr' ).hide() ;
                $( '#min_user_defined_deposit_price' ).closest( 'tr' ).hide() ;
                $( '#max_user_defined_deposit_price' ).closest( 'tr' ).hide() ;
                $( '#min_deposit' ).closest( 'tr' ).hide() ;
                $( '#max_deposit' ).closest( 'tr' ).hide() ;
                toggle_events.get_deposit_price_type( $( '#deposit_price_type' ).val() ) ;
            }

            $( '#pay_balance_type' ).closest( 'tr' ).show() ;
            toggle_events.get_pay_balance( $( '#pay_balance_type' ).val() ) ;
        } ,
        get_deposit_price_type : function( $deposit_price_type ) {
            $deposit_price_type = $deposit_price_type || 'percent-of-product-price' ;

            $( '#fixed_deposit_price' ).closest( 'tr' ).hide() ;
            $( '#fixed_deposit_percent' ).closest( 'tr' ).show() ;

            if( 'fixed-price' === $deposit_price_type ) {
                $( '#fixed_deposit_price' ).closest( 'tr' ).show() ;
                $( '#fixed_deposit_percent' ).closest( 'tr' ).hide() ;
            }
        } ,
        get_pay_balance : function( $pay_balance_type ) {
            $pay_balance_type = $pay_balance_type || 'after' ;

            $( '#pay_balance_after' ).show() ;
            $( '#pay_balance_before' ).hide() ;
            $( '#set_expired_deposit_payment_as' ).closest( 'tr' ).hide() ;

            if( 'before' === $pay_balance_type ) {
                $( '#pay_balance_after' ).hide() ;
                $( '#pay_balance_before' ).show() ;
                $( '#set_expired_deposit_payment_as' ).closest( 'tr' ).show() ;
            }
        } ,
        get_user_defined_deposit_type : function( $user_defined_deposit_type ) {
            $user_defined_deposit_type = $user_defined_deposit_type || 'percent-of-product-price' ;

            $( '#min_user_defined_deposit_price' ).closest( 'tr' ).hide() ;
            $( '#max_user_defined_deposit_price' ).closest( 'tr' ).hide() ;
            $( '#min_deposit' ).closest( 'tr' ).show() ;
            $( '#max_deposit' ).closest( 'tr' ).show() ;

            if( 'fixed-price' === $user_defined_deposit_type ) {
                $( '#min_user_defined_deposit_price' ).closest( 'tr' ).show() ;
                $( '#max_user_defined_deposit_price' ).closest( 'tr' ).show() ;
                $( '#min_deposit' ).closest( 'tr' ).hide() ;
                $( '#max_deposit' ).closest( 'tr' ).hide() ;
            }
        } ,
    } ;

    var paymentPlansSelector = {
        init : function() {
            $( document ).on( 'click' , '#_sumo_pp_add_col_1_plan' , this.onClickAddColumn1Plan ) ;
            $( document ).on( 'click' , '#_sumo_pp_add_col_2_plan' , this.onClickAddColumn2Plan ) ;
            $( 'table._sumo_pp_selected_plans' ).on( 'click' , 'a.remove_row' , this.onClickRemovePlan ) ;
        } ,
        onClickAddColumn1Plan : function( evt ) {
            evt.stopImmediatePropagation() ;
            evt.preventDefault() ;
            paymentPlansSelector.addPlanSearchField( 'col_1' ) ;
        } ,
        onClickAddColumn2Plan : function( evt ) {
            evt.stopImmediatePropagation() ;
            evt.preventDefault() ;
            paymentPlansSelector.addPlanSearchField( 'col_2' ) ;
        } ,
        onClickRemovePlan : function( evt ) {
            $( this ).closest( 'tr' ).remove() ;
            return false ;
        } ,
        addPlanSearchField : function( col ) {
            var rowID = $( 'table._sumo_pp_selected_col_' + col + '_plans' ).find( 'tbody tr' ).length ;
            $( '.spinner' ).addClass( 'is-active' ) ;

            $.ajax( {
                type : 'POST' ,
                url : ajaxurl ,
                data : {
                    action : '_sumo_pp_get_payment_plan_search_field' ,
                    security : sumo_pp_admin_bulk_action_settings.get_html_data_nonce ,
                    rowID : rowID ,
                    col : col ,
                } ,
                success : function( data ) {

                    if( typeof data !== 'undefined' ) {
                        $( '<tr><td class="sort" width="1%"></td>\n\
                                    <td>' + data.search_field + '</td><td><a href="#" class="remove_row button">X</a></td>\n\
                                    </tr>' ).appendTo( $( 'table._sumo_pp_selected_col_' + col + '_plans' ).find( 'tbody' ) ) ;
                        $( document.body ).trigger( 'wc-enhanced-select-init' ) ;
                    }
                } ,
                complete : function() {
                    $( '.spinner' ).removeClass( 'is-active' ) ;
                }
            } ) ;
            return false ;
        } ,
        hide : function() {
            $( '._sumo_pp_add_plans' ).closest( 'tr' ).hide() ;
            $( '._sumo_pp_selected_plans' ).hide() ;
        } ,
        show : function() {
            $( '._sumo_pp_add_plans' ).closest( 'tr' ).show() ;
            $( '._sumo_pp_selected_plans' ).show() ;
        } ,
    } ;

    var productUpdater = {
        /**
         * Bulk Update the product data's.
         */
        init : function( ) {
            $( document ).on( 'click' , '#bulk_update' , this.initUpdater ) ;
        } ,
        initUpdater : function() {
            if( true !== $( this ).data( 'is_bulk_update' ) ) {
                return false ;
            }

            $( 'tr.bulk-fields-save-and-update' ).find( '.spinner' ).addClass( 'is-active' ) ;

            $.ajax( {
                type : 'POST' ,
                url : sumo_pp_admin_bulk_action_settings.wp_ajax_url ,
                data : {
                    action : '_sumo_pp_bulk_update_product_meta' ,
                    security : sumo_pp_admin_bulk_action_settings.update_nonce ,
                    product_props : $( this ).closest( 'table' ).find( ':input' ).serialize() ,
                } ,
                dataType : 'json' ,
                success : function( response ) {
                    console.log( response ) ;

                    if( response.success ) {
                        window.alert( '"' + parseInt( response.data.productsCount ) + '" product(s) found. Product is updating in the background. The Product update process may take a little while, so please be patient.' ) ;
                    } else {
                        if( 0 === response.data.productsCount ) {
                            window.alert( 'No products found to update.' ) ;
                        } else {
                            window.alert( 'Something went wrong while updating the products.' ) ;
                        }

                    }
                    window.location.reload( true ) ;
                } ,
                complete : function() {
                    $( 'tr.bulk-fields-save-and-update' ).find( '.spinner' ).removeClass( 'is-active' ) ;
                }
            } ) ;
        } ,
    } ;

    toggle_events.init( ) ;
    paymentPlansSelector.init() ;
    productUpdater.init( ) ;
} ) ;