@extends('layouts.bank.default')
@section('title')
Applications
@endsection

@section('breadcrumbTitle')
<a href="{{ route('bank-dashboard') }}">Dashboard</a> / Applications
@endsection
@section('content')
<style type="text/css">
    table th:last-child,
    table th:first-child {
        min-width: auto;
    }

    #applications_list>th {
        min-width: 230px;
    }

    /* table th {
        min-width: 230px;
    } */

    table td .dropdown-menu {
        overflow: auto;
        /* height: 98px; */
        height: auto;
    }


</style>
@include('requestDate')
<div class="chatbox">
    <div class="chatbox-close"></div>
    <div class="custom-tab-1">
        <a class="nav-link active" data-toggle="tab" href="#Search">Advanced Search</a>
        <div class="tab-content">
            <div class="tab-pane fade active show" id="Search" role="tabpanel">
                <form method="" id="search-form">
                    <div class="basic-form">
                        <div class="form-row">
                            <div class="form-group col-lg-4">
                                <label for="business_name">Business Name</label>
                                <select name="user_id" id="business_name" data-size="7" data-live-search="true"
                                    class="select2 btn-primary fill_selectbtn_in own_selectbox" data-width="100%">
                                    <option selected disabled>Select here</option>
                                    @foreach($businessNames as $key => $value)
                                    <option value="{{ $value->user_id }}"
                                        {{ (isset($_GET['user_id']) && $_GET['user_id'] == $value->user_id) ? 'selected' : '' }}>
                                        {{ $value->business_name }}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('user_id'))
                                <span class="help-block">
                                    <strong class="text-danger">{{ $errors->first('user_id') }}</strong>
                                </span>
                                @endif
                            </div>

                            <div class="form-group col-lg-4">
                                <label for="text">Start Date</label>
                                <div class="date-input">
                                    <input class="form-control" type="text" name="start_date" placeholder="Enter here"
                                        id="start_date"
                                        value="{{ (isset($_GET['start_date']) && $_GET['start_date'] != '')?$_GET['start_date']: '' }}"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="form-group col-lg-4">
                                <label for="end_date">End Date</label>
                                <div class="date-input input-group">
                                    <input type="" id="end_date" class="form-control"
                                        data-multiple-dates-separator=" - " data-language="en" placeholder="Enter here"
                                        name="end_date"
                                        value="{{ (isset($_GET['end_date']) && $_GET['end_date'] != '')?$_GET['end_date']: '' }}"
                                        autocomplete="off">
                                </div>
                                @if ($errors->has('end_date'))
                                <span class="help-block">
                                    <strong class="text-danger">{{ $errors->first('end_date') }}</strong>
                                </span>
                                @endif
                            </div>
                            <div class="form-group col-lg-4">
                                <label for="website_url">Website URL</label>
                                <input type="text" class="form-control" placeholder="Enter here" name="website_url"
                                    value="{{ (isset($_GET['website_url']) && $_GET['website_url'] != '')?$_GET['website_url']:'' }}">
                                @if ($errors->has('website_url'))
                                <span class="help-block">
                                    <strong class="text-danger">{{ $errors->first('website_url') }}</strong>
                                </span>
                                @endif
                            </div>
                            <div class="form-group col-lg-4">
                                <label for="status">Status</label>
                                <select name="status" data-size="7" data-live-search="true"
                                    class="select2 btn-primary form-control fill_selectbtn_in own_selectbox"
                                    data-width="100%">
                                    <option disabled selected>Select here</option>
                                    <option value="1"
                                        {{ (isset($_GET['status']) && $_GET['status'] == '1')?'selected':'' }}>
                                        Approved</option>
                                    <option value="2"
                                        {{ (isset($_GET['status']) && $_GET['status'] == '2')?'selected':'' }}>
                                        Rejected</option>

                                </select>
                                @if ($errors->has('status'))
                                <span class="help-block">
                                    <strong class="text-danger">{{ $errors->first('status') }}</strong>
                                </span>
                                @endif
                            </div>

                            <div class="col-sm-12 mt-4 submit-buttons-commmon">
                                <button type="submit" class="btn btn-success" id="extraSearch123">Search</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-xl-12 col-xxl-12">
        <div class="card">
            <div class="card-header">
                <div class="mr-auto pr-3">
                    <h4 class="card-title">Applications</h4>
                </div>
                <form class="mr-2" id="noListform" method="GET">
                    <select class="custom-select form-control" name="noList" id="noList">
                        <option value="">--No of Records--</option>
                        <option value="30" {{request()->get('noList') == '30' ? 'selected' : '' }}>30</option>
                        <option value="50" {{request()->get('noList') == '50' ? 'selected' : '' }}>50</option>
                        <option value="100" {{request()->get('noList') == '100' ? 'selected' : '' }}>100</option>
                    </select>
                </form>
                <div class="btn-group mr-2">
                    <button type="button" class="btn btn-warning bell-link btn-sm"> <i class="fa fa-search-plus"></i>
                        Advanced
                        Search</button>
                    <a href="{{route('bank-application-list')}}" class="btn btn-danger btn-sm">Reset</a>
                </div>

            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="applications_list" class="table table-responsive-md shadow-hover">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th style="min-width: 115px;">Status</th>
                                <th>Business Name</th>
                                <!-- <th>Email</th>
                                <th>Merchant Name</th> -->
                                <th>Business Type</th>
                                <th>Website URL</th>
                                <th>Creation Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(!empty($applications) && $applications->count())
                            @foreach($applications as $key => $value)
                            @php $key++; @endphp
                            <tr>

                                <td>

                                    <div class="dropdown ml-auto">
                                        <a href="#" class="btn btn-primary sharp" data-toggle="dropdown"
                                            aria-expanded="true"><svg xmlns="http://www.w3.org/2000/svg"
                                                xmlns:xlink="http://www.w3.org/1999/xlink" width="18px" height="18px"
                                                viewBox="0 0 24 24" version="1.1">
                                                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                    <rect x="0" y="0" width="24" height="24">
                                                    </rect>
                                                    <circle fill="#FFF" cx="5" cy="12" r="2">
                                                    </circle>
                                                    <circle fill="#FFF" cx="12" cy="12" r="2">
                                                    </circle>
                                                    <circle fill="#FFF" cx="19" cy="12" r="2">
                                                    </circle>
                                                </g>
                                            </svg></a>

                                        <ul class="dropdown-menu dropdown-menu-right">
                                            @if($value->bstatus == 0)
                                            <li class="dropdown-item"><a href="javascript:void(0)" class="approve"
                                                    data-id="{{ $value->bapp_id }}" id="application_approved"
                                                    class="dropdown-a"><i class="fa fa-check text-success mr-2"></i>
                                                    Approve</a>
                                            <li class="dropdown-item"><a href="" class="user-show" data-toggle="modal"
                                                    data-id="{{ $value->bapp_id }}" data-target="#application_reject"
                                                    class="dropdown-a"><i class="fa fa-times text-danger mr-2"></i>
                                                    Reject</a>
                                            </li>
                                            @elseif($value->bstatus == '2' )

                                            <li class="dropdown-item"><a href="javascript:void(0)" class="approve"
                                                    data-id="{{ $value->bapp_id }}" id="application_approved"
                                                    class="dropdown-a"><i class="fa fa-check text-success mr-2"></i>
                                                    Approve</a>

                                                @endif

                                            <li class="dropdown-item">

                                                <a href="{!! URL::route('bank-application-view', [$value->bapp_id]) !!}"
                                                    class="dropdown-a"><i class="fa fa-eye text-secondary mr-2"></i>
                                                    Show
                                                </a>
                                            </li>
                                        </ul>

                                    </div>

                                </td>


                                <td style="min-width: 250px;width: 250px;">


                                    @if($value->bstatus == '1')
                                    <span class="badge badge-success badge-sm">Approved</span>
                                    @elseif($value->bstatus == '2')
                                    <span class="badge badge-danger badge-sm">Rejected</span>
                                    @else
                                    <span class="badge badge-warning badge-sm">Pending</span>

                                    @endif


                                        <span data-toggle="modal" data-target="#App_Note" data-id="{{ $value->id }}" class="AppNote">
                                        <a href="javascript:;" data-toggle="tooltip" data-placement="top" title="Application Note" class="btn btn-xs btn-warning pull-right"><i class="fa fa-sticky-note"></i></a>
                                    </span>

                                </td>
                                <td>{{ strlen($value->business_name) > 50 ? substr($value->business_name,0,30)."..." : $value->business_name }}
                                </td>
                                <!-- <td>{{ $value->user->email ?? 'No Email' }}</td>
                                <td>{{ $value->user->name ?? 'No Name' }}</td> -->
                                <td>{{ $value->business_type }}</td>
                                <td>
                                    {{ strlen($value->website_url) > 50 ? substr($value->website_url,0,30)."..." : $value->website_url }}
                                </td>
                                <td>{{ convertDateToLocal($value->created_at, 'd-m-Y') }}</td>
                            </tr>
                            @endforeach
                            @else
                            <tr>
                                @php $colSpan = 11; @endphp
                                <td colspan="{{ $colSpan }}">
                                    <p class="text-center"><strong>No applications found.</strong></p>
                                </td>
                                @for($i = 1; $i < $colSpan; $i++) <td style="display: none">
                                    </td>
                                    @endfor
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                <div class="pagination-wrap">
                    {!! $applications->appends($_GET)->links() !!}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal right fade" id="Send_email" tabindex="-1" role="dialog" aria-labelledby="right_modal_lg">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Send Mail</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="SendMailForm" method="POST" enctype="multipart/form-data">
                <div class="modal-body" id="">
                    @csrf
                    <div class="col-xl-12 col-sm-12 col-md-12 col-12 mb-4">
                        <input class="form-control" type="text" name="subject" id="subject" placeholder="Enter Subject"
                            value="">
                        <span class="help-block text-danger">
                            <strong id="er_subject"></strong>
                        </span>
                    </div>
                    <div class="col-xl-12 col-sm-12 col-md-12 col-12 mb-4">
                        <textarea class="form-control" name="bodycontent" id="bodycontent"
                            placeholder="Enter Mail Text Here...." rows="5"></textarea>
                        <span class="help-block text-danger">
                            <strong id="er_bodycontent"></strong>
                        </span>
                    </div>
                </div>
                <div class="modal-footer modal-footer-fixed">
                    <button type="button" class="btn btn-success btn-sm" id="submitSendMail"
                        data-link="{{ route('send-user-multi-mail') }}">Send Mail</button>
                    <button type="button" class="btn btn-primary btn-sm" data-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal right fade" id="application_reject" tabindex="-1" role="dialog" aria-labelledby="right_modal_lg">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Rejection Reason</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <input type="hidden" name="id" value="" id="application_id">
                <textarea width="100%" rows="4" class="form-control" id="reject_reason" style="width:100%"
                    placeholder="Describe here..." required></textarea>
                <span style="color:red;display:none;font-size:14px;" id="reject_reason_error">Please enter rejection
                    reason </span>
            </div>
            <div class="modal-footer">
                <div class="form-group col-lg-12 text-right">
                    <button type="submit" class="btn btn-success btn-sm showsubmit">Submit</button>
                    <button type="button" class="btn btn-primary btn-sm closeResion" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal right fade" id="App_Note" tabindex="-1" role="dialog" aria-labelledby="right_modal_lg">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Application Note</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <form id="noteForm">
                            {{ csrf_field() }}
                            <input type="hidden" name="id" value="" id="appId">
                            <div class="form-group">
                                <label>Write Note</label>
                                <textarea class="form-control" name="note" id="note" rows="3"
                                          placeholder="Write Here Your Note"></textarea>
                                <span class="help-block text-danger">
                                    <span id="note_error"></span>
                                </span>
                            </div>
                            <button type="button" id="submitNoteForm" data-link="{{ route('bank-store-application-note') }}" class="btn btn-success btn-sm">Submit Note</button>

                            <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal" id="closeNoteForm">Close</button>
                        </form>
                    </div>
                    <div class="col-md-12">
                        <div id="detailsContent"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('customScript')
