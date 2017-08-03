jQuery(document).ready( function($) {

   $( "#creer_source" ).click( function() {

      var url = ajax_var.url;
      var nonce = ajax_var.nonce;
      if ( confirm( 'Êtes-vous sûr(e) de vouloir créer cette source sur votre compte Sellsy.com ?') ) {
         $.ajax({
            type: "post",
            url: ajax_var.url,
            data: "action=wpi_createOppSource&nonce="+ajax_var.nonce+"&param=creerSource",
            success: function( retour ){
               if ( retour == 'true' ) {
                  $( "#submit" ).click();
               } else {
                  alert( 'Attention: La source d\'opportunités n\'a pas été créée sur votre compte Sellsy.com' );
               }
            }
         });
      }

   });

});

jQuery("body").on({

    ajaxStart: function() { 
        jQuery( "#imgloader" ).show();
    },
    ajaxStop: function() { 
        jQuery( "#imgloader" ).hide();
    }    

});