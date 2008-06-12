// DM : copié depuis la fonction param de jQuery, en remplaçant encodeURIComponent par escape
// génère une query string en iso-8859-1
function asQueryString( a ) { 
    var s = [];

    // If an array was passed in, assume that it is an array
    // of form elements
    if ( a.constructor == Array || a.jquery )
        // Serialize the form elements
        jQuery.each( a, function(){
            s.push( escape(this.name) + "=" + escape( this.value ) );
        });

    // Otherwise, assume that it's an object of key/value pairs
    else
        // Serialize the key/values
        for ( var j in a )
            // If the value is an array then the key names need to be repeated
            if ( a[j] && a[j].constructor == Array )
                jQuery.each( a[j], function(){
                    s.push( escape(j) + "=" + escape( this ) );
                });
            else
                s.push( escape(j) + "=" + escape( jQuery.isFunction(a[j]) ? a[j]() : a[j] ) );

    // Return the resulting serialization
    return s.join("&").replace(/%20/g, "+");
}


function checkDuplicate()
{
    // normallement, on devrait utiliser simplement jQuery('form').serialize()
    // le problème c'est que dans ce cas, la query string est encodée en utf-8
    // Actuellement fab ne gère pas ça et apparemment, rien dans la requête reçue 
    // ne permet de dire si l'url est encodée en ISO-8859-1 ou en UTF-8
    // Pour contourner, on génère nous même la query string pour être sûr qu'elle
    // est toujours envoyée en ISO-8859-1.  

    jQuery('#Duplicates').html('Recherche des doublons potentiels...');
    jQuery('#DuplicatesContainer').show('slow');
    jQuery('#Duplicates').load('../DedupModule/DedupData?' + asQueryString(jQuery("form").serializeArray()));
}