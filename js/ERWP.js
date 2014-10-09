var ERWP = (function($, window, erwpSettings) {

    'use strict';

    var $win = $(window),
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
             * Prints out an emediate ad using composed javascript
             * @param {String} src
             * @param {String|Number} cu
             */
            composed : function(src, cu) {
                var clickURL = 'http://' + erwpSettings.defaultJSHost + '/eas?'+erwpSettings.cuParamName+'='+cu+';ty=ct' +
                                ( erwpSettings.adQuery ? ';'+erwpSettings.adQuery : '');

                document.write(
                    '<script src="'+src+';'+erwpSettings.adQuery+'"></script>'+
                    '<noscript><a target="_blank" href="'+clickURL+'">'+
                        '<img src="'+src+';cre=img" alt="emediate" /></a></noscript>'
                );
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
                    self.renderFifAd($elem);
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
                            $(window).trigger('geolocation_failed', e.code);
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
                            $(window).trigger('geolocation_found');
                        }
                        erwpSettings.coords = loc.coords;
                        self.renderFifAd($elem, ';lat='+loc.latitude+';lon='+loc.longitude+';');
                    });

                    setTimeout(function() {
                        if (timeout !== null) {
                            timeout = null;
                            $(window).trigger('geolocation_failed', -1);
                            normal_render();
                        }
                    }, timeout);

                }
                else normal_render();
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

                    $win.trigger('erwpAdCreated', [src, $elem, cu, this.breakPoint]);
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
                    _this = this;

                if( this.shouldHideFif(iframeWin, $adElem) ) {
                    this.hideAd($adElem);
                } else {
                    $adElem.addClass('has-ad');
                    this.resizeIframeToDocumentSize($adElem);
                    setTimeout(function() {
                        _this.resizeIframeToDocumentSize($adElem);
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
                    .removeClass('has-ad')
                    .attr('data-current-cu', '')
                    .html('');

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
             */
            resizeIframeToDocumentSize : function($adElem) {
                var $iframe = $adElem.find('iframe'),
                    iframeHeight = $iframe.height(),
                    iframeDocHeight = $iframe.contents().outerHeight();

                if( iframeDocHeight != iframeHeight ) {
                    $iframe.height(iframeDocHeight);
                }
            },

            /**
             * @param {Object} fifWin
             * @param {jQuery} $adElem
             * @returns {Boolean}
             */
            shouldHideFif : function (fifWin, $adElem) {

                var containsEmptyAdTag = function () {
                        var body = fifWin.body || fifWin.document.body;
                        if ( !body )
                            return false;

                        var hasEmptyAdTag = false,
                            emptyAdTags = (erwpSettings.emptyAdTags || '').split('\n');

                        emptyAdTags.push('<!-- No matching campaign -->'); // emediates own no-ad-tag

                        $.each(emptyAdTags, function(i, tag) {
                            var emptyAdTag = $.trim(tag);
                            if( emptyAdTag && body.innerHTML.indexOf(emptyAdTag) > -1 ) {
                                hasEmptyAdTag = true;
                                return false;
                            }
                        });

                        return hasEmptyAdTag;
                    },
                    containsAllScriptNodes = function () {
                        // No comment but no other elements either, check if div-container has anything more than the iframe
                        return fifWin.document.querySelectorAll("body script").length === fifWin.document.querySelectorAll("body *").length;
                    };

                return containsEmptyAdTag() ||
                        containsAllScriptNodes() ||
                        $win.trigger('erwpAdLoaded', [fifWin, $adElem, this.breakPoint]) === false;
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
