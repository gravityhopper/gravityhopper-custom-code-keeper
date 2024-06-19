/**
* Custom Code Keeper Scripts
*/

jQuery(document).ready(function( $ ) {
    
    // create form file
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
            // $('#gravityhopper_cck-create_file_trigger_container').replaceWith(response.replace);
            console.log(response);
            location.reload(true); // temp: reload page in order to re-initialize codemirror
        } )
        .fail( function( response ) {
            console.log(response);
        });
        
        
    });

    // create file
    $('button[id^="gravityhopper_cck_create--"').on('click',function(e){
        
        var nonce = $(this).attr('data-nonce');
        var prefix = $(this).attr('data-prefix');
        var filename = $('input#filename').val();
        
        wp.ajax.post( 'create_prefixed_file', {
            headers: 'Content-type: application/json',
            data: {
                nonce: nonce,
                prefix: prefix,
                filename: filename
            }
        } )
        .done( function( response ) {
            // $('#gravityhopper_cck-create_file_trigger_container').replaceWith(response.replace);
            console.log(response);
            location.reload(true); // temp: reload page in order to re-initialize codemirror
        } )
        .fail( function( response ) {
            console.log(response);
        });
        
        
    });

    // save file
    $('button[id^="gravityhopper_cck_save--"]').on('click',function(e){
        
        var nonce = $(this).attr('data-nonce');
        var fileName = $(this).attr('data-file-name');
        var fileSlug = $(this).attr('data-file-slug');
        var fileContent = encodeURIComponent($(`#gravityhopper_cck_${fileSlug}`).val());

        wp.ajax.post( 'save_file', {
            headers: 'Content-type: application/json',
            data: {
                nonce: nonce,
                fileName: fileName,
                fileContent: fileContent
            }
        } )
        .done( function( response ) {
            $(`.gravityhopper_cck_alert_container[data-file-slug="${fileSlug}"]`).html(response.replace);
            setTimeout(function() {
                $(`.gravityhopper_cck_alert_container[data-file-slug="${fileSlug}"]`).fadeOut("slow", function() {
                    $(this).html(""); // Clear the content after fading out
                    $(this).show(); // Show the empty container
                });
            }, 10000);
            console.log(response);
            } )
        .fail( function( response ) {
            $(`.gravityhopper_cck_alert_container[data-file-slug="${fileSlug}"]`).html(response.replace);
                setTimeout(function() {
                    $(`.gravityhopper_cck_alert_container[data-file-slug="${fileSlug}"]`).fadeOut("slow", function() {
                        $(this).html(""); // Clear the content after fading out
                        $(this).show(); // Show the empty container
                    });
                }, 10000);
            console.log(response);
        });
        
    });

    // save file
    $('button[id^="gravityhopper_cck_delete--"]').on('click',function(e){

        var fileName = $(this).attr('data-file-name');
        var fileSlug = $(this).attr('data-file-slug');
        var nonce = $(this).attr('data-nonce');

        if (window.confirm(`Please confirm you would like to delete the file located at ${fileName}.`)) {
            GHCCKDeleteFile(fileName, fileSlug, nonce);
        }
        
    });

    function GHCCKDeleteFile( fileName, fileSlug, nonce ) {

        wp.ajax.post( 'delete_file', {
            headers: 'Content-type: application/json',
            data: {
                nonce: nonce,
                fileName: fileName,
            }
        } )
        .done( function( response ) {
            console.log(response);
            location.reload(true); // temp: reload page
            } )
        .fail( function( response ) {
            $(`.gravityhopper_cck_alert_container[data-file-slug="${fileSlug}"]`).html(response.replace);
                setTimeout(function() {
                    $(`.gravityhopper_cck_alert_container[data-file-slug="${fileSlug}"]`).fadeOut("slow", function() {
                        $(this).html(""); // Clear the content after fading out
                        $(this).show(); // Show the empty container
                    });
                }, 10000);
            console.log(response);
        });

    }

    const input = document.getElementById('filename');
    const sizer = document.getElementById('filename-sizer');

    input.addEventListener('input', updateWidth);

    function updateWidth() {
        sizer.textContent = input.value || ' '; // add a space to ensure minimum width
        input.style.width = `${sizer.scrollWidth}px`;
    }
    
});
