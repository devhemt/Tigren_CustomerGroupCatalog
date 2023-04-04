/**
 * Amasty Groupcat Sample Product Element
 */
define([
    'uiElement'
], function (Element) {
    'use strict';

    return Element.extend({
        defaults: {
            hidePrice: 0,
            hideCart: 0,
            hideWishlist: 0,
            hideCompare: 0
        },

        initObservable: function () {
            this._super().observe([
                'hidePrice',
                'hideCart',
                'hideWishlist',
                'hideCompare'
            ]);

            return this;
        }
    });
});
