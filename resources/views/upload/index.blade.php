@extends('main')

@section('content')
    <div class="content">
        <div class="row">
            <div class="col-md-9">
                <div class="row">
                    <div class="col-md-5">
                        <form id="upload-form" action="{{ route('upload') }}" method="post" enctype="multipart/form-data">
                            {{ csrf_field() }}
                            <div class="form-group">
                                <input type="file" name="file" class="btn btn-default" value="Select file" />
                            </div>
                            <div class="form-group">
                                <div class="col-md-3">
                                    <input type="submit" name="submit" class="btn btn-success shadow" value="Upload" />
                                </div>
                                <div class="col-md-9">
                                    <div id="upload-progress-container" class="progress shadow hide">
                                        <div id="upload-progress" class="progress-bar progress-bar-success notransition" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100">
                                            <span class="sr-only">40% Complete (success)</span></div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-7">
                        <div id="upload-error-panel" class="panel panel-danger shadow @if (count($storageErrors) == 0) hide @endif">
                            <div class="panel-heading">
                                <h3 class="panel-title">File upload error.</h3>
                            </div>
                            <div id="upload-error-text" class="panel-body">
                                @if (@count($storageErrors['file']) > 0)
                                    @foreach($storageErrors['file'] as $storageError)
                                        <div class="error">{{ $storageError }}</div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div id="server-upload-progress-container" class="row"></div>
            </div>
            <div class="col-md-3">
                <div id="ws-info-panel" class="panel panel-success shadow">
                    <div class="panel-heading">
                        <h3 class="panel-title">WebSocket server status</h3>
                    </div>
                    <div id="ws-info" class="panel-body">
                        Wait...
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <table id="uploads-table" class="table shadow @if (count($uploadEntities) == 0) hide @endif">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Original file</th>
                    <th>Server</th>
                    <th>Uploaded file</th>
                    <th>Message</th>
                </tr>
                </thead>
                <tbody>
                @foreach($uploadEntities as $uploadEntity)
                    <tr class="{{ $uploadEntity->status ? 'success' : 'error' }}">
                        <td>{{ $uploadEntity->created_at }}</td>
                        <td>{{ $uploadEntity->original_name }}</td>
                        <td>{{ $uploadEntity->server }}</td>
                        <td>{{ $uploadEntity->upload_name }}</td>
                        <td>{{ $uploadEntity->status_message }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('scripts')
    @parent
    <script src="/assets/js/websocket.js"></script>
    <script src="/assets/js/upload-form.js"></script>
    <script>
        function getWSBrokerConnection() {
            return 'ws://{!! config('daemons.wsserver.host') !!}:{!! config('daemons.wsserver.port') !!}/{!! config('daemons.wsserver.path') !!}';
        }
    </script>
@endsection