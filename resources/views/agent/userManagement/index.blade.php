@extends('layouts.agent.default')
@section('title')
Merchanat
@endsection
@section('breadcrumbTitle')
<a href="{{ route('rp.dashboard') }}">Dashboard</a> / Merchant Management
@endsection

@section('customeStyle')
@endsection

@section('content')
<div class="row gy-5 g-xl-8 d-flex align-items-center mt-lg-0 mb-10 mb-lg-15">
  <div class="chatbox">
    <div class="chatbox-close"></div>
    <div class="custom-tab-1">
      <a class="nav-link active" data-toggle="tab" href="#Search">Advanced Search</a>
      <div class="tab-content">
        <div class="tab-pane fade active show" id="Search" role="tabpanel">
          <form method="GET" id="search-form">
            <div class="basic-form">
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="">Username</label>
                  <input class="form-control" name="name" type="text" placeholder="Enter here..."
                    value="{{ (isset($_GET['name']) && $_GET['name'] != '')?$_GET['name']:'' }}">
                </div>

                <div class="form-group col-md-6">
                  <label for="">Company</label>
                  <select class="form-control select2" name="company_name" data-title="-- Select Company --"
                    id="company_name" data-width="100%">
                    <option selected value=""> -- Select Company -- </option>
                    @foreach($businessName as $k=>$value)
                    <option value="{{$value}}"
                      {{(isset($_GET['company_name']) && $_GET['company_name'] != '')?(($value==$_GET['company_name'])?'selected':''):''}}>
                      {{$value}}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-sm-12 mt-4 submit-buttons-commmon">
                  <button type="submit" class="btn btn-success" id="extraSearch">Search</button>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-lg-12 mb-3">
      <a class="btn btn-primary mr-2 pull-left" href="{{ route('user-management-agent-create') }}">Add New Merchant</a>

      <form class="pull-left" method="GET" id="search-form2">
        <div class="input-group">
            <input type="text" name="global_search" placeholder="Global Search" class="form-control" value="{{ (isset($_GET['global_search']) && $_GET['global_search'] != '')?$_GET['global_search']:'' }}">
            <div class="input-group-append">
                <button class="btn btn-primary btn-sm" type="submit" id="extraSearch2">Search</button>
            </div>
        </div>
      </form>
    </div>
    <div class="col-lg-12"> 
      <div class="card">
        <div class="card-header">
          <div class="mr-auto pr-3">
            <h4 class="card-title">Merchant Management</h4>
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
            <button type="button" class="btn btn-warning bell-link btn-sm"> <i class="fa fa-search-plus"></i> Advanced
              Search</button>
            <a href="{{ route('rp.user-management') }}" class="btn btn-danger btn-sm ">Reset</a>
          </div>
          <a href="{{ route('rp.user-management', ['type' => 'xlsx']+request()->all()) }}"
            class="ml-2 btn btn-secondary btn-sm" id="ExcelLink"><i class="fa fa-download"></i> Export Excel </a>

        </div>
        <div class="card-body">
          <div class="table-responsive ">
            <table id="merchant_List" class="table table-responsive-md ">
              <thead>
                <tr>
                  <th>User Name </th>
                  <th>Company name</th>
                  <th>Status</th>
                  <th class="text-center">Active</th>
                  <th>Created Date</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
              @if(!empty($merchantManagementData) && $merchantManagementData->count())
                @foreach($merchantManagementData as $k=>$value)
                <tr>
                  <td>{{$value->name}}</td>
                  <td>{{$value->business_name}}</td>
                  <td>
                    @if($value->email_verified_at != NULL)
                    <span class="badge badge-success badge-sm">Email Verification - Verified</span>
                    @else
                    <span class="badge badge-danger badge-sm">Email Verification - Unverified</span>
                    @endif
                    <br>
                    @if($value->appStatus == '1')
                    <span class="badge badge-info badge-sm">Application - In Progress</span>
                    @elseif($value->appStatus == '2')
                    <span class="badge badge-info badge-sm">Application - Incomplete</span>
                    @elseif($value->appStatus == '3')
                    <span class="badge badge-danger badge-sm">Application - Rejected</span>
                    @elseif($value->appStatus == '4')
                    <span class="badge badge-success badge-sm">Application - Pre Approval</span>
                    @elseif($value->appStatus == '5')
                    <span class="badge badge-warning badge-sm">Application - Agreement Sent</span>
                    @elseif($value->appStatus == '6')
                    <span class="badge badge-secondary badge-sm">Application - Agreement Received</span>
                    @elseif($value->appStatus == '7')
                    <span class="badge badge-danger badge-sm">Application - Not Interested</span>
                    @elseif($value->appStatus == '8')
                    <span class="badge badge-danger badge-sm">Application - Terminated</span>
                    @elseif($value->appStatus == '9')
                    <span class="badge badge-danger badge-sm">Application - Decline</span>
                    @elseif($value->appStatus == '10')
                    <span class="badge badge-success badge-sm">Application - Rate Accepted</span>
                    @elseif($value->appStatus == '11')
                    <span class="badge badge-success badge-sm">Application - Signed Agreement</span>
                    @else
                    <span class="badge badge-danger badge-sm">Application - Pending</span>
                    @endif
                  </td>
                  <td class="text-center">
                    @if($value->is_active == 1)
                    <label class="custom-control overflow-checkbox" style="padding-left: 0px;">
                      <input type="checkbox" class="overflow-control-input" name="is_active" id="is_active{{$value->id}}"
                          data-id="{{$value->id}}" checked>
                      <span class="overflow-control-indicator"></span>
                      <span class="overflow-control-description"></span>
                    </label>
                    @else
                    <label class="custom-control overflow-checkbox" style="padding-left: 0px;">
                      <input type="checkbox" class="overflow-control-input" name="is_active" id="is_active{{$value->id}}"
                          data-id="{{$value->id}}">
                      <span class="overflow-control-indicator"></span>
                      <span class="overflow-control-description"></span>
                    </label>
                    @endif
                  </td>
                  <td>{{ $value->created_at->format('d-m-Y') }}</td>
                  <td>
                    <div class="dropdown">
                      <a href="#" class="btn btn-primary sharp" data-toggle="dropdown" aria-expanded="true"><svg
                          xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="18px"
                          height="18px" viewBox="0 0 24 24" version="1.1">
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
                        @if($value->appStatus == '')
                          <li class="dropdown-item">
                            <a href="{{ route('user-management-application-create',$value->id) }}"
                              class="dropdown-a"><i class="fa fa-plus text-primary mr-2"></i> Create Application</a>
                          </li>
                        @else
                          @if($value->appStatus <= '2')
                          <li class="dropdown-item">
                            <a href="{{ route('user-management-application-edit',$value->id) }}"
                                class="dropdown-a"> <i class="fa fa-edit text-warning mr-2"></i> Edit Application</a>
                          </li>
                          @endif
                          <li class="dropdown-item">
                            <a href="{{ route('user-management-application-show',$value->id) }}"
                              class="dropdown-a"><i class="fa fa-eye text-primary mr-2"></i>
                              Show Application</a>
                          </li>
                        @endif
                        @if($value->email_verified_at == '')
                            <li class="dropdown-item">
                            <a href="{{ route('user-management-resendverification',$value->id) }}" class="dropdown-a"><i class="fa fa-repeat text-primary mr-2" aria-hidden="true"></i> Resend Verification Link</a>
                            </li>
                        @endif
                      </ul>
                    </div>
                  </td>
                </tr>
                @endforeach
              @else
                  <tr>
                      <td colspan="6">
                          <p class="text-center"><strong>No Merchant found</strong></p>
                      </td>
                  </tr>
              @endif
              </tbody>
            </table>
          </div>
            <div class="pagination-wrap">
                {!! $merchantManagementData->appends($_GET)->links() !!}
            </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('customScript')
<script type="text/javascript">
$('body').on('change', 'input[name="is_active"]', function() {
      var id        = $(this).data('id');
      var is_active = '0';
      // change the value based on check / uncheck
      if ($(this).prop("checked") == true) {
          var is_active = '1';
      }
      $.ajax({
          type: 'POST',
          context: $(this),
          url:'{{ route('user-deactive-for-rp') }}',
          data: {
              '_token': '{{ csrf_token() }}',
              'is_active': is_active,
              'id': id
          },
          success: function(data) {
              if (data.success == true) {
                  toastr.success('Merchant activation changed successfully!!');
                  window.setTimeout(
                        function(){
                            location.reload(true)
                        },
                        2000
                    );
              } else {
                  toastr.error('Something went wrong!!');
              }
          },
      });
  });
</script>

<script src="{{ storage_asset('newIpay/assets/custom_js/RP/custom.js') }}"></script>
@endsection

