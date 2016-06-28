/**
 * Created: 2016-06-25
 * @author Yauhen Saroka <yauhen.saroka@gmail.com>
 */

$(function() {
    'use strict';

    var wsInfoPanel = $('#ws-info-panel');
    var wsInfo = $('#ws-info');
    var uploadsTable = $('#uploads-table');
    var serverUploadProgressContainer = $('#server-upload-progress-container');
    var serverUploadProgressElements = {};

    var conn;

    WSconnect();

    function WSconnect() {
        conn = new WebSocket(getWSBrokerConnection());

        conn.onopen = function(e) {
            wsInfoMessage('Connection established!');
        };

        /**
         * data :
         *  status - progress | success | error
         *  status_message - status message
         *  server - server info array
         *  upload_name - new filename for file on server
         *  original_filename - original file name
         *
         * @param e event
         */
        conn.onmessage = function(e) {
            var data = JSON.parse(e.data);
            if (data.status === 'progress') {
                if (!existsUploadProgress(data.upload_id)) {
                    addUploadProgress(data.upload_id, data.server.scheme + '://' + data.server.host + '' + data.server.path + '/' + data.upload_name);
                }
                setUploadProgressPercent(data.upload_id, data.percent);
            } else if ($.inArray(data.status, ['success', 'error']) >= 0) {
                destroyUploadProgress(data.upload_id);
                var dt = new Date();

                addUploadEntity(
                    dt.getFullYear() + '-' + ('0' + (dt.getMonth() + 1)).slice(-2) + '-' + ('0' + dt.getDate()).slice(-2) + ' ' + ('0' + dt.getHours()).slice(-2) + ':' + ('0' + dt.getMinutes()).slice(-2) + ':' + ('0' + dt.getSeconds()).slice(-2),
                    data.original_filename,
                    data.server.scheme + '://' + data.server.host,
                    (data.server.path + '/' + data.upload_name).replace(/\/{2,}/, '/'),
                    data.status_message,
                    data.status
                );
            }
        };

        conn.onerror = function(e) {
            if (conn.readyState == WebSocket.CLOSED) {
                wsErrorMessage('Error connecting to WS server, reconnect...');
                WSconnect();
            }
        };

        conn.onclose = function(e) {
            if (conn.readyState == WebSocket.CLOSED) {
                wsErrorMessage('WS server closed, reconnect...');
                WSconnect();
            }
        };
    }

    function wsInfoMessage(text) {
        wsInfoPanel.addClass('panel-success');
        wsInfoPanel.removeClass('panel-danger');
        wsInfo.html(text);
    }

    function wsErrorMessage(text) {
        wsInfoPanel.addClass('panel-danger');
        wsInfoPanel.removeClass('panel-success');
        wsInfo.html(text);
    }

    function addUploadProgress(id, serverFile) {
        if (!existsUploadProgress(id)) {
            serverUploadProgressContainer.prepend('<div class="row"><div class="col-md-5"><div class="progress shadow"><div id="' + id + '" class="progress-bar progress-bar-striped" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"><span class="sr-only">0% Complete</span></div></div></div><div class="col-md-7"><span class="upload-label shadow">' + serverFile + '</span></div></div>');
            serverUploadProgressElements[id] = $('#' + id);
        }
    }

    function destroyUploadProgress(id) {
        if (existsUploadProgress(id)) {
            serverUploadProgressElements[id].parent().parent().parent().remove();
            delete serverUploadProgressElements[id];
        }
    }

    function setUploadProgressPercent(id, percent) {
        if (existsUploadProgress(id)) {
            serverUploadProgressElements[id].width(percent + '%');
        }
    }

    function existsUploadProgress(id) {
        return serverUploadProgressElements.hasOwnProperty(id);
    }

    function addUploadEntity(date, originalFileName, server, uploadedFile, message, status) {
        if (uploadsTable.hasClass('hide')) {
            uploadsTable.removeClass('hide');
        }
        var tbody = uploadsTable.find('tbody');

        tbody.prepend('\
        <tr> \
        <td>' + date + '</td> \
        <td>' + originalFileName + '</td> \
        <td>' + server + '</td> \
        <td>' + uploadedFile + '</td> \
        <td>' + message + '</td> \
        </tr>\
        ');

        var addedTr = tbody.children().first();
        var statusColor = (status == 'success' ? '#D5EBCC' : '#FDBEBB');
        var defaultColor = '#FFFFFF';

        for (var i = 1; i <= 3; i++) {
            addedTr.animate({
                backgroundColor: i % 2 ? statusColor : defaultColor
            }, 500);
        }
    }
});