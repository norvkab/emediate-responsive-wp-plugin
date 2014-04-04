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
                var clickURL = 'http://' + erwpSettings.defaultJSHost + '/eas?'+erwpSettings.cuParamName+'='+cu+';ty=ct';
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
                var $elem = $('#emediate-fif-'+fifIndex);

                // Collect this ad element for later (when ad needs
                // to be reloaded due to rotation change for example)
                if( this.hasRegisteredBreakPoints ) {
                    this.$fifAds[fifIndex] = $elem;
                }

                // render ad
                this.renderFifAd($elem);
            },

            /**
             * Inserts a fif-ad in given element
             * @param {jQuery} $elem
             */
            renderFifAd : function($elem) {
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
                        ';'+erwpSettings.adQuery;

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
                $.each(this.$fifAds, function(i, $adElem) {
                    ERWP.renderFifAd($adElem);
                });
            },

            /**
             * Function called by an iframe, containing a fif-ad, when the iframe
             * document gets loaded
             *
             * @param {Window} iframeWin
             */
            fifLoaded : function(iframeWin) {
                var $adElem = this.getAdElementFromFifIframe(iframeWin);
                if( this.shouldHideFif(iframeWin, $adElem) ) {
                    this.hideAd($adElem);
                } else {
                    $adElem.addClass('has-ad');
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
             * @param fifWin
             * @returns {Boolean}
             */
            shouldHideFif : function (fifWin, $adElem) {
                var containsEmptyAdTag = function () {
                        if ( !fifWin.body )
                            return false;

                        var hasEmptyAdTag = false,
                            emptyAdTags = (erwpSettings.emptyAdTags || '').split('\n');

                        emptyAdTags.push('<!-- No matching campaign -->');

                        $.each(emptyAdTags, function(i, tag) {
                            var emptyAdTag = $.trim(tag);
                            if( emptyAdTag && fifWin.body.innerHTML.indexOf(emptyAdTag) > -1 ) {
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
