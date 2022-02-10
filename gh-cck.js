/**
* Gravity Custom Code Keeper Scripts
*/

jQuery(document).ready(function( $ ) {
    
    $('#gravityhopper_cck-create_file_trigger').on('click',function(e){
        
        var nonce = $(this).attr('data-nonce');
        var formID = $(this).attr('data-form-id');
        
        wp.ajax.post( 'create_form_file', {
            headers: 'Content-type: application/json',
            data: {
                nonce: nonce,
                formID: formID,
            }
        } )
        .done( function( response ) {
            $('#gravityhopper_cck-create_file_trigger_container').replaceWith(response.replace);
            console.log(response);
        } )
        .fail( function( response ) {
            console.log(response);
        });
        
        
    });
    
});
