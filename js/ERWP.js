var ERWP = (function($, window, erwpSettings) {

    'use strict';

    var $win = $(window),
        _debug = function(message) {
            if( typeof window.erwpDebug == 'function' ) {
                window.erwpDebug('ERWP: '+message);
            }
            else if( erwpSettings.debug && window.console && window.console.log ) {
                window.console.log('EWRP: '+message);
            }
        },
        ERWP = {

            /**
             * Current break point
             * @var {Object|Boolean}
             */
            breakPoint : false,

            /**
             * Array with html elements containing fif-ads (jQuery).
             * This array will become populated while the page gets rendered
             * by calling ERWP.fif(...)
             *
             * @var {jQuery[]}
             */
            $fifAds : {},

            /**
             * Whether or not we support break points
             * @var {Boolean}
             */
            hasRegisteredBreakPoints : $.isArray(erwpSettings.breakPoints) && erwpSettings.breakPoints.length,

            /**
             * Scroll events for lazy loading.
             *
             * @var {Array}
             */
            scrollEvents : ['scroll', 'touchmove'],

            /**
             * Prints out an emediate ad using composed javascript
             * @param {String} src
             * @param {String|Number} cu
             */
            composed : function(src, cu) {
                if( !cu ) {
                    cu = (src.split(erwpSettings.cuParamName+'=')[1] || '').split(';')[0];
                }

                var clickURL = 'http://' + erwpSettings.defaultJSHost + '/eas?'+erwpSettings.cuParamName+'='+cu+';ty=ct' +
                                ( erwpSettings.adQuery ? ';'+erwpSettings.adQuery : '');

                document.write(
                    '<script src="'+src+';'+erwpSettings.adQuery+'"></script>'+
                    '<noscript><a target="_blank" data-test="click" href="'+clickURL+'">'+
                        '<img src="'+src+';cre=img" alt="emediate" /></a></noscript>'
                );

                _debug('Creating composed ad for '+cu);
            },

            /**
             * Finds ad-element at given index and inserts
             * a fif-ad in that element
             * @param {Number} fifIndex
             */
            fif : function(fifIndex) {
                var $elem = $('#emediate-fif-'+fifIndex),
                    self = this,
                    getLocation = null,
                    browserEnabled = erwpSettings.enableLocationBrowser,
                    ua = window.navigator.userAgent,
                    timeout = 5000;

                // Collect this ad element for later (when ad needs
                // to be reloaded due to rotation change for example)
                this.$fifAds[fifIndex] = $elem;

                var normal_render = function() {
                    timeout = null;
                    if (erwpSettings.useLazyLoad && fifIndex >= erwpSettings.lazyLoadStart) {
                        // Bind lazy load
                        $win.on(self.getNamespacedEvents(fifIndex), {i : fifIndex, el : $elem}, self.lazyLoad);
                    } else {
                        self.renderFifAd($elem);
                    }
                };

                // normal browser api
                if (ua.match(/android/i)) {
                    browserEnabled = erwpSettings.enableLocationAndroid;
                }
                else if (ua.match(/iphone/i) || ua.match(/ipad/i) || ua.match(/ipod/i) || ua.match(/iOS/)) {
                    browserEnabled = erwpSettings.enableLocationiOS;
                }

                var appLocationMethod = null;
                // get method to fetch location from app wrapper (if possible)
                // should return a function with arguments (questionTitle, questionMessage, callback)
                // if set
                try {
                    if (!erwpSettings.appLocationMethod) {
                        appLocationMethod = null;
                    }
                    else if ($.type(erwpSettings.appLocationMethod) == 'string') {
                        appLocationMethod = eval(erwpSettings.appLocationMethod);
                    }
                    else if ($.isFunction(erwpSettings.appLocationMethod)) {
                        appLocationMethod = erwpSettings.appLocationMethod;
                    }
                } catch (e) {
                    if (window.console && window.console.log) window.console.log('Error getting app location method. Fallback to browser <mode></mode>. ' + e.toString());
                }

                // cached
                if (erwpSettings.coords) {
                    getLocation = function(callback) {
                        callback(erwpSettings.coords);
                    }
                }
                else if (appLocationMethod) {
                    if (erwpSettings.enableLocationApp) {
                        getLocation = function(callback) {
                            appLocationMethod(erwpSettings.locationQueryTitle, erwpSettings.locationQueryText, callback);
                        }
                    }

                }
                else if (browserEnabled && navigator.geolocation) {

                    getLocation = function(callback) {

                        var opts = {
                            enableHighAccuracy: true
                        };

                        var _success = function(p) {
                            //for mozilla geode,it returns the coordinates slightly differently
                            var params;
                            if(typeof(p.latitude)===undefined) {
                                params = {
                                    timestamp: p.timestamp,
                                    coords: {
                                        latitude:  p.latitude,
                                        longitude: p.longitude
                                    }
                                };
                            } else {
                                params = p;
                            }

                            if (timeout !== null) {
                                callback(params.coords);
                            } else {
                                normal_render();
                            }
                        };

                        var _error = function(e) {
                            $win.trigger('geolocation_failed', e.code);
                            normal_render();
                        };

                        navigator.geolocation.getCurrentPosition(_success, _error, opts);
                    }

                }

                // Only query for jQuery element filter
                if (erwpSettings.locationjQueryFilter) {
                    try {
                        if (!$elem.is(erwpSettings.locationjQueryFilter)) {
                            getLocation = null;
                        }
                    } catch (e) {}
                }

                if (getLocation) {
                    getLocation(function(loc) {
                        if (timeout == null) return;
                        timeout = null;

                        if (!loc.latitude) {
                            normal_render();
                            return;
                        }
                        if (!erwpSettings.coords) {
                            $win.trigger('geolocation_found', loc);
                        }
                        erwpSettings.coords = loc.coords;
                        self.renderFifAd($elem, ';lat='+loc.latitude+';lon='+loc.longitude+';');
                    });

                    setTimeout(function() {
                        if (timeout !== null) {
                            timeout = null;
                            $win.trigger('geolocation_failed', -1);
                            normal_render();
                        }
                    }, timeout);

                }
                else normal_render();
            },

            /**
             * Get namespaced scroll events, for unbinding purposes after ad has loaded.
             *
             * @param {Number} index
             */
            getNamespacedEvents : function(index) {
                var namespace = '.ERWP_'+index;
                return (this.scrollEvents.join(namespace+' ')+namespace);
            },

            /**
             * Checks whether to lazy load the current ad or not.
             *
             * @param {Object} event Contains $elem and fifIndex.
             */
            lazyLoad : function(event) {
                var $elem = event.data.el,
                    fifIndex = event.data.i;

                //load ads that are close by, either above or below. Don't load ads that are waaay above, for instance when the user uses the back button (to a previously scrolled page) or ctrl+end
                if ( $win.scrollTop() >= ($elem.offset().top - $win.height() - erwpSettings.lazyLoadOffset)
                    && $win.scrollTop() - ($elem.offset().top + $elem.height()) < erwpSettings.lazyLoadOffset
                    ) {
                    _debug('Lazy loading ad: ' + fifIndex);
                    ERWP.renderFifAd($elem);
                    $win.off(ERWP.getNamespacedEvents(fifIndex));
                }
            },

            /**
             * @returns {Number}
             */
            getNumFifAds : function() {
                var num = 0;
                $.each(this.$fifAds, function(i, obj) {
                    num++;
                });
                return num;
            },

            /**
             * Inserts a fif-ad in given element
             * @param {jQuery} $elem
             * @param {String} extraQuery
             */
            renderFifAd : function($elem, extraQuery) {
                var cu, src,
                    height = $elem.attr('data-height') || 0,
                    width = $elem.attr('data-width') || 0;

                if( this.breakPoint ) {
                    cu = $elem.attr('data-bp-'+this.breakPoint.min_width+'-'+this.breakPoint.max_width);
                }
                if( !cu ) {
                    cu = $elem.attr('data-cu');
                }

                if( !cu ) {
                    this.hideAd($elem); // Current break-point does not have an ad
                }

                else if( $elem.attr('data-current-cu') != cu ) {

                    // Restore ad element
                    $elem
                        .removeClass('has-ad')
                        .attr('data-current-cu', cu)
                        .html('');

                    src = '//'+erwpSettings.defaultJSHost+'/eas?cre=mu;js=y;target=_blank;'+erwpSettings.cuParamName+'='+cu+
                        ';'+erwpSettings.adQuery+(extraQuery || '');

                    // sanitize url
                    src = src.replace(/;;/g, ';');
                    if( src.substr(-1) == ';' ) {
                        src = src.substr(0, src.length - 1);
                    }

                    // Load fif
                    // window.EAS_load_fif('emediate-fif-'+$elem.attr('data-index'), erwpSettings.fifHtmlFile, src, width, height);
                    // Here we use the undocumented EAS_create_iframe to get hold of the iframe document
                    var iframe = window.EAS_create_iframe($elem.get(0), width, height, erwpSettings.fifHtmlFile);
                    iframe.EAS_src = src+";fif=y";
                    iframe.ERWP_fifIndex = $elem.attr('data-ad-index');

                    // Make iframe element support trancparency
                    $elem.find('iframe').attr('allowTransparency','true');

                    $win.trigger('erwpAdCreated', [src, $elem, cu, this.breakPoint]);
                    _debug('Creating fif for '+cu);
                }
            },

            /**
             * Figures out the break point for given window width
             * @param {Number} windowWidth
             * @return {Object|Boolean} Returns false if not suitable break point could be found
             */
            getBreakPoint : function(windowWidth) {
                var foundBreakPoint = false;
                $.each(erwpSettings.breakPoints, function(i, breakPoint) {
                    if( windowWidth >= breakPoint.min_width && windowWidth < breakPoint.max_width ) {
                        foundBreakPoint = breakPoint;
                        return false;
                    }
                });
                return foundBreakPoint;
            },

            /**
             * Iterate through all fif-ad elements and re-insert fif-ad
             */
            reloadFiFAds : function() {
                var extraArgs = '';

                // add location params if cached
                if (erwpSettings.coords && erwpSettings.coords.latitude) {
                    var loc = erwpSettings.coords;
                    extraArgs = ';lat='+loc.latitude+';lon='+loc.longitude+';';
                }

                _debug('Reloading fif ads');

                $.each(this.$fifAds, function(i, $adElem) {
                    ERWP.renderFifAd($adElem, extraArgs);
                });
            },

            /**
             * Function called by an iframe, containing a fif-ad, when the iframe
             * document gets loaded
             *
             * @param {Window} iframeWin
             */
            fifLoaded : function(iframeWin) {
                var $adElem = this.getAdElementFromFifIframe(iframeWin),
                    adInspect = this.inspectFif(iframeWin, $adElem);

                // Don't resize/collapse ads with index defined in erwpSettings.adsToNotResize
                if (erwpSettings.adsToNotResize) {
                    var index = parseInt($adElem.attr('data-ad-index'), 10);
                    if (erwpSettings.adsToNotResize.indexOf(index) !== -1) {
                        // Probably safe to add class even if we don't know if we get an ad or not.
                        $adElem.addClass('has-ad');
                        _debug('Skipping resizing of ad ' + index);
                        return;
                    }
                }

                if( adInspect.isEmpty ) {
                    _debug('Making ad '+$adElem.attr('id')+' hidden, cause: '+adInspect.emptyReason);
                    this.hideAd($adElem);
                } else {
                    $adElem.addClass('has-ad');
                    this.resizeIframeToDocumentSize($adElem);

                    var count = 0,
                        _this = this,
                        interval = setInterval(function() {
                            _this.resizeIframeToDocumentSize($adElem);
                            count += 1;
                            if (count > 4)
                                clearInterval(interval);
                        }, 500);
                }
            },

            /**
             * Hide an ad
             * @param {jQuery} $adElem
             */
            hideAd : function($adElem) {
                $adElem
                    .height(0)
                    .hide()
                    .removeClass('has-ad')
                    .attr('data-current-cu', '');

                $win.trigger('erwpAdHidden', [$adElem, this.breakPoint]);
            },

            /**
             * @param {Window} iframeWin
             * @return {jQuery}
             */
            getAdElementFromFifIframe : function(iframeWin) {
                if( 'ERWP_fifIndex' in iframeWin.frameElement ) {
                    return this.$fifAds[iframeWin.frameElement.ERWP_fifIndex];
                } else {
                    throw new Error('Fif iframe is missing ERWP_fifIndex');
                }
            },

            /**
             * @param {jQuery} $adElem
             * @return {Boolean}
             */
            resizeIframeToDocumentSize : function($adElem) {
                var $iframe = $adElem.find('iframe'),
                    $iframeBody = $iframe.contents().find('body'),
                    iframeHeight = $iframe.attr('data-current-height') || $iframe.height(),
                    iframeWidth = $iframe.attr('data-current-width') || $iframe.width(),
                    iframeDocHeight = $iframeBody.outerHeight(),
                    iframeDocWidth = $iframeBody.outerWidth(),
                    updateSize = function(newSize, oldSize, sizeFunc) {
                        if( newSize != oldSize ) {
                            _debug('Resizing ad '+$adElem.attr('id')+' '+sizeFunc+', from '+oldSize+' to '+newSize);
                            $iframe[sizeFunc](iframeDocHeight).attr('data-current-'+sizeFunc, iframeDocHeight);
                            $adElem[sizeFunc](iframeDocHeight);
                            return true;
                        }
                        return false;
                    },
                    gotNewHeight = updateSize(iframeDocHeight, iframeHeight, 'height'),
                    gotNewWidth = erwpSettings.resizeAdWidth ? updateSize(iframeDocWidth, iframeWidth, 'width') : false;

                return gotNewHeight || gotNewWidth;
            },

            /**
             * @example
             *
             *  var fifWindow = $('#fif-1 iframe').contents();
             *  inspection = ERWP.inspectFif(fifWindow, ERWP.getAdElementFromFifIframe(fifWindow);)
             *
             *  inspection =  {
             *   isEmpty : bool,
             *   emptyReason : '...'
             *  }
             *
             * @param {Object} fifWin
             * @param {jQuery} $adElem
             * @returns {Object}
             */
            inspectFif : function (fifWin, $adElem) {

                // @todo: what about ie7 ?

                var foundEmptyTag = null,
                    containsEmptyAdTag = function () {
                        var body = fifWin.body || fifWin.document.body;
                        if ( !body )
                            return false;

                        var hasEmptyAdTag = false,
                            emptyAdTags = (erwpSettings.emptyAdTags || '').split('\n');

                        emptyAdTags.push('<!-- No matching campaign -->'); // emediates own no-ad-tag

                        $.each(emptyAdTags, function(i, tag) {
                            var emptyAdTag = $.trim(tag);
                            if( emptyAdTag && body.innerHTML.indexOf(emptyAdTag) > -1 ) {
                                foundEmptyTag = emptyAdTag;
                                hasEmptyAdTag = true;
                                return false;
                            }
                        });
                        return hasEmptyAdTag;
                    };

                var status = {
                    isEmpty : false,
                    emptyReason : ''
                };

                if( containsEmptyAdTag() ) {
                    status = {isEmpty: true, emptyReason: 'Contains empty-ad-tag: '+foundEmptyTag};
                } else if( $win.trigger('erwpAdLoaded', [fifWin, $adElem, this.breakPoint]) === false ) {
                    status = {isEmpty: true, emptyReason: 'Turned hidden via js event'};
                }

                return status;
            }
        };


    if( ERWP.hasRegisteredBreakPoints ) {

        // figure out current break point
        ERWP.breakPoint = ERWP.getBreakPoint($win.width());

        // Capture window width and figure out current break-point
        // when window size changes. If we have a new break-point
        // all fif ads gets reloaded
        var onWindowResize = function() {
            var breakPoint = ERWP.getBreakPoint($win.width());
            if( breakPoint != ERWP.breakPoint ) {
                // break point has changed
                ERWP.breakPoint = breakPoint;
                ERWP.reloadFiFAds();
                $win.trigger('erwpBreakPointChange', [ERWP.breakPoint]);
            }
        };
        $win.on('resize', onWindowResize);
        $win.on('orientationchange', onWindowResize);
    }

    return ERWP;

})(jQuery, window, window.erwpSettings || {});
