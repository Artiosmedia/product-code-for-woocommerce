jQuery(function(){
jQuery('#the-list').on('click', '.editinline', function(){

    /**
     * Extract metadata and put it as the value for the custom field form
     */
   // inlineEditPost.revert();

    var post_id = jQuery(this).closest('tr').attr('id');

    post_id = post_id.replace("post-", "");

    var $cfd_inline_data = jQuery('#product_code_inline_' + post_id),
        $wc_inline_data = jQuery('#woocommerce_inline_' + post_id );

        //console.log("$cfd_inline_data = "+$cfd_inline_data);

    jQuery('input[name="_product_code"]', '.inline-edit-row').val($cfd_inline_data.find("#product_codeddd").text());


    /**
     * Only show custom field for appropriate types of products (simple)
     */
    var product_type = $wc_inline_data.find('.product_type').text();

    if (product_type=='simple' || product_type=='external') {
        jQuery('.product_code', '.inline-edit-row').show();
    } else {
        jQuery('.product_code', '.inline-edit-row').hide();
    }

});
});
