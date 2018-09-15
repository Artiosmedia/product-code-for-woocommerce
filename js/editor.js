(function($){
    $(document).ready( function(){

        var prev = '';

        $( ".variations_form" ).on( "woocommerce_variation_select_change", function () {
            var val = $(this).find('select').val();
            var className = '.if_' + val;
            $(className).removeClass('hidden');
            if( prev ){
                $(prev).addClass('hidden');
            }
            prev = className;
        } );

    } );
})(jQuery);