<script src="{{ storage_asset('newIpay/assets/custom_js/admin/applications.js') }}"></script>
<script src="{{ storage_asset('newIpay/assets/custom_js/common.js') }}"></script>
<script>
    // show bank details
   $('body').on('click', '.user-show', function() {
        var id = $(this).data('id');
        $('#application_id').val(id);
    });
    $('body').on('click', '.closeResion', function() {
        //var id = $(this).data('id');
        $('#application_id').val('');
        $('#reject_reason').val('');
    });

   $('body').on('click', '.showsubmit', function() {
        var id = $('#application_id').val();
        var reject_reason = $('#reject_reason').val();
        //alert(reject_reason);
        if(reject_reason=='')
        {
            $('#reject_reason_error').show().delay(5000).hide(0);

              return false;
        }

        $.ajax({
            type:'POST',
            url:'{{ route('bank-application-rejectstatus') }}',
            data: {
                'id': id,
                'reject_reason':reject_reason,
                '_token': "{{ csrf_token() }}"
            },
            context: $(this),

            success:function(data) {
                location.reload();
            },
        });
   });

   $(document).on('click', '#application_approved', function(){

        var id = $(this).attr('data-id');

        if(id){
          swal({
              title: "Are you sure?",
              text: "You want to approve this application!",
              icon: "info",
              buttons: true,
              dangerMode: true,
          })
          .then((willDelete) => {
            if (willDelete) {
                  $.ajax({
                    url:"{{ route('bank-application-status')}}",
                    method:"POST",
                    data: {
                        'id': id,
                        '_token': "{{ csrf_token() }}"
                    },
                    success:function(data){
                        location.reload();
                    //   toastr.success('Application Approved Successfully!!');
                    //   window.setTimeout(
                    //     function(){
                    //         location.reload(true)
                    //     },
                    //     1000
                    //   );
                    }
                  });
            }
          })
        } else {
          toastr.error('Please select atleast one application !!');
        }
   });
