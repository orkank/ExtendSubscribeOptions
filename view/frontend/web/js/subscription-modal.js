define([
    'jquery',
    'Magento_Ui/js/modal/modal'
], function ($, modal) {
    'use strict';

    return function (config, element) {
        var options = {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            title: false,
            buttons: [{
                text: $.mage.__('Close'),
                class: 'action-close',
                click: function () {
                    this.closeModal();
                }
            }]
        };

        var popup = modal(options, $(config.target));

        $(element).on('click', function (e) {
            e.preventDefault();
            $(config.target).modal('openModal');
        });
    };
});