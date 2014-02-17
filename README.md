emediate-responsive-wp-plugin
=============================

*Collaboration between Norran, VK and Aftonbladet.*

## Admin page

This section of the documentation will go through how you setup the plugin using the settings page in wp-admin

**TODO: write important stuff...**


## Theme implementation

This section of the documentation will go through the javascript events that's triggered by the plugin
when the ads gets rendered.


#### Ad server requests

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

#### Events

`erwpBreakPointChange` — Called each time the client has entered a new break point. This happens when you change
the size of the browser window or when you change rotation of a tablet.

`erwpShouldHideAd` — The plugin will check if the HTML of an ad contains <!-- no matching campaign --> to determine
if the ad should be hidden. This event makes it possible to do more checks against the HTML content of the add. If you
return true from this callback the add will become hidden.

`erwpAdHidden` — This event is called every time an ad becomes hidden.

`erwpFifCreated` — This event is called every time an ad is rendered


```js
$(window)
    .on('erwpBreakPointChange', function() {
        // We have entered a new break point
    })
    .on('erwpShouldHideAd', function(evt, fifWin) {
        // If an ad iframe contains this special comment we should hide the ad
        if( fifWin.body.innerHTML.indexOf('<!-- custom-no-ad-comment -->') > -1 ) {
            return true;
        }
    })
    .on('erwpAdHidden', function(evt, $fifAdElement) {
        // apply a special class on ad containers containing an empty ad
        $fifAdElement.parent().addClass('no-ad-here');
    })
    .on('erwpFifCreated', function(evt, adSrc, $fifAdElement) {
        // The iframe is rendered, soon there will be an ad here...
        // Lets remove the .no-ad-here class in case it was 
        // added on previous break point
        $fifAdElement.parent().removeClass('no-ad-here');
    })
```
