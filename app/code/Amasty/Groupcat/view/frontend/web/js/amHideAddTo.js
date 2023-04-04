define([
    "jquery",
    "Magento_Ui/js/modal/modal"
], function ($) {
    $.widget('mage.amHideAddTo', {
        options: {},
        _create: function () {
            if (this.element) {
                var parent = this.element.parents(this.options['parent']);
                if (!parent) {
                    return;
                }
                if (this.options['hide_compare'] === '1') {
                    jQuery(parent[2]).find('a.tocompare').remove();
                }
                if (this.options['hide_wishlist'] === '1') {
                    jQuery(parent[2]).find('a.towishlist').remove();
                }
            }
        }
    });

    return $.mage.amHideAddTo;
});
