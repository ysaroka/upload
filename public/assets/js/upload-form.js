/**
 * Created: 2016-06-25
 * @author Yauhen Saroka <yauhen.saroka@gmail.com>
 */

$(function() {
    'use strict';

    var uploadForm = $('#upload-form');
    var uploadProgressContainer = $('#upload-progress-container');
    var uploadProgress = $('#upload-progress');
    var uploadErrorPanel = $('#upload-error-panel');
    var uploadErrorText = $('#upload-error-text');

    uploadForm.submit(function(event) {
        if (uploadForm.find('input[name=file]').val() === '') {
            resetErrorMessages();
            addErrorMessage('File is not selected.');
            return false;
        }
    });

    // bind form using ajaxForm
    uploadForm.ajaxForm({
        dataType: 'json',
        success: processJson,
        uploadProgress: function(event, position, total, percentComplete) {
            var percentVal = percentComplete + '%';
            uploadProgress.width(percentVal);
            //percent.html(percentVal);
        },
        beforeSubmit: function() {
            uploadForm.find('input[type=submit]').attr('disabled', 'disabled');
            uploadProgressContainer.removeClass('hide');
            uploadProgress.width('0%').attr("aria-valuenow", 0);
        }
    });

    function processJson(data) {
        uploadForm.find('input[type=submit]').removeAttr('disabled');
        uploadProgressContainer.addClass('hide');

        // 'data' is the json object returned from the server
        if (data.uploadStatus) {
            resetErrorMessages();
        } else if (typeof data.storageErrors.file === 'object') {
            resetErrorMessages();
            for (var key in data.storageErrors.file) {
                addErrorMessage(data.storageErrors.file[key]);
            }
        }
    }

    function addErrorMessage(message) {
        if (uploadErrorPanel.hasClass('hide')) {
            uploadErrorPanel.removeClass('hide');
        }
        uploadErrorText.html(uploadErrorText.html() + '<div class="error">' + message + '</div>');
    }

    function resetErrorMessages() {
        uploadErrorPanel.addClass('hide');
        uploadErrorText.html('');
    }

});