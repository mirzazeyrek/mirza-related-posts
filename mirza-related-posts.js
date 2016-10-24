jQuery(document).on('click', '#get-related-posts', function(){
    jQuery(this).html("Please wait loading...");
    jQuery.ajax({
        url: mrp.ajax_url,
        type: "POST",
        data: {
            post_id: jQuery(this).attr("data-post"),
            action: "get_related_posts",
            security: mrp.ajax_check
        },
        success: function (data) {
            jQuery("#get-related-posts").hide();
            var json;
            json = jQuery.parseJSON(data);
            if(json.status == false) {
                jQuery("#related-posts").html(json.message).slideDown();
            } else {
                jQuery("#related-posts").html(json.related_posts).slideDown();
            }
        }
    });
});