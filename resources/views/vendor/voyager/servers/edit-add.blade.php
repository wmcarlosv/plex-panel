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

                            <div class="col-md-12">
                                <h3>Cantidad de Cuentas (Panel)</h3>
                                <h2><span class="label label-success">{{$dataTypeContent->customers->count()}}</span></h2>
                            </div>
                            @if($edit)
                                <div class="form-group col-md-12">
                                    <label for="">Librerias Asignadas</label>
                                    <select name="libraries[]" class="form-control libraries" multiple>
                                        @foreach($libraries as $library)
                                        <option value="{{$library['Section']['id']}}" @if( in_array($library["Section"]["id"],$libraries_agg) ) selected='selected' @endif>{{$library['Section']['title']}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                        </div><!-- panel-body -->

                        <div class="panel-footer">
                            @section('submit-buttons')
                                <button type="submit" class="btn btn-primary save">{{ __('voyager::generic.save') }}</button>
                            @stop
                            @yield('submit-buttons')
                            @if($edit)
                                @if(is_array($accounts))
                                    <button class="btn btn-success" type="button" id="update-libraries-button">Refrescar Librerias</button>
                                    <button class="btn btn-warning" type="button" id="view-plex-accounts">Ver Cuentas en Plex</button>
                                    <button class="btn btn-info" type="button" id="active-sessions">Ver Sesiones Activas</button>
                                @endif
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
                    <button type="button" class="btn btn-danger" id="confirm_delete">{{ __('voyager::generic.delete_confirm') }}</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete File Modal -->

    @if($edit)
        <div class="modal fade modal-success" id="update-libraries-modal">
            <div class="modal-dialog">
                <div class="modal-content">

                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"
                                aria-hidden="true">&times;</button>
                        <h4 class="modal-title">Refrescar Librerias</h4>
                    </div>

                    <div class="modal-body">
                        <ul class="list-group">
                            @foreach($libraries as $library)
                                <li class="list-group-item"><input type="checkbox" name="libraries[]" value="{{$library['Section']['key']}}"> {{$library['Section']['title']}}</li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" id="save-update-libraries">Actualizar</button>
                        <button type="button" class="btn btn-danger" id="cancel-update-libraries">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade modal-success" id="view-plex-accounts-modal">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">

                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"
                                aria-hidden="true">&times;</button>
                        <h4 class="modal-title">Cuentas en el Servidor</h4>
                    </div>

                    <div class="modal-body">
                        <button class="btn btn-success" type="button" id="import-from-plex-to-panel">Importar al Panel</button>
                        <br />
                        <table class="table table-bordered table-striped">
                            <thead>
                                <th><input type="checkbox" id="check_all"></th>
                                <th>Email</th>
                                <!--<th>Plex UserName</th>-->
                                <th>Inicio</th>
                                <th>Fin</th>
                                <th>Estado</th>
                            </thead>
                            <tbody>
                                <form action="{{ route('import_from_plex') }}" method="POST" id="form-accounts-import">
                                    @method('post')
                                    @csrf
                                    <input type="hidden" name="server_id" value="{{$dataTypeContent->id}}" />
                                    @foreach($accounts as $account)
                                        @php
                                            $customer = \App\Models\Customer::verifyCustomer($account['id']);
                                        @endphp
                                        <tr>
                                            <td>
                                                @if($customer->count() <= 0)
                                                    <input type="checkbox" class="check_individual" data-id="{{$account['id']}}" name="accounts_for_import[]" value="{{json_encode($account)}}">
                                                @endif
                                            </td>
                                            <td>{{ $account['email'] }}</td>
                                            <!--<td>{{ $account['username'] }}</td>-->
                                            <td>
                                                @if($customer->count() <= 0)
                                                    <input type="date" class="form-control" value="{{date('Y-m-d')}}" name="date_from_{{$account['id']}}">
                                                @else
                                                    <center>{{date('d/m/Y',strtotime($customer[0]->date_from))}}</center>
                                                @endif
                                            </td>
                                            <td>
                                                @if($customer->count() <= 0)
                                                    <input type="date" class="form-control" name="date_to_{{$account['id']}}" />
                                                @else
                                                    <center>{{date('d/m/Y',strtotime($customer[0]->date_to))}}</center>
                                                @endif
                                            </td>
                                            <td>
                                                @if($customer->count() > 0)
                                                    @if($customer[0]->invited_id == $account['id'])
                                                        @if($customer[0]->status == 'active')
                                                            <span class="label label-success">Ya Existe</span>
                                                        @else
                                                            <span class="label label-danger">Existe en Plex y esta Inactivo</span>
                                                        @endif
                                                    @endif
                                                @else
                                                    <span class="label label-warning">No Existe</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </form>
                            </tbody>
                        </table>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" id="cancel-view-plex-accounts">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade modal-success" id="active-sessions-modal">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">

                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"
                                aria-hidden="true">&times;</button>
                        <h4 class="modal-title">Sesiones Activas</h4>
                    </div>

                    <div class="modal-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <th>Cover</th>
                                <th>Titulo</th>
                                <th>Dispostivo</th>
                                <th>Usuario</th>
                            </thead>
                            <tbody id="load-sessions">
                                
                            </tbody>
                        </table>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" id="cancel-active-sessions">Salir</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@stop

@section('javascript')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        var params = {};
        var $file;

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

            $('select.libraries').select2();
            $('.toggleswitch').bootstrapToggle();

            $("input[name='token']").attr("type","password");

            $("#active-sessions").click(function(){
                let server_id = '{{$dataTypeContent->id}}';
                let html = "";
                $("#load-sessions").html("<tr><td colspan='4'><center>Cargando Sesiones...</center></td></tr>");
                
                $.get("/api/get-active-sessions/"+server_id, function(response){
                    let sessions = response;
                    if(parseInt(sessions.length) > 0){
                        for(let i=0;i < sessions.length;i++){
                            html+="<tr><td><img src='"+sessions[i].media.cover+"' class='img-thumbnail' style='width:150px; height:150px;' /></td><td><b>"+sessions[i].media.title+"</b></td><td>"+sessions[i].player.ip+" / "+sessions[i].player.device+"</td><td><img src='"+sessions[i].user.avatar+"' style='width:50px; height:50px;' /> "+sessions[i].user.name+"</td></tr>";
                        }
                        $("#load-sessions").html(html);
                    }else{
                        $("#load-sessions").html("<tr><td colspan='4'><center>No se encontraron sesiones activas en este Servidor</center></td></tr>");
                    }
                });

                $("#active-sessions-modal").modal({backdrop:'static', keyboard:false}, 'show');
            });

            $("#cancel-active-sessions").click(function(){
                $("#active-sessions-modal").modal("hide");
            });

            $("#update-libraries-button").click(function(){
                $("#update-libraries-modal").modal({backdrop: 'static', keyboard: false}, 'show');
            });

            $("#cancel-update-libraries").click(function(){
                $("#update-libraries-modal").modal('hide');
            });

            $("#view-plex-accounts").click(function(){
                $("#view-plex-accounts-modal").modal({backdrop: 'static', keyboard: false}, 'show');
            });

            $("#cancel-view-plex-accounts").click(function(){
                $("#view-plex-accounts-modal").modal("hide");
            });

            $("#check_all").click(function(){
                $("input[name='accounts_for_import[]").not(this).prop('checked', this.checked);
            });

            $("#import-from-plex-to-panel").click(function(){
                var count = $("input[name='accounts_for_import[]']:checked").length;
                if(count > 0){
                    if(confirm("Estas seguro de realizar la importacion?")){
                        $("#form-accounts-import").submit();
                        $(this).attr("disabled").text("Importando Cuentas...");
                    } 
                }else{
                    alert("Debes Seleccionar al menos una cuenta para importar!!");
                }
            });

            $("#save-update-libraries").click(function(){
                let libraries = $("input[name='libraries[]']");
                var cont = 0;
                libraries.each(function(){
                    if($(this).prop("checked")){
                        cont++;
                    }
                });

                if(cont > 0){
                    $("#update-libraries-modal").modal('hide');
                    Swal.fire({
                      title: 'Advertencia',
                      text: "Estamos Realizando el Cambio!!",
                      icon: 'warning',
                      showConfirmButton:false,
                      allowOutsideClick: false,
                      confirmButtonText: 'Yes, delete it!'
                    });

                    $.ajax({
                        url:"{{route('update_libraries',$dataTypeContent->id)}}",
                        type: "POST",
                        data: $("input[name='libraries[]']:checked").serialize(),
                        success: function(response){
                        let data = response;
                            if(data.success){
                                Swal.fire({
                                  title: 'Notificacion',
                                  text: data.message,
                                  icon: 'success',
                                  showConfirmButton:true,
                                  allowOutsideClick:false,
                                  confirmButtonText: 'OK'
                                });
                            }else{
                                 Swal.fire({
                                  title: 'Notificacion',
                                  text: data.message,
                                  icon: 'error',
                                  showConfirmButton:true,
                                  confirmButtonText: 'OK'
                                });
                            }
                        }
                    });
                }else{
                    alert("Debes seleccionar al menos una libreria!!");
                }
            });

            @if($edit)
                @if(is_array($accounts))
                    $("input[name='accounts_count']").val("{{count($accounts)}}").attr("readonly","readonly");
                @else
                    alert("Existen problemas en el servidor, verifica que el Email, nombre de usuario y clave sean los correctos!!");
                    $("input[name='accounts_count']").val("0").attr("readonly","readonly");
                @endif
            @else
                $("input[name='accounts_count']").val("0").attr("readonly","readonly");
            @endif

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
        });
    </script>
@stop
