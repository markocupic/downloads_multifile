/**
 * Created by Marko on 08.06.2016.
 */
(function ($) {
    $().ready(function () {

        if ($('.multifile-downloads-link-container').length) {
            // Download language data from xhr
            if (typeof ceDownloadsLang === 'undefined') {
                ceDownloadsLang = {};
                $.ajax({
                        url: window.location.href,
                        type: 'get',
                        dataType: 'json',
                        data: {
                            'loadLanguageData': 'true',
                            'ceDownloads': 'true'
                        }
                    })
                    .done(function (resp) {
                        if (resp.done == 'true') {
                            $.each(resp, function (index, value) {
                                ceDownloadsLang[index] = value;
                            });
                        }
                    })
                    .fail(function () {
                        //
                    })
                    .always(function () {
                        //
                    });
            }
        }


        // Init file download
        $('.multifile-downloads-button-container button').click(function (e) {
            e.preventDefault();
            var button = $(this);
            var list = button.closest('.ce_downloads').find('ul').eq(0);
            var files = [];
            $(list).find('input').each(function () {
                var input = $(this);
                if ($(this).is(':checked')) {
                    files.push(input.attr('value'));
                }
            });

            if (files.length > 0) {
                var path = window.location.href + '?zipDownload=true&files=' + files.join();
                window.location.href = path;
            } else {
                alert(ceDownloadsLang.pleaseSelectOneFile)
            }

        });


        // Toggle checkboxes, select-all link and download button
        $('.multifile-downloads-link-container a').click(function (e) {
            e.preventDefault();
            $(this).closest('.ce_downloads').find('ul').toggleClass('show-checkbox');
            $(this).closest('.ce_downloads').find('.multifile-downloads-button-container').toggle();
            $(this).closest('.ce_downloads').find('.multifile-downloads-select-all-container').toggle();
        });

        // Disable links
        $('.ce_downloads ul li a').click(function (e) {
            var link = $(this);
            if ($(this).closest('ul').hasClass('show-checkbox')) {
                e.preventDefault();
            }
        });

        // Select all checkboxes
        $('.multifile-downloads-select-all-container a').click(function (e) {
            e.preventDefault();
            $(this).toggleClass('selected');
            if ($(this).hasClass('selected')) {
                $(this).closest('.ce_downloads').find('ul li input[type="checkbox"]').prop('checked', true);
            } else {
                $(this).closest('.ce_downloads').find('ul li input[type="checkbox"]').prop('checked', false);
            }
        });
    });

})(jQuery);