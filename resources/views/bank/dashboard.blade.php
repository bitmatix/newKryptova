@extends( $bankUserTheme)
@section('title')
Dashboard
@endsection

@section('breadcrumbTitle')
Dashboard
@endsection
@section('content')
<!-- @if(auth()->guard('bank_user')->user()->referral_code != NULL || auth()->guard('bank_user')->user()->referral_code !=
'')
<div class="row">
    <div class="col-xl-12 col-lg-12">
        <div class="widget-stat card">
            <div class="card-body p-4">
                <div class="media ai-icon">
                    <span class="mr-3 bgl-primary text-primary">
                        <i class="la la-users"></i>
                    </span>
                    
                </div>
            </div>
        </div>
    </div>
</div>
@endif -->

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header flex-wrap border-0 pb-0">
                <h4 class="text-black fs-20 mb-3">Dashboard Overview</h4>
            </div>
            
        </div>
    </div>
</div>
<div class="row">
    <div class="col-xl-12 col-lg-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Recent Pending Applications</h4>
                <a href="{!! url('bank/applications') !!}" class="btn btn-info btn-rounded">View All <i
                        class="fa fa-chevron-right"></i></a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-responsive-md">
                        <thead>
                            <tr>
                                <th style="min-width: 115px;">Status</th>
                                <th>Business Name</th>
                                <th>Business Type</th>
                                <th>Website URL</th>
                                <th>Creation Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        @if(!empty($pandingApplication) && $pandingApplication->count())
                            @foreach($pandingApplication as $key => $value)
                            @php $key++; @endphp
                            <tr>
                                <td>
                                  <span class="badge badge-warning badge-sm">Pending</span>
                                </td>
                                <td>{{ strlen($value->business_name) > 50 ? substr($value->business_name,0,30)."..." : $value->business_name }}
                                </td>
                                
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
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-xl-6 col-lg-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Recent Approved Applications</h4>
                
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-responsive-md">
                        <thead>
                            <tr>
                                <th style="min-width: 115px;">Status</th>
                                <th>Business Name</th>
                                <th>Business Type</th>
                                <th>Website URL</th>
                                <th>Creation Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        @if(!empty($approvedApplication) && $approvedApplication->count())
                            @foreach($approvedApplication as $key => $value)
                            @php $key++; @endphp
                            <tr>
                                <td>
                                   @if($value->bstatus == '1')
                                    <span class="badge badge-success badge-sm">Approved</span>
                                    @elseif($value->bstatus == '2')
                                    <span class="badge badge-danger badge-sm">Rejected</span>
                                    @endif
                                </td>
                                <td>{{ strlen($value->business_name) > 50 ? substr($value->business_name,0,30)."..." : $value->business_name }}
                                </td>
                                
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
            </div>
        </div>
    </div>
    <div class="col-xl-6 col-lg-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Recent Rejected Applications</h4>
                
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-responsive-md">
                        <thead>
                            <tr>
                                <th style="min-width: 115px;">Status</th>
                                <th>Business Name</th>
                                <th>Business Type</th>
                                <th>Website URL</th>
                                <th>Creation Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        @if(!empty($rejectedApplication) && $rejectedApplication->count())
                            @foreach($rejectedApplication as $key => $value)
                            @php $key++; @endphp
                            <tr>
                                <td>
                                   @if($value->bstatus == '1')
                                    <span class="badge badge-success badge-sm">Approved</span>
                                    @elseif($value->bstatus == '2')
                                    <span class="badge badge-danger badge-sm">Rejected</span>
                                    @endif
                                </td>
                                <td>{{ strlen($value->business_name) > 50 ? substr($value->business_name,0,30)."..." : $value->business_name }}
                                </td>
                                
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
            </div>
        </div>
    </div>
</div>



@endsection
@section('customScript')
<script src="https://cdn.lordicon.com//libs/frhvbuzj/lord-icon-2.0.2.js"></script>
<script src="{{ storage_asset('theme/vendor/peity/jquery.peity.min.js') }}"></script>
<script src="{{ storage_asset('theme/vendor/apexchart/apexchart.js') }}"></script>

<script>
    function Clipboard_CopyTo(value) {
    var tempInput = document.createElement("input");
    tempInput.value = value;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand("copy");
    document.body.removeChild(tempInput);
}
document.querySelector('#Copy').onclick = function() {
    var code = $('#link').attr("data-link");
    Clipboard_CopyTo(code);
    toastr.success("Referral link copied successfully!");
}
</script>

<script type="text/javascript">
   
</script>
@endsection