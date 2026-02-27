/**
 * Admin JavaScript for Natalie Auto Poster
 */
(function ($) {
    'use strict';

    var NAP = {

        init: function () {
            this.bindEvents();
            this.initProviderToggle();
            this.initStorageToggle();
        },

        bindEvents: function () {
            // Manual fetch button
            $('#nap-btn-manual-fetch').on('click', this.handleManualFetch.bind(this));

            // Process single article
            $('#nap-btn-process-single').on('click', this.handleProcessSingle.bind(this));

            // Test connection buttons
            $(document).on('click', '.nap-test-btn', this.handleTestConnection.bind(this));

            // Retry buttons
            $(document).on('click', '.nap-retry-btn', this.handleRetry.bind(this));

            // Provider select change
            $('#nap_translation_provider').on('change', this.handleProviderChange.bind(this));

            // Storage select change
            $('#nap_image_storage').on('change', this.handleStorageChange.bind(this));
        },

        /**
         * Initialize provider settings visibility
         */
        initProviderToggle: function () {
            var provider = $('#nap_translation_provider').val();
            if (provider) {
                this.showProviderSettings(provider);
            }
        },

        /**
         * Initialize storage settings visibility
         */
        initStorageToggle: function () {
            var storage = $('#nap_image_storage').val();
            if (storage) {
                this.showStorageSettings(storage);
            }
        },

        /**
         * Handle provider change
         */
        handleProviderChange: function (e) {
            this.showProviderSettings($(e.target).val());
        },

        /**
         * Show provider settings panel
         */
        showProviderSettings: function (provider) {
            $('.nap-provider-settings').removeClass('active');
            if (provider && provider !== 'wordpress') {
                $('#settings-' + provider).addClass('active');
            }
        },

        /**
         * Handle storage change
         */
        handleStorageChange: function (e) {
            this.showStorageSettings($(e.target).val());
        },

        /**
         * Show storage settings panel
         */
        showStorageSettings: function (storage) {
            $('.nap-storage-settings').removeClass('active');
            if (storage && storage !== 'wordpress') {
                $('#storage-' + storage).addClass('active');
            }
        },

        /**
         * Handle manual fetch
         */
        handleManualFetch: function (e) {
            e.preventDefault();

            var $btn = $('#nap-btn-manual-fetch');
            var $status = $('#nap-fetch-status');
            var source = $('#nap-manual-source').val();
            var limit = $('#nap-manual-limit').val();

            $btn.prop('disabled', true).text(napAdmin.strings.fetching);
            $status.removeClass('success error').text('');

            $.ajax({
                url: napAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'nap_manual_fetch',
                    nonce: napAdmin.nonce,
                    source: source,
                    limit: limit
                },
                success: function (response) {
                    if (response.success) {
                        $status.addClass('success').text(response.data.message);
                        // Reload page after 2 seconds to show updated stats
                        setTimeout(function () {
                            window.location.reload();
                        }, 2000);
                    } else {
                        $status.addClass('error').text(response.data.message || napAdmin.strings.error);
                    }
                },
                error: function () {
                    $status.addClass('error').text(napAdmin.strings.error);
                },
                complete: function () {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Fetch Now');
                }
            });
        },

        /**
         * Handle process single article
         */
        handleProcessSingle: function (e) {
            e.preventDefault();

            var $btn = $('#nap-btn-process-single');
            var $status = $('#nap-single-status');
            var url = $('#nap-single-url').val().trim();
            var source = $('#nap-single-source').val();

            if (!url) {
                $status.addClass('error').text('Please enter an article URL');
                return;
            }

            $btn.prop('disabled', true).text(napAdmin.strings.processing);
            $status.removeClass('success error').text('');

            $.ajax({
                url: napAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'nap_process_single',
                    nonce: napAdmin.nonce,
                    url: url,
                    source: source
                },
                success: function (response) {
                    if (response.success) {
                        var msg = response.data.message;
                        if (response.data.edit_url) {
                            msg += ' <a href="' + response.data.edit_url + '" target="_blank">Edit Post</a>';
                        }
                        $status.addClass('success').html(msg);
                        $('#nap-single-url').val('');
                    } else {
                        $status.addClass('error').text(response.data.message || napAdmin.strings.error);
                    }
                },
                error: function () {
                    $status.addClass('error').text(napAdmin.strings.error);
                },
                complete: function () {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-media-text"></span> Process Article');
                }
            });
        },

        /**
         * Handle test connection
         */
        handleTestConnection: function (e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var $result = $btn.siblings('.nap-test-result');
            var provider = $btn.data('provider');

            $btn.prop('disabled', true).text(napAdmin.strings.testing);
            $result.removeClass('success error').text('');

            $.ajax({
                url: napAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'nap_test_connection',
                    nonce: napAdmin.nonce,
                    provider: provider
                },
                success: function (response) {
                    if (response.success) {
                        $result.addClass('success').text(
                            '✓ ' + response.data.message + ' Test: "' + response.data.test_output + '"'
                        );
                    } else {
                        $result.addClass('error').text('✗ ' + (response.data.message || napAdmin.strings.error));
                    }
                },
                error: function () {
                    $result.addClass('error').text('✗ ' + napAdmin.strings.error);
                },
                complete: function () {
                    $btn.prop('disabled', false).text('Test Connection');
                }
            });
        },

        /**
         * Handle retry failed article
         */
        handleRetry: function (e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var url = $btn.data('url');
            var source = $btn.data('source');

            if (!confirm(napAdmin.strings.confirm)) {
                return;
            }

            $btn.prop('disabled', true).text(napAdmin.strings.processing);

            $.ajax({
                url: napAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'nap_process_single',
                    nonce: napAdmin.nonce,
                    url: url,
                    source: source
                },
                success: function (response) {
                    if (response.success) {
                        alert(response.data.message);
                        window.location.reload();
                    } else {
                        alert(response.data.message || napAdmin.strings.error);
                        $btn.prop('disabled', false).text('Retry');
                    }
                },
                error: function () {
                    alert(napAdmin.strings.error);
                    $btn.prop('disabled', false).text('Retry');
                }
            });
        }
    };

    // Initialize on DOM ready
    $(document).ready(function () {
        NAP.init();
    });

})(jQuery);
