emediate-responsive-wp-plugin
=============================

*Collaboration between Norran, VK and Aftonbladet.*

### Admin page

This section of the documentation will go through how you setup the plugin using the settings page in wp-admin

**TODO: write important stuff...**



### Creating ads programmatically

There are two ways to create ads programmatically. Either you use the class `Emediate_Plugin` and refer to an ad that
you have created in the admin page. The other way is to use `ERWP_AdCreator`, which makes it possible to define all
the parameters on-the-fly in your code.

```php
<?php
// The easy way, still controlling the ad from the admin page
$ad_html = ERWP_Plugin::generateAdMarkup('my-ad-slug', false);

// The hard way
$break_points = array();
$js_host = 'ad1.emediate.dk';
$cu_param_name = 'cu';
$cu = 1283;
$impl = 'fif'; // either 'js' or 'fif'
$ad_height = 320;

$ad_creator = new ERWP_AdCreator($break_points, $js_host, $cu_param_name);
$ad_html = $ad_creator->create($cu, $impl, $ad_height);

```


### Ad server requests

Each ad will make a request to the ad server containing a CU-parameter and a query-string containing information
about the current page, this looks something like `?cre=mu;js=y;target=_blank;cu=25027;...`. You can modify the
ad query on front-end by using the action `wp_head`, Example:

```php
<?php
add_action('wp_head', function() { ?>
    <script>
        erwpSettings.adQuery += ';isHandheld=yes';
    </script>
<?php });
```

### Javascript events

`erwpBreakPointChange` — Called each time the client has entered a new break point. This happens when you change
the size of the browser window or when you change orientation on a tablet.

`erwpAdCreated` — This event is called every time an ad is created.

`erwpAdLoaded` — This event is called when an ad iframe has finished loading. If you return false from this callback the add will become hidden.

`erwpAdHidden` — This event is called every time an ad becomes hidden. An ad that doesn't become hidden will get the class `.has-ad`


```js
$(window)
    .on('erwpBreakPointChange', function(evt, newBreakPoint) {
        // We have entered a new break point
    })
    .on('erwpAdCreated', function(evt, adSrc, $fifAdElement, cu, breakPoint) {
        // The iframe is rendered, soon there will be an ad here...
        // Lets remove the .no-ad-here class in case it was
        // added on previous break point
        $fifAdElement.parent().removeClass('no-ad-here');
    })
    .on('erwpAdLoaded', function(evt, fifWin, $fifAdElem, breakPoint) {
        // If an ad iframe contains this special comment we should hide the ad
        if( fifWin.body.innerHTML.indexOf('<!-- custom-no-ad-comment -->') > -1 ) {
            return false; // Hide the add
        }
    })
    .on('erwpAdHidden', function(evt, $fifAdElement, breakPoint) {
        // apply a special class on ad containers containing an empty ad
        $fifAdElement.parent().addClass('no-ad-here');
    });
```

*These events are only triggered on fif ads, not composed js*
