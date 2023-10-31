@php
    $edit = !is_null($dataTypeContent->getKey());
    $add  = is_null($dataTypeContent->getKey());
@endphp

@extends('voyager::master')

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@stop

@section('page_title', __('voyager::generic.'.($edit ? 'edit' : 'add')).' '.$dataType->getTranslatedAttribute('display_name_singular'))

@section('page_header')
    <h1 class="page-title">
        <i class="{{ $dataType->icon }}"></i>
        {{ __('voyager::generic.'.($edit ? 'edit' : 'add')).' '.$dataType->getTranslatedAttribute('display_name_singular') }}
    </h1>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')
    <div class="page-content edit-add container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <!-- form start -->
                    <form role="form"
                            class="form-edit-add"
                            action="{{ $edit ? route('voyager.'.$dataType->slug.'.update', $dataTypeContent->getKey()) : route('voyager.'.$dataType->slug.'.store') }}"
                            method="POST" enctype="multipart/form-data">
                        <!-- PUT Method if we are editing -->
                        @if($edit)
                            {{ method_field("PUT") }}
                        @endif

                        <!-- CSRF TOKEN -->
                        {{ csrf_field() }}

                        <div class="panel-body">

                            @if (count($errors) > 0)
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <!-- Adding / Editing -->
                            @php
                                $dataTypeRows = $dataType->{($edit ? 'editRows' : 'addRows' )};
                            @endphp

                            @foreach($dataTypeRows as $row)
                                <!-- GET THE DISPLAY OPTIONS -->
                                @php
                                    $display_options = $row->details->display ?? NULL;
                                    if ($dataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')}) {
                                        $dataTypeContent->{$row->field} = $dataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')};
                                    }
                                @endphp
                                @if (isset($row->details->legend) && isset($row->details->legend->text))
                                    <legend class="text-{{ $row->details->legend->align ?? 'center' }}" style="background-color: {{ $row->details->legend->bgcolor ?? '#f0f0f0' }};padding: 5px;">{{ $row->details->legend->text }}</legend>
                                @endif

                                <div class="form-group @if($row->type == 'hidden') hidden @endif col-md-{{ $display_options->width ?? 12 }} {{ $errors->has($row->field) ? 'has-error' : '' }}" @if(isset($display_options->id)){{ "id=$display_options->id" }}@endif>
                                    {{ $row->slugify }}
                                    <label class="control-label" for="name">{{ $row->getTranslatedAttribute('display_name') }}</label>
                                    @include('voyager::multilingual.input-hidden-bread-edit-add')
                                    @if ($add && isset($row->details->view_add))
                                        @include($row->details->view_add, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $dataTypeContent->{$row->field}, 'view' => 'add', 'options' => $row->details])
                                    @elseif ($edit && isset($row->details->view_edit))
                                        @include($row->details->view_edit, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $dataTypeContent->{$row->field}, 'view' => 'edit', 'options' => $row->details])
                                    @elseif (isset($row->details->view))
                                        @include($row->details->view, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $dataTypeContent->{$row->field}, 'action' => ($edit ? 'edit' : 'add'), 'view' => ($edit ? 'edit' : 'add'), 'options' => $row->details])
                                    @elseif ($row->type == 'relationship')
                                        @include('voyager::formfields.relationship', ['options' => $row->details])
                                    @else
                                        {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!}
                                    @endif

                                    @if($row->field == "email")
                                        <button class="btn btn-info" id="generate-email" type="button">Generar Email</button>
                                    @endif

                                    @if($row->field == "password")
                                        <button class="btn btn-info" id="generate-password" type="button">Generar Clave</button>
                                    @endif

                                    @foreach (app('voyager')->afterFormFields($row, $dataType, $dataTypeContent) as $after)
                                        {!! $after->handle($row, $dataType, $dataTypeContent) !!}
                                    @endforeach
                                    @if ($errors->has($row->field))
                                        @foreach ($errors->get($row->field) as $error)
                                            <span class="help-block">{{ $error }}</span>
                                        @endforeach
                                    @endif
                                </div>
                            @endforeach

                            @if($edit)
                                <div class="col-md-12">
                                    @if($dataTypeContent->status == "active")
                                    <h3>Estado actual:</h3> 
                                    <h2><label for="" class="label label-success">Activo</label></h2>
                                @else
                                    <h3>Estado actual:</h3> 
                                    <h2><label for="" class="label label-danger">Inactivo</label></h2>
                                @endif
                                </div>

                                <div class="col-md-12">
                                    <h3>Servidor actual:</h3> 
                                    <h2><label for="" class="label label-success">{{ $dataTypeContent->server->name }}</label></h2>
                                </div>
                                
                            @endif

                        </div><!-- panel-body -->

                        <div class="panel-footer">
                            @section('submit-buttons')
                                <button type="submit" class="btn btn-primary save">{{ __('voyager::generic.save') }}</button>
                            @stop
                            @yield('submit-buttons')
                            @if($edit)
                                <button type="button" class="btn btn-success" id="extend_membership">Extender Membresia</button>
                            @endif
                        </div>
                    </form>

                    <div style="display:none">
                        <input type="hidden" id="upload_url" value="{{ route('voyager.upload') }}">
                        <input type="hidden" id="upload_type_slug" value="{{ $dataType->slug }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-danger" id="confirm_delete_modal">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"
                            aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><i class="voyager-warning"></i> {{ __('voyager::generic.are_you_sure') }}</h4>
                </div>

                <div class="modal-body">
                    <h4>{{ __('voyager::generic.are_you_sure_delete') }} '<span class="confirm_delete_name"></span>'</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                    <button type="button" class="btn btn-success" id="confirm_delete">{{ __('voyager::generic.delete_confirm') }}</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete File Modal -->

        @if($edit)
        <div class="modal fade modal-success" id="extend_modal">
            <div class="modal-dialog">
                <form id="extend_form" action="{{ route('extend_membership') }}" method="POST">
                    @method('PUT')
                    @csrf
                    <input type="hidden" name="customer_id" value="{{ $dataTypeContent->id }}">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal"
                                    aria-hidden="true">&times;</button>
                            <h4 class="modal-title"><i class="voyager-question"></i> Extender Membresia</h4>
                        </div>

                        <div class="modal-body">
                            <div class="form-group">
                                <label for="">Servidor:</label>
                                <select name="plexserver" required class="form-control">
                                    <option value="">-</option>
                                    @foreach($servers as $server)
                                        <option value="{{$server->id}}" @if($dataTypeContent->server_id == $server->id) selected='selected' @endif>{{$server->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="">Duracion:</label>
                                <select name="duration" required class="form-control">
                                    <option value="">-</option>
                                    @foreach($durations as $duration)
                                        <option data-months='{{$duration->months}}' value="{{$duration->id}}">{{$duration->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="">Ultima Fecha Hasta:</label>
                                <input type="date" class="form-control" readonly value="{{$dataTypeContent->date_to}}" />
                            </div>
                            <div class="form-group">
                                <label for="">Fecha a Extender:</label>
                                <input type="date" class="form-control" require name="to" readonly id="to" />
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success">Confirmar</button>
                            <button type="button" class="btn btn-danger" id="cancel_extend_modal">Cancelar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
@stop

@section('javascript')
    <script>
        var params = {};
        var $file;

        function getDomainName() {
          return window.location.hostname;
        }

        function formatDateToDDMMYYYYHMS() {
          const now = new Date();
          const day = String(now.getDate()).padStart(2, '0');
          const month = String(now.getMonth() + 1).padStart(2, '0'); // Month is zero-based
          const year = String(now.getFullYear());
          const hours = String(now.getHours()).padStart(2, '0');
          const minutes = String(now.getMinutes()).padStart(2, '0');
          const seconds = String(now.getSeconds()).padStart(2, '0');
          const formattedDate = day + month + year + hours + minutes + seconds;
          return formattedDate;
        }

        // Function to generate an email address with the current time and domain name
        function generateEmail() {
          const domainName = getDomainName();
          const currentTime = formatDateToDDMMYYYYHMS();
          const email = `cuenta${currentTime}@${domainName}`;
          return email;
        }

        function generateStrongPassword() {
          const length = 15;
          const uppercaseChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
          const lowercaseChars = 'abcdefghijklmnopqrstuvwxyz';
          const numberChars = '0123456789';
          const specialChars = '!@#$%^&*';

          const allChars = uppercaseChars + lowercaseChars + numberChars + specialChars;

          let password = '';
          for (let i = 0; i < length; i++) {
            const randomIndex = Math.floor(Math.random() * allChars.length);
            password += allChars[randomIndex];
          }

          return password;
        }

        function deleteHandler(tag, isMulti) {
          return function() {
            $file = $(this).siblings(tag);

            params = {
                slug:   '{{ $dataType->slug }}',
                filename:  $file.data('file-name'),
                id:     $file.data('id'),
                field:  $file.parent().data('field-name'),
                multi: isMulti,
                _token: '{{ csrf_token() }}'
            }

            $('.confirm_delete_name').text(params.filename);
            $('#confirm_delete_modal').modal('show');
          };
        }

        $('document').ready(function () {
            $('.toggleswitch').bootstrapToggle();
            var today = new Date();
            $("input[name='date_from']").val('<?=date('Y-m-d')?>').attr("readonly","readonly");
            $("input[name='date_to']").attr("readonly","readonly");

            $("#generate-email").click(function(){
                $("input[name='email']").val(generateEmail());
            });
           
            @if(Auth::user()->role_id == 1 || Auth::user()->role_id == 4 || Auth::user()->role_id == 6)
                $("input[name='date_to']").removeAttr("readonly");
            @endif


            $("#generate-password").click(function(){
                $("input[name='password']").val(generateStrongPassword());
            });

            $("select[name='duration']").change(function(){
                if($(this).val()){
                    let ms = $(this).children("option:selected").attr("data-months");
                    @php
                        $current_date = date('Y-m-d');
                        $last_date_to = $dataTypeContent->date_to;
                        $the_date = "";
                        if(strtotime($current_date) > strtotime($last_date_to)){
                            $the_date = $current_date;
                        }else{
                            $the_date = $last_date_to;
                        }
                    @endphp
                    let current_date_to = '{{$the_date}}';
                    $.get('/api/get-extend-month-durations/'+current_date_to+'/'+ms, function(response){
                        if(response.date){
                            $("input[name='to']").val(response.date);
                        }else{
                            $("input[name='to']").val("");
                        }
                    });
                }else{
                    $("input[name='to']").val("");
                }
                
            });

            $("select[name='duration_id']").change(function(){
                let id = $(this).val();
                if(id){
                   $.get("/api/get-months-duration/"+id, function(response){
                        let data = response;
                        $("input[name='date_to']").val(data.new_date);

                   }); 
               }else{
                $("input[name='date_to']").val("");
               }
                
            });

            //Init datepicker for date fields if data-datepicker attribute defined
            //or if browser does not handle date inputs
            $('.form-group input[type=date]').each(function (idx, elt) {
                if (elt.hasAttribute('data-datepicker')) {
                    elt.type = 'text';
                    $(elt).datetimepicker($(elt).data('datepicker'));
                } else if (elt.type != 'date') {
                    elt.type = 'text';
                    $(elt).datetimepicker({
                        format: 'L',
                        extraFormats: [ 'YYYY-MM-DD' ]
                    }).datetimepicker($(elt).data('datepicker'));
                }
            });

            @if ($isModelTranslatable)
                $('.side-body').multilingual({"editing": true});
            @endif

            $('.side-body input[data-slug-origin]').each(function(i, el) {
                $(el).slugify();
            });

            $('.form-group').on('click', '.remove-multi-image', deleteHandler('img', true));
            $('.form-group').on('click', '.remove-single-image', deleteHandler('img', false));
            $('.form-group').on('click', '.remove-multi-file', deleteHandler('a', true));
            $('.form-group').on('click', '.remove-single-file', deleteHandler('a', false));

            $('#confirm_delete').on('click', function(){
                $.post('{{ route('voyager.'.$dataType->slug.'.media.remove') }}', params, function (response) {
                    if ( response
                        && response.data
                        && response.data.status
                        && response.data.status == 200 ) {

                        toastr.success(response.data.message);
                        $file.parent().fadeOut(300, function() { $(this).remove(); })
                    } else {
                        toastr.error("Error removing file.");
                    }
                });

                $('#confirm_delete_modal').modal('hide');
            });

            $('[data-toggle="tooltip"]').tooltip();

            @if($edit)
                $("select[name='status'], input[name='date_from'], select[name='duration_id'], select[name='server_id']").parent().hide();
                $("#generate-password").hide();
                $("input[name='email'], input[name='password']").attr("readonly","readonly");

                $("#extend_membership").click(function(){
                    $("#extend_modal").modal('show');
                });

                $("#cancel_extend_modal").click(function(){
                    $("input[name='to']").val("");
                    $("select[name='duration']").val("");
                    $("select[name='server']").val('{{$dataTypeContent->server_id}}');
                    $("#extend_modal").modal('hide');
                });
            @endif
        });
    </script>
@stop
