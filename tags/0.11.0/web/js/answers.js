jQuery('document').ready
(
    function ()
    {
        // Lien "ajouter cette notice au caddie"
        jQuery('a.addtocart').click(function(e)
        {
            jQuery.post(this.href);

            // recherche le li.record qui englobe ce lien pour lui appliquer la classe 'incart'            
            var node=jQuery(this).parent();
            while (node.size())
            {
                if (node.is('li.record'))
                {
                    node.addClass('incart');
                    break;
                }
                node=node.parent();
            }
            jQuery(this).remove();
            return false;
        });
    
        // Ajoute toutes les notices au caddie
        jQuery('#answers').submit(function(){
            jQuery.ajax({
                url: jQuery(this).attr('action'),
                type: jQuery(this).attr('method'),
                data: jQuery('input:hidden', this).serialize(),
                success: function(){
                    jQuery('li.record').addClass('incart');
                    jQuery('a.addtocart').remove();
                    jQuery('input.addall').css('visibility','hidden');
                }
            });        
            return false;
        });
        
        /* Gère un effet "hover" du li.record */
        jQuery('li.record').hover
        (
            function(e)
            {
                jQuery(this).addClass("hover");
            }, 
            function(e)
            {
                jQuery(this).removeClass("hover");
            }
        );
    }
);