var EmediateAdmin = new function(){
    this.addAd = function() {
        var parent = document.createElement('tr');
        var i = jQuery("#emediate_ads").children().length-1 ;
        var j = jQuery("#emediate_breakpoints").children().length-1;


        // Slug

        var slug = document.createElement('input');
        slug.type='text';
        slug.name="emediate_options[ads]["+i+"][slug]";
        slug.placeholder = "Slug";

        // Implementation

        var implemention = document.createElement('select');
        implemention.name="emediate_options[ads]["+i+"][implementation]";
        this.addOptions('FIF',implemention);
        this.addOptions('Script',implemention);

        // Status

        var status = document.createElement('select');
        status.name="emediate_options[ads]["+i+"][status]";
        this.addOptions('Active',status);
        this.addOptions('Inactive',status);

        // Action

        var action = document.createElement('select');
        action.name="emediate_options[ads]["+i+"][action]";
        this.addOptions('Yes',action);
        this.addOptions('No',action);

        // Remove button

        var remove_button = document.createElement('input');
        remove_button.type='button';
        remove_button.className = "button-secondary";
        remove_button.onclick = function(){EmediateAdmin.remove(parent);};
        remove_button.value='Ta Bort';


        //Height

        var height = document.createElement('input');
        height.type='text';
        height.name="emediate_options[ads]["+i+"][slug]";
        height.placeholder = "Height";
        height.value = 0;

        // ADDING TO TABLE

        parent.appendChild(document.createElement('td').appendChild(slug));

        // CU

        for(var bp=0;bp<j;bp++){
            var cu = document.createElement('input');
            cu.type='text';
            cu.name="emediate_options[ads]["+i+"][cu"+bp+"]";
            cu.placeholder = "CU-"+bp;
            parent.appendChild(document.createElement('td').appendChild(cu));
        }

        parent.appendChild(document.createElement('td').appendChild(implemention));
        parent.appendChild(document.createElement('td').appendChild(status));
        parent.appendChild(document.createElement('td').appendChild(action));
        parent.appendChild(document.createElement('td').appendChild(height));
        parent.appendChild(document.createElement('td').appendChild(remove_button));


        jQuery("#emediate_ads table").append(parent);
    };

    this.addBreakpoint = function(){
        var parent = document.createElement('tr');

        // Min width
        var i = jQuery("#emediate_breakpoints table tbody").children().length;
        var min = document.createElement('input');
        min.type='text';
        min.name="emediate_options[breakpoints]["+i+"][min_width]";
        min.placeholder = "Min width";

        // Max width

        var max = document.createElement('input');
        max.type='text';
        max.name="emediate_options[breakpoints]["+i+"][max_width]";
        max.placeholder = "Max width";

        // Remove button

        var remove_button = document.createElement('input');
        remove_button.type='button';
        remove_button.className = "button-secondary";
        remove_button.onclick = function(){EmediateAdmin.remove(parent);};
        remove_button.value='Ta Bort';
        parent.appendChild(document.createElement('td').appendChild(min));
        parent.appendChild(document.createElement('td').appendChild(max));
        parent.appendChild(document.createElement('td').appendChild(remove_button));

        jQuery("#emediate_breakpoints table").append(parent);


    };

    this.addOptions = function(option, parent){

        var opt = document.createElement('option');
        opt.value = option;
        opt.innerHTML = option;
        parent.appendChild(opt);
    };

    this.remove = function(parent){
        if(confirm("Är du säker?"))jQuery(parent).remove();
    };
};
