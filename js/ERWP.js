var ERWP = (function($, erwpSettings) {

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
             * This array will be populated while the page gets rendered
             * by calling ERWP.fif(...)
             *
             * @var {jQuery[]}
             */
            $fifAds : [],

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

                // Collect this ad element for later (when ad gets
                // reloaded due to rotation change for example)
                if( this.hasRegisteredBreakPoints ) {
                    this.$fifAds.push($elem);
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
                    cu = $elem.attr('data-bp-'+this.breakPoint.min+'-'+this.breakPoint.max);
                }
                if( !cu ) {
                    cu = $elem.attr('data-cu');
                }

                if( $elem.attr('data-current-cu') != cu ) {

                    $elem.html(''); // clear possibly previous ad

                    $elem.attr('data-current-cu', cu);

                    src = 'http://'+erwpSettings.defaultJSHost+'/eas?cre=mu;js=y;target=_blank;'+erwpSettings.cuParamName+'='+cu+
                        ';'+erwpSettings.adQuery;

                    // Load fif
                    window.EAS_load_fif('emediate-fif-'+$elem.attr('data-index'), erwpSettings.fifHtmlFile, src, width, height);

                    $win.trigger('erwpFifCreated', [src, $elem]);
                }
            },

            /**
             * Figures out the break point for given window width
             * @param {Number} windowWidth
             * @return {Object|Boolean} Returns false if not suitable break point could be found
             */
            getBreakPoint : function(windowWidth) {
                var current = false;
                $.each(erwpSettings.breakPoints, function(i, breakPoint) {
                    if( windowWidth >= breakPoint.min && windowWidth < breakPoint.max ) {
                        current = breakPoint;
                        return false;
                    }
                });
                return current;
            },

            /**
             * Iterate through all fif-ad elements and re-insert fif-ad
             */
            reloadFiFAds : function() {
                $.each(ERWP.$fifAds, function(i, $adElem) {
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
                if( this.shouldHideFif(iframeWin) ) {
                    var $adElem = this.getAdElementFromFifIframe(iframeWin);
                    $adElem.html('');
                    $win.trigger('erwpAdHidden', [$adElem]);
                }
            },

            /**
             * @param {Window} iframeWin
             * @return {jQuery}
             */
            getAdElementFromFifIframe : function(iframeWin) {
                var cu = iframeWin.frameElement.EAS_src.split(erwpSettings.cuParamName+'=')[1].split(';')[0];
                return $('.emediate-ad[data-current-cu="'+cu+'"]');
            },

            /**
             * @param fifWin
             * @returns {Boolean}
             */
            shouldHideFif : function (fifWin) {
                var hasNoMatchingCampaignComment = function () {
                        if ( !fifWin.body ) return false;
                        return (-1 !== fifWin.body.innerHTML.indexOf('<!-- No matching campaign -->'));
                    },
                    containsAllScriptNodes = function () {
                        // No comment but no other elements either, check if div-container has anything more than the iframe
                        return fifWin.document.querySelectorAll("body script").length === fifWin.document.querySelectorAll("body *").length;
                    };

                return hasNoMatchingCampaignComment() ||
                        containsAllScriptNodes() ||
                        $win.trigger('erwpShouldHideAd', [fifWin]) === true;
            }
        };


    if( ERWP.hasRegisteredBreakPoints ) {

        // figure out current break point
        ERWP.breakPoint = ERWP.getBreakPoint($win.width());

        // Capture window width and figure out current break-point
        // when window size changes. If we have a new break-point
        // all fif ads gets reloaded
        $win.on('resize', function() {
            var breakPoint = ERWP.getBreakPoint($win.width());
            if( breakPoint != ERWP.breakPoint ) {
                // break point has changed
                ERWP.breakPoint = breakPoint;
                ERWP.reloadFiFAds();
                $win.trigger('erwpBreakPointChange');
            }
        });
    }

    return ERWP;

})(jQuery, window.erwpSettings || {});