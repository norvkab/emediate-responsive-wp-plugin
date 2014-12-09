var EmediateAdmin = new function(){
    this.addAd = function() {
        var parent = jQuery('<tr></tr>');
        var i = jQuery("#emediate_ads tr").length;
        var j = jQuery("#emediate_breakpoints tr").length;

        // Slug

        var slug = jQuery('<input/>').attr('type','text').attr('name','emediate_options[ads]['+i+'][slug]').attr('placeholder','Slug');

        // Implementation

        var implemention = jQuery('<select></select>').attr('name','emediate_options[ads]['+i+'][implementation]');
        this.addOptions('FIF',implemention);
        this.addOptions('Composed',implemention);

        // Status

        var status = jQuery('<select></select>').attr('name','emediate_options[ads]['+i+'][status]');
        this.addOptions('Active',status);
        this.addOptions('Inactive',status);

        // Action

        var action = jQuery('<select></select>').attr('name','emediate_options[ads]['+i+'][action]');
        this.addOptions('Yes',action);
        this.addOptions('No',action);

        // Remove button

        var remove_button = jQuery('<input/>').attr('type','button').addClass('button-secondary').attr('value', 'Ta Bort');
        remove_button.onclick = function(){
            EmediateAdmin.remove(parent);
        };

        //Height

        var height = jQuery('<input/>').attr('type','text').attr('name','emediate_options[ads]['+i+'][height]').attr('placeholder','Height').attr('value',0);

        // ADDING TO TABLE

        parent.append(jQuery('<td></td>').append(jQuery('<strong>Slug:</strong>')).append(slug));

        // CU

        for(var bp=0;bp<j;bp++){
            var cu = jQuery('<input/>').attr('type','text').attr('name','emediate_options[ads]['+i+'][cu'+bp+']').attr('placeholder','CU-'+bp);
            parent.append(jQuery('<td></td>').append(jQuery('<strong>CU-'+bp+':</strong>')).append(cu));
        }

        parent.append(jQuery('<td></td>').append(jQuery('<strong>Implementation:</strong>')).append(implemention));
        parent.append(jQuery('<td></td>').append(jQuery('<strong>Status:</strong>')).append(status));
        parent.append(jQuery('<td></td>').append(jQuery('<strong>Action:</strong>')).append(action));
        parent.append(jQuery('<td></td>').append(jQuery('<strong>Height:</strong>')).append(height));
        parent.append(jQuery('<td></td>').append(remove_button));

        jQuery("#emediate_ads table").append(parent);
    };

    this.addBreakpoint = function(){
        var parent = jQuery('<tr></tr>');

        // Min width
        var i = jQuery("#emediate_breakpoints table tbody").children().length;
        var min = jQuery('<input/>').attr('type','text').attr('name','emediate_options[breakpoints]['+i+'][min_width]').attr('placeholder','Min width');

        // Max width

        var max = jQuery('<input/>').attr('type','text').attr('name','emediate_options[breakpoints]['+i+'][max_width]').attr('placeholder','Max width');

        // Remove button

        var remove_button = jQuery('<input/>').attr('type','button').addClass('button-secondary').attr('value','Ta Bort');
        remove_button.onclick = function(){
            EmediateAdmin.remove(parent);
        };

        parent.append(jQuery('<td></td>').append(jQuery('<strong>Min-width:</strong>').append(min)));
        parent.append(jQuery('<td></td>').append(jQuery('<strong>Max-width:</strong>')).append(max));
        parent.append(jQuery('<td></td>').append(remove_button));

        jQuery("#emediate_breakpoints table").append(parent);


    };

    this.addOptions = function(option, object){
        var opt = jQuery('<option></option>').attr('value',option).text(option);
        object.append(opt);
    };

    this.remove = function(parent){
        if(confirm("Är du säker?"))jQuery(parent).remove();
    };
};
