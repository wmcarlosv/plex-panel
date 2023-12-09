@extends('voyager::master')

@section('page_title', __('voyager::generic.viewing').' '.$dataType->getTranslatedAttribute('display_name_plural'))

@section('page_header')
    <div class="container-fluid">
        <h1 class="page-title">
            <i class="{{ $dataType->icon }}"></i> {{ $dataType->getTranslatedAttribute('display_name_plural') }}
        </h1>
        @can('add', app($dataType->model_name))
            <a href="{{ route('voyager.'.$dataType->slug.'.create') }}" class="btn btn-success btn-add-new">
                <i class="voyager-plus"></i> <span>{{ __('voyager::generic.add_new') }}</span>
            </a>
        @endcan
        @can('delete', app($dataType->model_name))
            @include('voyager::partials.bulk-delete')
        @endcan
        @can('edit', app($dataType->model_name))
            @if(!empty($dataType->order_column) && !empty($dataType->order_display_column))
                <a href="{{ route('voyager.'.$dataType->slug.'.order') }}" class="btn btn-primary btn-add-new">
                    <i class="voyager-list"></i> <span>{{ __('voyager::bread.order') }}</span>
                </a>
            @endif
        @endcan
        @can('delete', app($dataType->model_name))
            @if($usesSoftDeletes)
                <input type="checkbox" @if ($showSoftDeleted) checked @endif id="show_soft_deletes" data-toggle="toggle" data-on="{{ __('voyager::bread.soft_deletes_off') }}" data-off="{{ __('voyager::bread.soft_deletes_on') }}">
            @endif
        @endcan
        @foreach($actions as $action)
            @if (method_exists($action, 'massAction'))
                @include('voyager::bread.partials.actions', ['action' => $action, 'data' => null])
            @endif
        @endforeach
        @include('voyager::multilingual.language-selector')
    </div>
@stop

