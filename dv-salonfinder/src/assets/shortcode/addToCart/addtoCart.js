jQuery('document').ready(function($) {
    $('single_add_to_cart_button').on('click', function(e) {
        e.prevertDefault();
        $(this).parent('form').submit(function() {
            alert('submitted');
        });
        return false;
    });
});