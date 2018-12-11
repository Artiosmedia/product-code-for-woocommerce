(function($){
    var product_id
    var is_variant = 0
    $(document).ready( function(){

       /* var prev = '';

        $( ".variations_form" ).on( "woocommerce_variation_select_change", function () {
            var val = $(this).find('select').val();
            var className = '.if_' + val;
            $(className).removeClass('hidden');
            if( prev ){
                $(prev).addClass('hidden');
            }
            prev = className;
        } );*/
        product_id = $( 'input#product_id' ).val()
        
        check_product_code()
        $( ".variations_form" ).on( "check_variations", function () {
            product_id = $( this  ).find( '[name="variation_id"]' ).val()
            is_variant = 1 
            check_product_code()
        } );

    } );

    function check_product_code()
    {
        $.ajax({
            url : PRODUCT_CODE.ajax,
            data : { action : 'product_code', product_code_id : product_id, is_variant : is_variant },
            dataType : 'json',
            type : 'post',
            beforeSend : function() {
                //$( '.stl_codenum' ).html( '' )
            },
            success : function( response ) {
                if( response.status ) {
                    $( '.stl_codenum' ).html( response.data )
                }
            }
        })
    }
})(jQuery);