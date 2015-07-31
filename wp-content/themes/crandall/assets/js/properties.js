(function($){

$( ".property-contact-message" ).val(function(){
    var form = $(this).parents( "form" );
    var message = form.find( ".property-contact-message-holder > input[type='hidden']" ).val();
    return message;
});

})( jQuery );