@section('content')
    <div class="page-content browse container-fluid">
        @include('voyager::alerts')
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-body" style="min-height: 500px;">
                        @if ($isServerSide)
                            <form method="get" class="form-search">
                                <div id="search-input">
                                    <div class="col-2">
                                        <select id="search_key" name="key">
                                            @foreach($searchNames as $key => $name)
                                                <option value="{{ $key }}" @if($search->key == $key || (empty($search->key) && $key == $defaultSearchKey)) selected @endif>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-2">
                                        <select id="filter" name="filter">
                                            <option value="contains" @if($search->filter == "contains") selected @endif>{{ __('voyager::generic.contains') }}</option>
                                            <option value="equals" @if($search->filter == "equals") selected @endif>=</option>
                                        </select>
                                    </div>
                                    <div class="input-group col-md-12">
                                        <input type="text" class="form-control" placeholder="{{ __('voyager::generic.search') }}" name="s" value="{{ $search->value }}">
                                        <span class="input-group-btn">
                                            <button class="btn btn-info btn-lg" type="submit">
                                                <i class="voyager-search"></i>
                                            </button>
                                        </span>
                                    </div>
                                </div>
                                @if (Request::has('sort_order') && Request::has('order_by'))
                                    <input type="hidden" name="sort_order" value="{{ Request::get('sort_order') }}">
                                    <input type="hidden" name="order_by" value="{{ Request::get('order_by') }}">
                                @endif
                            </form>
                        @endif
                        <!--<div class="table-responsive">-->
                            <table id="dataTable" class="table table-hover display nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        @if($showCheckboxColumn)
                                            <th class="dt-not-orderable">
                                                <input type="checkbox" class="select_all">
                                            </th>
                                        @endif
                                        @foreach($dataType->browseRows as $row)
                                            @if(setting('admin.show_ip_address_all'))
                                                <th>
                                                    @if ($isServerSide && in_array($row->field, $sortableColumns))
                                                        <a href="{{ $row->sortByUrl($orderBy, $sortOrder) }}">
                                                    @endif
                                                    {{ $row->getTranslatedAttribute('display_name') }}
                                                    @if ($isServerSide)
                                                        @if ($row->isCurrentSortField($orderBy))
                                                            @if ($sortOrder == 'asc')
                                                                <i class="voyager-angle-up pull-right"></i>
                                                            @else
                                                                <i class="voyager-angle-down pull-right"></i>
                                                            @endif
                                                        @endif
                                                        </a>
                                                    @endif
                                                </th>
                                            @else
                                                @if(Auth::user()->role_id == 4 || Auth::user()->role_id == 1)
                                                    <th>
                                                        @if ($isServerSide && in_array($row->field, $sortableColumns))
                                                            <a href="{{ $row->sortByUrl($orderBy, $sortOrder) }}">
                                                        @endif
                                                        {{ $row->getTranslatedAttribute('display_name') }}
                                                        @if ($isServerSide)
                                                            @if ($row->isCurrentSortField($orderBy))
                                                                @if ($sortOrder == 'asc')
                                                                    <i class="voyager-angle-up pull-right"></i>
                                                                @else
                                                                    <i class="voyager-angle-down pull-right"></i>
                                                                @endif
                                                            @endif
                                                            </a>
                                                        @endif
                                                    </th>
                                                @else
                                                    @if($row->field != 'customer_belongsto_proxy_relationship')
                                                        <th>
                                                            @if ($isServerSide && in_array($row->field, $sortableColumns))
                                                                <a href="{{ $row->sortByUrl($orderBy, $sortOrder) }}">
                                                            @endif
                                                            {{ $row->getTranslatedAttribute('display_name') }}
                                                            @if ($isServerSide)
                                                                @if ($row->isCurrentSortField($orderBy))
                                                                    @if ($sortOrder == 'asc')
                                                                        <i class="voyager-angle-up pull-right"></i>
                                                                    @else
                                                                        <i class="voyager-angle-down pull-right"></i>
                                                                    @endif
                                                                @endif
                                                                </a>
                                                            @endif
                                                        </th>
                                                    @endif
                                                @endif
                                                
                                            @endif
                                        @endforeach
                                        <th class="actions text-right dt-not-orderable">{{ __('voyager::generic.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dataTypeContent as $data)
                                    <tr @if(empty($data->invited_id) and $data->status == "active") style="background: #e1b6b6 !important;" @endif>
                                        @if($showCheckboxColumn)
                                            <td>
                                                <input type="checkbox" name="row_id" id="checkbox_{{ $data->getKey() }}" value="{{ $data->getKey() }}">
                                            </td>
                                        @endif
                                        @foreach($dataType->browseRows as $row)
                                            @php
                                            if ($data->{$row->field.'_browse'}) {
                                                $data->{$row->field} = $data->{$row->field.'_browse'};
                                            }
                                            @endphp

                                            @if(!setting('admin.show_ip_address_all'))
                                                @if(Auth::user()->role_id != 4 && Auth::user()->role_id != 1)
                                                    @if($row->field == "customer_belongsto_proxy_relationship")
                                                        @continue
                                                    @endif
                                                @endif
                                            @endif
                                            <td>
                                                @if (isset($row->details->view_browse))
                                                    @include($row->details->view_browse, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $data->{$row->field}, 'view' => 'browse', 'options' => $row->details])
                                                @elseif (isset($row->details->view))
                                                    @include($row->details->view, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $data->{$row->field}, 'action' => 'browse', 'view' => 'browse', 'options' => $row->details])
                                                @elseif($row->type == 'image')
                                                    <img src="@if( !filter_var($data->{$row->field}, FILTER_VALIDATE_URL)){{ Voyager::image( $data->{$row->field} ) }}@else{{ $data->{$row->field} }}@endif" style="width:100px">
                                                @elseif($row->type == 'relationship')
                                                    @include('voyager::formfields.relationship', ['view' => 'browse','options' => $row->details])
                                                @elseif($row->type == 'select_multiple')
                                                    @if(property_exists($row->details, 'relationship'))

                                                        @foreach($data->{$row->field} as $item)
                                                            {{ $item->{$row->field} }}
                                                        @endforeach

                                                    @elseif(property_exists($row->details, 'options'))
                                                        @if (!empty(json_decode($data->{$row->field})))
                                                            @foreach(json_decode($data->{$row->field}) as $item)
                                                                @if (@$row->details->options->{$item})
                                                                    {{ $row->details->options->{$item} . (!$loop->last ? ', ' : '') }}
                                                                @endif
                                                            @endforeach
                                                        @else
                                                            {{ __('voyager::generic.none') }}
                                                        @endif
                                                    @endif

                                                    @elseif($row->type == 'multiple_checkbox' && property_exists($row->details, 'options'))
                                                        @if (@count(json_decode($data->{$row->field}, true)) > 0)
                                                            @foreach(json_decode($data->{$row->field}) as $item)
                                                                @if (@$row->details->options->{$item})
                                                                    {{ $row->details->options->{$item} . (!$loop->last ? ', ' : '') }}
                                                                @endif
                                                            @endforeach
                                                        @else
                                                            {{ __('voyager::generic.none') }}
                                                        @endif

                                                @elseif(($row->type == 'select_dropdown' || $row->type == 'radio_btn') && property_exists($row->details, 'options'))

                                                    @if($row->field == "status")
                                                        @if($data->status == "active")
                                                            <span class="label label-success">{!! $row->details->options->{$data->{$row->field}} ?? '' !!}</span>
                                                        @else
                                                            <span class="label label-danger">{!! $row->details->options->{$data->{$row->field}} ?? '' !!}</span>
                                                        @endif
                                                    @else
                                                        {!! $row->details->options->{$data->{$row->field}} ?? '' !!}
                                                    @endif
                                                    


                                                @elseif($row->type == 'date' || $row->type == 'timestamp')
                                                    @if ( property_exists($row->details, 'format') && !is_null($data->{$row->field}) )
                                                        {{ \Carbon\Carbon::parse($data->{$row->field})->formatLocalized($row->details->format) }}
                                                    @else
                                                        {{ $data->{$row->field} }}
                                                    @endif
                                                @elseif($row->type == 'checkbox')
                                                    @if(property_exists($row->details, 'on') && property_exists($row->details, 'off'))
                                                        @if($data->{$row->field})
                                                            <span class="label label-info">{{ $row->details->on }}</span>
                                                        @else
                                                            <span class="label label-primary">{{ $row->details->off }}</span>
                                                        @endif
                                                    @else
                                                    {{ $data->{$row->field} }}
                                                    @endif
                                                @elseif($row->type == 'color')
                                                    <span class="badge badge-lg" style="background-color: {{ $data->{$row->field} }}">{{ $data->{$row->field} }}</span>
                                                @elseif($row->type == 'text')
                                                    @include('voyager::multilingual.input-hidden-bread-browse')
                                                    <div>{{ mb_strlen( $data->{$row->field} ) > 200 ? mb_substr($data->{$row->field}, 0, 200) . ' ...' : $data->{$row->field} }}</div>
                                                @elseif($row->type == 'text_area')
                                                    @include('voyager::multilingual.input-hidden-bread-browse')
                                                    <div>{{ mb_strlen( $data->{$row->field} ) > 200 ? mb_substr($data->{$row->field}, 0, 200) . ' ...' : $data->{$row->field} }}</div>
                                                @elseif($row->type == 'file' && !empty($data->{$row->field}) )
                                                    @include('voyager::multilingual.input-hidden-bread-browse')
                                                    @if(json_decode($data->{$row->field}) !== null)
                                                        @foreach(json_decode($data->{$row->field}) as $file)
                                                            <a href="{{ Storage::disk(config('voyager.storage.disk'))->url($file->download_link) ?: '' }}" target="_blank">
                                                                {{ $file->original_name ?: '' }}
                                                            </a>
                                                            <br/>
                                                        @endforeach
                                                    @else
                                                        <a href="{{ Storage::disk(config('voyager.storage.disk'))->url($data->{$row->field}) }}" target="_blank">
                                                            {{ __('voyager::generic.download') }}
                                                        </a>
                                                    @endif
                                                @elseif($row->type == 'rich_text_box')
                                                    @include('voyager::multilingual.input-hidden-bread-browse')
                                                    <div>{{ mb_strlen( strip_tags($data->{$row->field}, '<b><i><u>') ) > 200 ? mb_substr(strip_tags($data->{$row->field}, '<b><i><u>'), 0, 200) . ' ...' : strip_tags($data->{$row->field}, '<b><i><u>') }}</div>
                                                @elseif($row->type == 'coordinates')
                                                    @include('voyager::partials.coordinates-static-image')
                                                @elseif($row->type == 'multiple_images')
                                                    @php $images = json_decode($data->{$row->field}); @endphp
                                                    @if($images)
                                                        @php $images = array_slice($images, 0, 3); @endphp
                                                        @foreach($images as $image)
                                                            <img src="@if( !filter_var($image, FILTER_VALIDATE_URL)){{ Voyager::image( $image ) }}@else{{ $image }}@endif" style="width:50px">
                                                        @endforeach
                                                    @endif
                                                @elseif($row->type == 'media_picker')
                                                    @php
                                                        if (is_array($data->{$row->field})) {
                                                            $files = $data->{$row->field};
                                                        } else {
                                                            $files = json_decode($data->{$row->field});
                                                        }
                                                    @endphp
                                                    @if ($files)
                                                        @if (property_exists($row->details, 'show_as_images') && $row->details->show_as_images)
                                                            @foreach (array_slice($files, 0, 3) as $file)
                                                            <img src="@if( !filter_var($file, FILTER_VALIDATE_URL)){{ Voyager::image( $file ) }}@else{{ $file }}@endif" style="width:50px">
                                                            @endforeach
                                                        @else
                                                            <ul>
                                                            @foreach (array_slice($files, 0, 3) as $file)
                                                                <li>{{ $file }}</li>
                                                            @endforeach
                                                            </ul>
                                                        @endif
                                                        @if (count($files) > 3)
                                                            {{ __('voyager::media.files_more', ['count' => (count($files) - 3)]) }}
                                                        @endif
                                                    @elseif (is_array($files) && count($files) == 0)
                                                        {{ trans_choice('voyager::media.files', 0) }}
                                                    @elseif ($data->{$row->field} != '')
                                                        @if (property_exists($row->details, 'show_as_images') && $row->details->show_as_images)
                                                            <img src="@if( !filter_var($data->{$row->field}, FILTER_VALIDATE_URL)){{ Voyager::image( $data->{$row->field} ) }}@else{{ $data->{$row->field} }}@endif" style="width:50px">
                                                        @else
                                                            {{ $data->{$row->field} }}
                                                        @endif
                                                    @else
                                                        {{ trans_choice('voyager::media.files', 0) }}
                                                    @endif
                                                @else
                                                    @include('voyager::multilingual.input-hidden-bread-browse')
                                                    <span>{{ $data->{$row->field} }}</span>
                                                @endif

                                                @if($row->field == "customer_belongsto_server_relationship")
                                                    @if($data->pin)
                                                        <img src="{{ asset('images/iphone.png') }}" style="width:25px; height: 25px; display: inline; background: transparent !important;" alt="Iphone">
                                                    @endif
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="no-sort no-click bread-actions">
                                            <div class="dropdown" id="menu-content" style="display: inline !important;">
                                              <a class="btn btn-success dropdown-toggle" title="Mas Opciones" id="dropdownMenu1" data-toggle="dropdown">
                                                <i class="voyager-list-add"></i>
                                              </a>
                                              <ul class="dropdown-menu dropdown-menu-left" id="menu-list" aria-labelledby="dropdownMenu1" style="position:sticky;">
                                                @if(setting('admin.extra_options_limited'))
                                                    @if(Auth::user()->role_id == 1 || Auth::user()->role_id == 3 || Auth::user()->role_id == 4)
                                                        @if($data->status == "active")
                                                            <li><a href="#" class="change-server-modal" data-row='{{json_encode($data)}}'>Cambiar Servidor</a></li>
                                                        @endif
                                                    @endif
                                                @endif

                                                @if(strtotime($data->date_to) >= strtotime(date('Y-m-d')))
                                                    @if($data->status == "active")
                                                        <li><a href="#" class="change-status" data-row='{{json_encode($data)}}'>Inhabilitar</a></li>
                                                    @else
                                                        <li><a href="#" class="change-status" data-row='{{json_encode($data)}}'>Habilitar</a></li>
                                                    @endif
                                                @endif

                                                @if($data->status == "active" && $data->password != "#5inCl4ve#")
                                                    @if( setting('admin.iphone_for_all') )
                                                            @if(empty($data->pin))
                                                                <li><a href="#" class="convert-iphone" data-row='{{json_encode($data)}}'>Convertir a Iphone</a></li>
                                                            @else
                                                                <li><a href="{{ route('remove_iphone', $data->id) }}" class="remove-iphone" data-row='{{json_encode($data)}}'>Quitar Iphone</a></li>
                                                            @endif
                                                    @else
                                                        @if(Auth::user()->role_id == 6 || Auth::user()->role_id == 4 || Auth::user()->role_id == 1)
                                                            @if(empty($data->pin))
                                                                <li><a href="#" class="convert-iphone" data-row='{{json_encode($data)}}'>Convertir a Iphone</a></li>
                                                            @else
                                                                <li><a href="{{ route('remove_iphone', $data->id) }}" class="remove-iphone" data-row='{{json_encode($data)}}'>Quitar Iphone</a></li>
                                                            @endif
                                                        @endif
                                                    @endif
                                                @endif

                                                @if(Auth::user()->role_id == 4 || Auth::user()->role_id == 1)
                                                    <li><a href="#" class="asigned-user-data" data-row='{{json_encode($data)}}'>Asignar a Usuario</a></li>
                                                @endif

                                                @if($data->status == "active")
                                                    <li><a href="#" class="repair-account" data-row='{{json_encode($data)}}'>Reparar Cuenta</a></li>
                                                @endif
                                                @if($data->password !="#5inCl4ve#")
                                                    <li><a href="#" class="change-password-user-plex" data-row='{{json_encode($data)}}'>Cambiar Clave en Plex</a></li>
                                                    <li><a href="#" class="activate-device" data-row='{{json_encode($data)}}'>Activar Cuenta en Dispositivo</a></li>
                                                @endif
                                              </ul>
                                            </div>
                                            @foreach($actions as $action)
                                                @if (!method_exists($action, 'massAction'))
                                                    @include('vendor.voyager.partials.actions', ['action' => $action])
                                                @endif
                                            @endforeach
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        <!--</div>-->
                        @if ($isServerSide)
                            <div class="pull-left">
                                <div role="status" class="show-res" aria-live="polite">{{ trans_choice(
                                    'voyager::generic.showing_entries', $dataTypeContent->total(), [
                                        'from' => $dataTypeContent->firstItem(),
                                        'to' => $dataTypeContent->lastItem(),
                                        'all' => $dataTypeContent->total()
                                    ]) }}</div>
                            </div>
                            <div class="pull-right">
                                {{ $dataTypeContent->appends([
                                    's' => $search->value,
                                    'filter' => $search->filter,
                                    'key' => $search->key,
                                    'order_by' => $orderBy,
                                    'sort_order' => $sortOrder,
                                    'showSoftDeleted' => $showSoftDeleted,
                                ])->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Single delete modal --}}
    <div class="modal modal-danger fade" tabindex="-1" id="delete_modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('voyager::generic.close') }}"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="voyager-trash"></i> {{ __('voyager::generic.delete_question') }} {{ strtolower($dataType->getTranslatedAttribute('display_name_singular')) }}?</h4>
                </div>
                <div class="modal-footer">
                    <form action="#" id="delete_form" method="POST">
                        {{ method_field('DELETE') }}
                        {{ csrf_field() }}
                        <input type="submit" class="btn btn-danger pull-right delete-confirm" value="{{ __('voyager::generic.delete_confirm') }}">
                    </form>
                    <button type="button" class="btn btn-default pull-right" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->

        <!--Modal asing-user-->
    <div class="modal modal-success" id="asing-user" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Asignar Usuario</h4>
                </div>
                <form action="{{ route('change_user') }}" id="form-asing-user" method="POST">
                    @method("POST")
                    @csrf
                    <input type="hidden" name="user_asigned_customer_id" id="user_asigned_customer_id" />
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="">Usuario:</label>
                            <select id="user_asigned_id" name="user_asigned_id" required class="form-control">
                                <option value="">Seleccione</option>
                                @foreach($users_asigned as $ua)
                                    <option value="{{$ua->id}}">{{$ua->name_and_role}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-success" type="submit" id="asing-user-save">Cambiar</button>
                        <button class="btn btn-danger" type="button" id="asing-user-cancel">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!--Modal Change Server-->
    <div class="modal modal-success" id="change-server" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Cambiar de Servidor</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="">Servidor:</label>
                        <select id="server_id" class="form-control">
                            <option value="">Seleccione</option>
                            @foreach($servers as $server)
                                <option value="{{$server->id}}">{{$server->name_and_local_name}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-success" type="button" id="change-server-save">Cambiar</button>
                    <button class="btn btn-danger" type="button" id="change-server-cancel">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

        <!--Modal Convert Iphone-->
    <div class="modal modal-success" id="convert-iphone-modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Convertir a Iphone</h4>
                </div>
                <form action="{{ route('convert_iphone') }}" id="convert-iphone-form" method="POST">
                    @method("POST")
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="pp_customer_id">
                        <div class="form-group">
                            <label for="">Servidor:</label>
                            <select name="server_pp_id" required class="form-control">
                                <option value="">Seleccione</option>
                                @foreach($servers_pp as $spp)
                                    <option value="{{ $spp->id }}">{{$spp->name_and_local_name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="">Pin: ("Para Convertir a Iphone es neceario tener un pin de 4 digitos")<br /><b style="font-weight: bold; font-size: 14px;">No Colocar: 1234</b></label>
                            <input type="text" id="pin" required name="pin" class="form-control" minlength="4" maxlength="4" />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-success" id="btn-convert-iphone" type="submit">Convertir</button>
                        <button class="btn btn-danger" type="button" id="convert-iphone-cancel">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!--Modal Change Server-->
    <div class="modal modal-success" id="change-password-user-plex-modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Cambiar Clave Cuenta Plex</h4>
                </div>
                <form action="{{ route('change_password_user_plex') }}" id="change-password-form" method="POST">
                    @method('POST')
                    @csrf
                    <input type="hidden" name="chp_customer_id" />
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="">Email:</label>
                            <input type="text" class="form-control" id="chp_email" readonly>
                        </div>
                        <div class="form-group">
                            <label for="">Clave Actual</label>
                            <input type="text" name="chp_current_password" id="chp_current_password" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="">Nueva Clave:</label>
                            <input type="text" required name="chp_new_password" class="form-control" />
                        </div>
                        <div class="checkbox">
                            <label for="">
                                <input type="checkbox" name="remove_all_devices"> Remover todas las Sesiones
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-info" type="button" id="change-password-user-plex-generate">Generar Clave</button>
                        <button class="btn btn-success" id="change-password-button" type="button">Cambiar</button>
                        <button class="btn btn-danger" type="button" id="change-password-user-plex-cancel">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!--Activate Device Modal-->
    <div class="modal modal-success" id="activate-device-modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Activar Cuenta en Dispostivo</h4>
                </div>
                <div class="modal-body">
                    <form action="{{ route('activate_device') }}" id="activate-device-form" method="POST">
                        @method("POST")
                        @csrf
                        <input type="hidden" name="customer_id" id="activate_device_customer_id" />
                        <div class="form-group">
                            <label for="">Codigo</label>
                            <input type="text" maxlength="4" minlength="4" id="code_activate_device" id="" class="form-control" name="code" />
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-success" type="button" id="activate-device-save">Activar</button>
                    <button class="btn btn-danger" type="button" id="activate-device-cancel">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    @if(!$dataType->server_side && config('dashboard.data_tables.responsive'))
        <link rel="stylesheet" href="{{ voyager_asset('lib/css/responsive.dataTables.min.css') }}">
        <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    @endif
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
@stop

@section('javascript')
    <!-- DataTables -->
    @if(!$dataType->server_side && config('dashboard.data_tables.responsive'))
        <script src="{{ voyager_asset('lib/js/dataTables.responsive.min.js') }}"></script>
    @endif
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script>

        function onlyNumbers(id){
            var numberInput = document.getElementById(id);
            numberInput.addEventListener("input", function (e) {
                numberInput.value = numberInput.value.replace(/[^0-9]/g, '');
            });
        }

        onlyNumbers("pin");

        function generateStrongPassword() {
          const length = 10;
          const uppercaseChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
          const lowercaseChars = 'abcdefghijklmnopqrstuvwxyz';
          const numberChars = '0123456789';
          const specialChars = '!@#$%^&*';

          const allChars = uppercaseChars + lowercaseChars + numberChars + specialChars;

          let password = '';
          //upper
          for (let i = 0; i < 3; i++) {
            const randomIndex = Math.floor(Math.random() * uppercaseChars.length);
            password += uppercaseChars[randomIndex];
          }

          //lower
          for (let i = 0; i < 3; i++) {
            const randomIndex = Math.floor(Math.random() * lowercaseChars.length);
            password += lowercaseChars[randomIndex];
          }

          //number
          for (let i = 0; i < 3; i++) {
            const randomIndex = Math.floor(Math.random() * numberChars.length);
            password += numberChars[randomIndex];
          }

           //special
          for (let i = 0; i < 1; i++) {
            const randomIndex = Math.floor(Math.random() * specialChars.length);
            password += specialChars[randomIndex];
          }

          return password;
        }

         $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': '{{csrf_token()}}'
            }
        });

        $(document).ready(function () {
            var id;
            $("body").on("click","a.change-server-modal", function(){
                let row = JSON.parse($(this).attr("data-row"));
                console.log(row);
                server_id = row.server_id;
                id = row.id;
                removeServerById(server_id);
                $("#change-server").modal({backdrop: 'static', keyboard: false}, 'show');
            });

            $("body").on("click","a.activate-device", function(){

                let row = JSON.parse($(this).attr("data-row"));
                $("#activate_device_customer_id").val(row.id);

                $("#activate-device-modal").modal({backdrop: 'static', keyboard: false}, 'show');
            });

            $("#activate-device-cancel").click(function(){
                $("#activate-device-modal").modal("hide");
            });

            $("#activate-device-save").click(function(){
                let code = $("#code_activate_device").val();
                if(code.length == 4){
                    $(this).text("Activando...").attr("disabled", true);
                    $("#activate-device-form").submit();
                }else{
                    alert("El Codigo debe ser de 4 Digitos entre letras y numeros!!");
                }
            });

            $("body").on("click","a.change-password-user-plex", function(){
                let row = JSON.parse($(this).attr("data-row"));
                $("input[name='chp_customer_id']").val(row.id);
                $("input[name='chp_new_password']").val(generateStrongPassword());
                $("#chp_current_password").val(row.password);
                $("#chp_email").val(row.email);
                $("#change-password-user-plex-modal").modal({ backdrop:'static', keyboard: false }, "show");
            });

            $("body").on("click","a.asigned-user-data", function(){
                let row = JSON.parse($(this).attr("data-row"));
                removeUserById(row.user_id);
                $("#user_asigned_customer_id").val(row.id);
                $("#asing-user").modal({ backdrop:'static', keyboard: false }, "show");
            });

            $("#asing-user-cancel").click(function(){
                $("#asing-user").modal("hide");
            });

            $("#form-asing-user").submit(function(){
                $("#asing-user").modal("hide");
                Swal.fire({
                  title: 'Advertencia',
                  text: "Estamos Realizando el Cambio!!",
                  icon: 'warning',
                  showConfirmButton:false,
                  allowOutsideClick: false,
                  confirmButtonText: 'Yes, delete it!'
                });
            });

            $("#change-password-user-plex-generate").click(function(){
                $("input[name='chp_new_password']").val(generateStrongPassword());
            });

            $("#change-password-user-plex-cancel").click(function(){
                $("#change-password-user-plex-modal").modal("hide");
            });

            $("#change-password-button").click(function(){
                $("#change-password-button").text("Enviando...").attr("disabled", true);
                $("#change-password-user-plex-generate, #change-password-user-plex-cancel").attr("disabled", true);
                $("#change-password-form").submit();
            })

            $("body").on("click","a.remove-iphone", function(){

                Swal.fire({
                  title: 'Estas Seguro de Quitar de Iphone?',
                  icon: 'info',
                  showCancelButton: true,
                  confirmButtonText:'Aceptar',
                  confirmButtonColor: "#2ecc71",
                  cancelButtonText:'Cancelar',
                  cancelButtonColor: "#fa2a00"
                }).then((result)=>{
                    if(result.isConfirmed){

                        Swal.fire({
                          title: 'Advertencia',
                          text: "Estamos Realizando el Cambio!!",
                          icon: 'warning',
                          showConfirmButton:false,
                          allowOutsideClick: false,
                          confirmButtonText: 'Yes, delete it!'
                        });

                        location.href=$(this).attr("href");

                    }
                });

                return false;

            });

            $("#convert-iphone-form").submit(function(){
                $("#btn-convert-iphone").attr("disabled", true).text("Cargando...");
                $("#convert-iphone-cancel").attr("disabled", true);
            });

            $("body").on("click","a.convert-iphone", function(){
                let row = JSON.parse($(this).attr("data-row"));
                server_id = row.server_id;
                id = row.id;
                $("input[name='pp_customer_id']").val(id);
                $("#convert-iphone-modal").modal({backdrop: 'static', keyboard: false}, 'show');
            });

            $("#convert-iphone-cancel").click(function(){
                $("#convert-iphone-modal").modal("hide");
            });

            function removeServerById(id){
                $("#server_id").children("option").each(function(){
                    if(id == $(this).val()){
                        $(this).remove();
                    }
                });
            }

            function removeUserById(id){
                $("#user_asigned_id").children("option").each(function(){
                    if(id == $(this).val()){
                        $(this).remove();
                    }
                });
            }

            $("body").on("click","a.change-status", function(){
                let row = JSON.parse($(this).attr("data-row"));

                Swal.fire({
                  title: 'Estas Seguro de Realizar Esta Accion?',
                  icon: 'info',
                  showCancelButton: true,
                  confirmButtonText:'Aceptar',
                  confirmButtonColor: "#2ecc71",
                  cancelButtonText:'Cancelar',
                  cancelButtonColor: "#fa2a00"
                }).then((result)=>{
                    if(result.isConfirmed){

                        Swal.fire({
                          title: 'Advertencia',
                          text: "Estamos Realizando el Cambio!!",
                          icon: 'warning',
                          showConfirmButton:false,
                          allowOutsideClick: false,
                          confirmButtonText: 'Yes, delete it!'
                        });

                        $.get("change-status/"+row.id, function(response){
                            let data = response;
                            if(data.success){
                                Swal.fire({
                                  title: 'Notificacion',
                                  text: data.message,
                                  icon: 'success',
                                  showConfirmButton:false,
                                  allowOutsideClick:false,
                                  confirmButtonText: 'OK'
                                });
                                setTimeout(() => location.reload(), 3000);
                            }else{
                                 Swal.fire({
                                  title: 'Notificacion',
                                  text: data.message,
                                  icon: 'error',
                                  showConfirmButton:false,
                                  confirmButtonText: 'OK'
                                });
                            }
                        });
                    }
                });

            });

            $("body").on("click","a.repair-account", function(){
                let row = JSON.parse($(this).attr("data-row"));

                Swal.fire({
                  title: 'Estas Seguro de Reparar esta Cuenta?',
                  icon: 'info',
                  showCancelButton: true,
                  confirmButtonText:'Aceptar',
                  confirmButtonColor: "#2ecc71",
                  cancelButtonText:'Cancelar',
                  cancelButtonColor: "#fa2a00"
                }).then((result)=>{
                    if(result.isConfirmed){

                        Swal.fire({
                          title: 'Advertencia',
                          text: "Estamos Realizando la Reparacion!!",
                          icon: 'warning',
                          showConfirmButton:false,
                          allowOutsideClick: false,
                          confirmButtonText: 'Yes, delete it!'
                        });

                        location.href = "repair-account/"+row.id;
                    }
                });

            });

            @if(Session::get('modal'))
                @php 
                    $data = Session::get('modal');
                @endphp
                @if($data->password == "#5inCl4ve#")
                    Swal.fire({
                      title: 'Estos son los datos que debes darle al cliente!!',
                      icon: 'info',
                      html:'<textarea id="field_copy" class="form-control" style="height: 200px; width: 403px;" readonly>Correo: {{$data->email}}\nEnlace Activacion: https://plex.tv/pms/home/users/accept.html?invite_token={{$data->plex_user_id}}\nUsuario: {{$data->plex_user_name}}\nPantallas: {{$data->screens}}\nPin: {{$data->pin}}\nFecha de Vencimiento: {{date("d-m-Y",strtotime($data->date_to))}}</textarea>',
                      confirmButtonColor: '#5cb85c',
                      confirmButtonText: 'Copiar y Salir',
                      allowOutsideClick:false
                    }).then((result) => {
                      if (result.isConfirmed) {
                        $("#field_copy").select();
                        document.execCommand('copy');
                      }
                    });
                @else
                    Swal.fire({
                      title: 'Estos son los datos que debes darle al cliente!!',
                      icon: 'info',
                      html:'<textarea id="field_copy" class="form-control" style="height: 150px; width: 403px;" readonly>Correo: {{$data->email}}\nClave: {{$data->password}}\nUsuario: {{$data->plex_user_name}}\nPantallas: {{$data->screens}}\nPin: {{$data->pin}}\nFecha de Vencimiento: {{date("d-m-Y",strtotime($data->date_to))}}</textarea>',
                      confirmButtonColor: '#5cb85c',
                      confirmButtonText: 'Copiar y Salir',
                      allowOutsideClick:false
                    }).then((result) => {
                      if (result.isConfirmed) {
                        $("#field_copy").select();
                        document.execCommand('copy');
                      }
                    });
                @endif
            @endif

            $("#change-server-save").click(function(){
                $(this).text("Cargando...").attr("disabled", true);
                let server = $("#server_id").val();
                if(server){
                    $.ajax({
                        type:"POST",
                        url:"{{ route('change_server') }}",
                        data:{'id':id, 'server_id':server},
                        success:function(response){
                            let data = response;
                            console.log(data);
                            if(data.success){
                                $("#change-server").modal('hide');
                                Swal.fire({
                                  position: 'top-end',
                                  icon: 'success',
                                  title: data.message,
                                  showConfirmButton: false,
                                  timer: 2000
                                });

                                setTimeout(() => location.reload(), 3000);

                            }else{
                                $("#change-server").modal("hide");
                                setTimeout(() => location.reload(), 3000);
                                Swal.fire(
                                  'Alert',
                                  data.message,
                                  'error'
                                )
                            }
                        }
                    });
                }else{
                    alert("Debes Seleccionar un Servidor!!");
                }
            });

            $("#change-server-cancel").click(function(){
                $("#change-server").modal('hide');
                $("#server_id").val("")
                server_id = "";
                id = "";
            });


            @if (!$dataType->server_side)
                var table = $('#dataTable').DataTable({!! json_encode(
                    array_merge([
                        "responsive"=>true,
                        "iDisplayLength"=> 10,
                        "aLengthMenu"=>[[5, 10, 25, 50, 100, -1], [5, 10, 25, 50, 100, "All"]],
                        "dom"=>"Bfrtip",
                        "buttons"=>[
                            "excelHtml5",
                            "csvHtml5"
                        ],
                        "iDisplayLength"=> -1,
                        "order" => $orderColumn,
                        "language" => __('voyager::datatable'),
                        "columnDefs" => [
                            ['targets' => 'dt-not-orderable', 'searchable' =>  false, 'orderable' => false],
                        ],
                    ],
                    config('voyager.dashboard.data_tables', []))
                , true) !!});

                $('#sort').change(function() {
                  table.search($(this).val()).draw();
                });
            @else
                $('#search-input select').select2({
                    minimumResultsForSearch: Infinity
                });
            @endif

            @if ($isModelTranslatable)
                $('.side-body').multilingual();
                //Reinitialise the multilingual features when they change tab
                $('#dataTable').on('draw.dt', function(){
                    $('.side-body').data('multilingual').init();
                })
            @endif
            $('.select_all').on('click', function(e) {
                $('input[name="row_id"]').prop('checked', $(this).prop('checked')).trigger('change');
            });
        });


        var deleteFormAction;
        $('body').on('click', '.delete', function (e) {
            $('#delete_form')[0].action = '{{ route('voyager.'.$dataType->slug.'.destroy', '__id') }}'.replace('__id', $(this).data('id'));
            $('#delete_modal').modal('show');
        });

        @if($usesSoftDeletes)
            @php
                $params = [
                    's' => $search->value,
                    'filter' => $search->filter,
                    'key' => $search->key,
                    'order_by' => $orderBy,
                    'sort_order' => $sortOrder,
                ];
            @endphp
            $(function() {
                $('#show_soft_deletes').change(function() {
                    if ($(this).prop('checked')) {
                        $('#dataTable').before('<a id="redir" href="{{ (route('voyager.'.$dataType->slug.'.index', array_merge($params, ['showSoftDeleted' => 1]), true)) }}"></a>');
                    }else{
                        $('#dataTable').before('<a id="redir" href="{{ (route('voyager.'.$dataType->slug.'.index', array_merge($params, ['showSoftDeleted' => 0]), true)) }}"></a>');
                    }

                    $('#redir')[0].click();
                })
            })
        @endif
        $('input[name="row_id"]').on('change', function () {
            var ids = [];
            $('input[name="row_id"]').each(function() {
                if ($(this).is(':checked')) {
                    ids.push($(this).val());
                }
            });
            $('.selected_ids').val(ids);
        });
    </script>
@stop
