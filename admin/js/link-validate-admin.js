/*jQuery(document).ready(function(jQuery) {
    jQuery('.retest').on('click', function() {
        var lv_element = jQuery(this);
        result = testLink(lv_element);

    });
    function testLink(lv_element) {
        var url = lv_element.attr('id');
        jQuery.ajax({
            url: url, //+'dud#link',
            type: 'get',
            method: 'get',
            error: function() {
                lv_element.addClass('red');
            },
            success: function() {
                lv_element.addClass('green');
            }
        });
    }
});*/
