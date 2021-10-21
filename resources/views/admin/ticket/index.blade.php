@extends('layouts.admin.default')
@section('title')
Tickets
@endsection

@section('breadcrumbTitle')
<a href="{{ route('admin.dashboard') }}">Dashboard</a> / Tickets
@endsection

@section('content')
<div class="row">
   <div class="col-xl-12 col-lg-12">
      <div class="card">
         <div class="card-header">
            <div class="mr-auto pr-3">
               <h4 class="card-title">Ticket List</h4>
            </div>
         </div>
         <div class="card-body">
            <div class="table-responsive">
               <table class="table table-responsive-md">
                  <thead>
                     <tr>
                        <th class="text-center">No.</th>
                        <th class="text-center">Title</th>
                        <th class="text-center">Description</th>
                        <th class="text-center">Date created</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Department</th>
                        <th class="text-center">Action</th>
                     </tr>
                  </thead>
                  <tbody>
                     @foreach($tickets as $ticket)
                     <tr>
                        <td class="text-center">{{ $loop->index + 1 }}</td>
                        <td class="text-center">{{ $ticket->title }}</td>
                        <td class="text-center">{{ Str::limit($ticket->body,50) }}</td>
                        <td class="text-center">{{ convertDateToLocal($ticket->created_at, 'd-m-Y') }}</td>
                        <td class="text-center">
                           @if($ticket->status == 0)
                           <span class="badge badge-sm badge-info">Pending</span>
                           @elseif($ticket->status == 1)
                           <span class="badge badge-sm badge-warning">In Progress</span>
                           @elseif($ticket->status == 3)
                           <span class="badge badge-sm badge-danger">Closed</span>
                           @elseif($ticket->status == 2)
                           <span class="badge badge-sm badge-success">Reopened</span>
                           @else
                           <span></span>
                           @endif
                        </td>
                        <td class="text-center">
                           @if($ticket->department == 1)
                           <span class="badge badge-sm badge-primary">Technical</span>
                           @elseif($ticket->department == 2)
                           <span class="badge badge-sm badge-warning">Finance</span>
                           @else
                           <span class="badge badge-sm badge-success">Customer Service</span>
                           @endif
                        </td>
                        <td class="text-center">
                           <div class="d-flex align-items-center ms-3 ms-lg-5" id="kt_header_user_menu_toggle">
                              <!--begin::Menu wrapper-->
                               <div class="btn btn-icon btn-active-light-primary w-30px h-30px w-md-40px h-md-40px" data-kt-menu-trigger="click" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
                                   <svg
                                    xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                    width="18px" height="18px" viewBox="0 0 24 24" version="1.1">
                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                       <rect x="0" y="0" width="24" height="24"></rect>
                                       <circle fill="#FFF" cx="5" cy="12" r="2"></circle>
                                       <circle fill="#FFF" cx="12" cy="12" r="2"></circle>
                                       <circle fill="#FFF" cx="19" cy="12" r="2"></circle>
                                    </g>
                                 </svg>
                               </div>
                               <!--begin::Menu-->
                               <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-primary fw-bold py-4 fs-6 w-150px" data-kt-menu="true">
                                   <!--begin::Menu item-->
                                   
                                   <div class="menu-item px-5">
                                       <a href="{!! URL::route('admin.ticket.show',[$ticket->id]) !!}" class="menu-link px-5">Show</a>
                                   </div>
                                   <!--end::Menu item-->
                                   <div class="menu-item px-5">
                                       <a href="" class="dropdown-a delete_modal menu-link px-5" data-toggle="modal"
                                       data-target="#delete_modal"
                                       data-url="{{\URL::route('admin.ticket.destroy', $ticket->id)}}"
                                       data-id="{{ $ticket->id }}">Delete</a>
                                   </div>
                                    @if($ticket->status != 3)
                                    <div class="menu-item px-5">
                                       <a
                                       href="{!! URL::route('admin.ticket.close',[$ticket->id]) !!}"
                                       class="menu-link px-5"> Close</a>
                                    <div>
                                 @else
                                 <div class="menu-item px-5">
                                       <a
                                       href="{!! URL::route('admin.ticket.reopen',[$ticket->id]) !!}"
                                       class="menu-link px-5"> Reopen</a>
                                    <div>
                                 @endif
                                   <!--end::Menu item-->
                               </div>
                            <!--end::Menu-->
                            <!--end::Menu wrapper-->
                           </div>
                           <!-- <div class="dropdown ml-auto">
                              
                              <ul class="dropdown-menu dropdown-menu-right">
                                 
                                 <li class="dropdown-item"><a
                                       href="{!! URL::route('admin.ticket.show',[$ticket->id]) !!}"
                                       class="dropdown-a"><i class="fa fa-eye text-primary mr-2"></i> Show</a></li>
                                      
                                 <li class="dropdown-item">
                                    <a href="" class="dropdown-a delete_modal" data-toggle="modal"
                                       data-target="#delete_modal"
                                       data-url="{{\URL::route('admin.ticket.destroy', $ticket->id)}}"
                                       data-id="{{ $ticket->id }}"><i class="fa fa-trash text-danger mr-2"></i>
                                       Delete</a>
                                 </li>
                                     

                                 
                                 @if($ticket->status != 3)
                                 <li class="dropdown-item"><a
                                       href="{!! URL::route('admin.ticket.close',[$ticket->id]) !!}"
                                       class="dropdown-a"><i class="fa fa-times text-warning mr-2"></i> Close</a></li>
                                 @else
                                 <li class="dropdown-item"><a
                                       href="{!! URL::route('admin.ticket.reopen',[$ticket->id]) !!}"
                                       class="dropdown-a"><i class="fa fa-unlock text-success mr-2"></i> Reopen</a></li>
                                 @endif
                                 
                              </ul>
                           </div> -->
                        </td>
                     </tr>
                     @endforeach
                  </tbody>
               </table>
            </div>
         </div>
      </div>
   </div>
</div>
@endsection