/* AH Arabic Translation — Frontend Language Script */
(function($) {
    'use strict';

    var AHLang = {
        current: ahLang.current || 'en',
        isRTL:   ahLang.isRTL   || false,

        init: function() {
            this.applyDirection();
            this.bindSwitcher();
            this.handleAjaxCart();
        },

        applyDirection: function() {
            if ( this.isRTL ) {
                $('html').attr('dir', 'rtl').attr('lang', 'ar');
                $('body').addClass('ah-rtl').removeClass('ah-ltr');
            } else {
                $('html').attr('dir', 'ltr').attr('lang', 'en');
                $('body').addClass('ah-ltr').removeClass('ah-rtl');
            }
        },

        bindSwitcher: function() {
            $(document).on('click', '.ah-lang-item', function(e) {
                // Let the href handle the redirect — no need to prevent default
                // Just add a loading class for UX feedback
                var $switcher = $(this).closest('.ah-lang-switcher');
                $switcher.addClass('ah-switching');
                $('<span class="ah-loading"> ...</span>').appendTo($switcher);
            });
        },

        handleAjaxCart: function() {
            // Re-apply RTL after WooCommerce AJAX cart fragments reload
            $(document.body).on('wc_fragments_refreshed wc_fragments_loaded', function() {
                if ( AHLang.isRTL ) {
                    AHLang.applyDirection();
                }
            });
        }
    };

    $(document).ready(function() {
        AHLang.init();
    });

})(jQuery);
