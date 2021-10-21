@extends('layouts.bank.default')

@section('title')
Review Application
@endsection

@section('breadcrumbTitle')
<a href="{{ route('bank-dashboard') }}">Dashboard</a> / <a
  href="{{ route('bank-application-list') }}">Applications</a> / Review
@endsection

@section('customeStyle')
<link rel="stylesheet" href="{{ storage_asset('newIpay/assets/custom_css/sweetalert2.min.css') }}">
@endsection

@section('content')
<div class="row">
 
  
  <div class="col-xl-12 col-xxl-12">
    <div class="card height-auto">
      <div class="card-header d-block">
        <h4 class="card-title">Review Application</h4>
      </div>
      <div class="card-body">
        <div class="row">
          @include('partials.application.applicationShow')
        </div>
      </div>
    </div>
    <div class="card height-auto">
      <div class="card-header d-block">
        <h4 class="card-title">Documents List</h4>
      </div>
      <div class="card-body">
        <div class="row">
          @if ($data->licence_document != null)
          <div class="col-md-8 mt-2">Licence Document</div>
          <div class="col-md-4 mb-2">
            <a href="{{ getS3Url($data->licence_document) }}" target="_blank" class="btn btn-info btn-xxs">Show</a>
            <a href="{{ route('downloadDocumentsUploadeBank',['file'=>$data->licence_document]) }}"
              class="btn btn-warning btn-xxs">Download</a>
          </div>
          @endif
          <div class="col-md-6 mt-2">Passport</div>
          <div class="col-md-6 mb-2">
            <div class="row">
              @foreach (json_decode($data->passport) as $key => $passport )
              <div class="col-md-4 mt-2">File - {{ $key +1 }}</div>
              <div class="col-md-8 mt-2">
                <a href="{{ getS3Url($passport) }}" target="_blank" class="btn btn-info btn-xxs">Show</a>
                <a href="{{ route('downloadDocumentsUploadeBank',['file'=>$passport]) }}"
                  class="btn btn-warning btn-xxs">Download</a>
              </div>
              @endforeach
            </div>

          </div>

          <div class="col-md-8 mt-2">Articles Of Incorporation</div>
          <div class="col-md-4 mb-2">
            <a href="{{ getS3Url($data->company_incorporation_certificate) }}" target="_blank"
              class="btn btn-info btn-xxs">Show</a>
            <a href="{{ route('downloadDocumentsUploadeBank',['file'=>$data->company_incorporation_certificate]) }}"
              class="btn btn-warning btn-xxs">Download</a>
          </div>

          @if(isset($data->domain_ownership))
          <div class="col-md-8 mt-2">Domain Ownership</div>
          <div class="col-md-4 mb-2">
            <a href="{{ getS3Url($data->domain_ownership) }}" target="_blank"
              class="btn btn-info btn-xxs">Show</a>
            <a href="{{ route('downloadDocumentsUploadeBank',['file'=>$data->domain_ownership]) }}"
              class="btn btn-warning btn-xxs">Download</a>
          </div>
          @endif

          {{--@if(isset($data->latest_bank_account_statement))
          <div class="col-md-6 mt-2">Company's Bank Statement (last 180 days)</div>
          <div class="col-md-6 mb-2">
            <div class="row">
            @foreach (json_decode($data->latest_bank_account_statement) as $key => $bankStatement )
              <div class="col-md-4 mt-2">File - {{ $key +1 }}</div>
              <div class="col-md-8 mt-2">
                  <a href="{{ getS3Url($bankStatement) }}" target="_blank"
                      class="btn btn-info btn-xxs">Show</a>
                  <a href="{{ route('downloadDocumentsUploadeBank',['file'=>$bankStatement]) }}"
                      class="btn btn-warning btn-xxs">Download</a>
              </div>
              @endforeach
              </div>
          </div>
          @endif--}}

          {{--@if(isset($data->utility_bill))
          <div class="col-md-6 mt-2">Utility Bill</div>
          <div class="col-md-6 mb-2">
            <div class="row">
                @foreach (json_decode($data->utility_bill) as $key => $utilityBill )
                <div class="col-md-4 mt-2">File - {{ $key +1 }}</div>
                <div class="col-md-8 mt-2">
                    <a href="{{ getS3Url($utilityBill) }}" target="_blank"
                        class="btn btn-info btn-xxs">Show</a>
                    <a href="{{ route('downloadDocumentsUploadeBank',['file'=>$utilityBill]) }}"
                        class="btn btn-warning btn-xxs">Download</a>
                </div>
                @endforeach
            </div>
          </div>
          @endif--}}

          @if(isset($data->owner_personal_bank_statement))
          <div class="col-md-8 mt-2">UBO's Bank Statement (last 90 days)</div>
          <div class="col-md-4 mb-2">
            <a href="{{ getS3Url($data->owner_personal_bank_statement) }}" target="_blank"
              class="btn btn-info btn-xxs">Show</a>
            <a href="{{ route('downloadDocumentsUploadeBank',['file'=>$data->owner_personal_bank_statement]) }}"
              class="btn btn-warning btn-xxs">Download</a>
          </div>
          @endif

          @if(isset($data->previous_processing_statement) && $data->previous_processing_statement != null)
          <div class="col-md-6 mt-2">
            Processing History (if any)
          </div>
          <div class="col-md-6 mb-2">
            <div class="row">
              @php
              $previous_processing_statement_files = json_decode($data->previous_processing_statement);
              @endphp
              <div class="col-md-12">
                <div class="row">
                  @php
                  $count = 1;
                  @endphp
                  @foreach($previous_processing_statement_files as $key => $value)
                  <div class="col-md-4 mt-2">File - {{ $count }}</div>
                  <div class="col-md-8 mb-2">
                    <a href="{{ getS3Url($value) }}" target="_blank" class="btn btn-info btn-xxs">Show</a>
                    <a href="{{ route('downloadDocumentsUploadeBank',['file' => $value]) }}"
                      class="btn btn-warning btn-xxs">Download</a>
                  </div>
                  @php
                  $count++;
                  @endphp
                  @endforeach
                </div>
              </div>
            </div>
          </div>
          @endif
          @if ($data->moa_document != null)
          <div class="col-md-8 mt-2">MOA(Memorandum of Association) Document</div>
          <div class="col-md-4 mb-2">
            <a href="{{ getS3Url($data->moa_document) }}" target="_blank" class="btn btn-info btn-xxs">Show</a>
            <a href="{{ route('downloadDocumentsUploadeBank',['file'=>$data->moa_document]) }}"
              class="btn btn-warning btn-xxs">Download</a>
          </div>
          @endif
          @if(isset($data->extra_document) && $data->extra_document != null)
          <div class="col-md-6 mt-2">
            Additional Document
          </div>
          <div class="col-md-6 mb-2">
            <div class="row">
              @php
              $extra_document_files = json_decode($data->extra_document);
              @endphp
              <div class="col-md-12">
                <div class="row">
                  @php
                  $count = 1;
                  @endphp
                  @foreach($extra_document_files as $key => $value)
                  <div class="col-md-4 mt-2">File - {{ $count }}</div>
                  <div class="col-md-8 mb-2">
                    <a href="{{ getS3Url($value) }}" target="_blank" class="btn btn-info btn-xxs">Show</a>
                    <a href="{{ route('downloadDocumentsUploadeBank',['file' => $value]) }}"
                      class="btn btn-warning btn-xxs">Download</a>
                  </div>
                  @php
                  $count++;
                  @endphp
                  @endforeach
                </div>
              </div>
            </div>
          </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  
</div>





@endsection

@section('customScript')
<script src="{{ storage_asset('newIpay/assets/custom_js/sweetalert2.min.js') }}"></script>
<script src="{{ storage_asset('newIpay/assets/custom_js/admin/applications.js') }}"></script>
@endsection