</script>
<script>
    // show bank details
    $('body').on('click', '.user-show', function() {
        var id = $(this).data('id');
        $.ajax({
            type:'POST',
            url:'{{ route('get-bank-list') }}',
            data: {
                'id': id,
                '_token': "{{ csrf_token() }}"
            },
            context: $(this),
            beforeSend: function(){
                $('#userDetailsContent').html('<i class="fa fa-spinner fa-spin"></i>  Please Wait...');
            },
            success:function(data) {
                $('#userDetailsContent').html(data.html);
            },
        });
    });

    $('body').on('click', '.AppNote', function(){
        var id = $(this).data('id');
        $('#appId').val(id);
        $('#detailsContent').html('');
        $.ajax({
            url:'{{ route('bank-get-application-note') }}',
            type:'POST',
            data:{ "_token": "{{ csrf_token() }}", 'id' : id},
            beforeSend: function(){
                $('#detailsContent').html('<i class="fa fa-spinner fa-spin"></i>  Please Wait...');
            },
            success:function(data) {
                $('#detailsContent').html(data.html);
            },
        });
    });

    function getAppNote(id) {
        $.ajax({
            url:'{{ route('bank-get-application-note') }}',
            type:'POST',
            data:{ "_token": "{{ csrf_token() }}", 'id' : id},
            beforeSend: function(){
                $('#detailsContent').html('<i class="fa fa-spinner fa-spin"></i>  Please Wait...');
            },
            success:function(data) {
                $('#detailsContent').html(data.html);
            },
        });
    }

    $('body').on('click', '#submitNoteForm', function(){
        var noteForm = $("#noteForm");
        var formData = noteForm.serialize();
        $( '#note_error' ).html( "" );
        $( '#note' ).val( "" );
        let apiUrl = $(this).data('link');

        $.ajax({
            url: apiUrl,
            type:'POST',
            data:formData,
            success:function(data) {
                if(data.errors) {
                    if(data.errors.note){
                        $( '#note_error' ).html( data.errors.note[0] );
                    }
                }
                if(data.success == '1') {
                    getAppNote(data.id);
                    toastr.success('Add Note Successfully.');
                } else if (data.success == '0')   {
                    toastr.error('Something went wrong, please try again!');
                }
            },
        });
    });

    $('body').on('click', '#closeNoteForm', function(){
        $( '#note_error' ).html( "" );
        $( '#note' ).val( "" );
    });
</script>
@endsection
