emediate-responsive-wp-plugin
=============================

*Collaboration between Norran, VK and Aftonbladet.*

**Table of contents**

- [Getting started](#getting-started)
- [Creating ads programmatically](#Creating ads programmatically)
- [Ad server requests](#Ad-server-requests)
- [Geolocation](#geolocation)
- [Javascript events](#Javascript-events)


### Getting started

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
$impl = ERWP_AdCreator::IMPL_FIF; // either ERWP_AdCreator::IMPL_FIF or ERWP_AdCreator::IMPL_COMPOSED
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

### Geolocation

By enabling the geolocating features of this plugin the coordinates of your visitors will be sent to the ad server. This makes it  possible to target ads only to be displayed when the visitor is residing at certain locations.
Geolocation is possible both via browser API, or via a native app-wrapper.
Options for native app-wrapper is disabled by default, and can be enabled by the
following filters:

```php
// method to show emediate options for mobile app in admin
add_filter('emediate_app_show_options', '__return_true');

// javascript-method to return position from app
add_filter('emediate_app_location_method', function() {
    return 'window._nativeAppQueryLocation';
});
```

Example of a javascript-implementation with asynchronous location function:
```js

var interval = null;
var hasStartedGPS = false;
var cachedCoords = null;

var callbacks = [];

// wait for gps lock, try to fetch multiple times
var queryLocation = function(title, message, callback) {

    var resetGPS = function() {
        callbacks = []; // clear memory
        if (interval) {
            clearInterval(interval);
            interval = false;
        }
        if (hasStartedGPS) {
            nativeapp.stopGeoLocating();
            hasStartedGPS = false;
        }
    };

    if (cachedCoords) {
        return callback(cachedCoords);
    } else if (interval === false) {  // timeout
        return resetGPS();
    }

    callbacks.push(callback);

    if (interval === null) {

        if (!hasStartedGPS) {
            nativeapp.startGeoLocating(title, message);
            hasStartedGPS = true;
        }
        interval = setInterval(function() {
            try {
                var coords = nativeapp.getGeoLocation();
                coords = $.parseJSON(coords);
            } catch (e) { return; }

            if (coords && coords.latitude) {
                cachedCoords = coords;
                for (var i = 0; i < callbacks.length; i++) {
                    callbacks[i](coords);
                }

                resetGPS();
            }
        }, 300);

        setTimeout(function() {
            resetGPS();
        }, 5000);
    }
}

window._nativeAppQueryLocation = queryLocation;
```

To override disable browser geolocation (e.g. on desktop site), use filter `emediatate_enable_browser_location`:
```php
// override option to always disable geolocation on desktop site
add_filter('emediatate_enable_browser_location', function($enabled) {
    if (WP_IS_MOBILE) return $enabled;
    return false;
});
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
