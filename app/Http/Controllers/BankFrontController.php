<?php

namespace App\Http\Controllers;

use App\ApplicationNote;
use DB;
use Str;
use Mail;
use Auth;
use Session;
use View;
use Redirect;
use Illuminate\Validation\Rule;
// use App\Agent;
use Validator;
use App\Notification;
use Carbon\Carbon;
use App\Mail\AssignBankReplyToAdmin;
use App\Mail\SendForgotEmailBank;
use App\Mail\AgentOtpMail;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Hash;
use App\Agent;
use App\Admin;
use App\BankUsers;
use App\Providers\RouteServiceProvider;
use App\Application;
use App\TechnologyPartner;
use App\ApplicationAssignToBank;

use App\Categories;
use App\User;
use Storage;



class BankFrontController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    protected $banks;
    protected $agentUser;
    public function __construct()
    {
        view()->share('bankUserTheme', 'layouts.bank.default');


        $this->middleware(function ($request, $next) {
            $user_Id = auth()->guard('bank_user')->user();
            if ($user_Id == '' || $user_Id == null) {
                return redirect()->route('bank/login');
            }
            if ($user_Id->is_active == 0) {

                return redirect()->route('bank/logout');
            }
            $userData = BankUsers::where('bank_users.id', auth()->guard('bank_user')->user()->id)
                ->first();

            view()->share('userData', $userData);
            return $next($request);
        });
        $this->user = new User;
        $this->Application = new Application;
        $this->categories = new Categories;
        $this->BankUser = new BankUsers;
        $this->AppAssignToBank = new ApplicationAssignToBank;
    }

    // public function dashboard(Request $request)
    // {   //dd('Dashboard');
    //     //auth()->guard('bank_user')->user()
    //     return view('bank.dashboard');
    // }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard(Request $request)
    {
        $date = \Carbon\Carbon::today()->subDays(7)->format("Y-m-d");
        $authUser = auth()->guard('bank_user')->user();
        $pandingApplication = ApplicationAssignToBank::select("application_assign_to_banks.*", "application_assign_to_banks.status as bstatus", 'application_assign_to_banks.id as bapp_id', 'applications.*')
        ->join('applications', 'applications.id', 'application_assign_to_banks.application_id')
        ->where('application_assign_to_banks.status', '0')
        ->where('application_assign_to_banks.bank_id',$authUser->id)
        ->orderBy('application_assign_to_banks.id', 'desc')
        ->take(5)
        ->get();

        $approvedApplication = ApplicationAssignToBank::select("application_assign_to_banks.*", "application_assign_to_banks.status as bstatus", 'application_assign_to_banks.id as bapp_id', 'applications.*')
        ->join('applications', 'applications.id', 'application_assign_to_banks.application_id')
        ->where('application_assign_to_banks.status', '1')
        ->where('application_assign_to_banks.bank_id',$authUser->id)
        ->orderBy('application_assign_to_banks.id', 'desc')
        ->take(5)
        ->get();

        $rejectedApplication = ApplicationAssignToBank::select("application_assign_to_banks.*", "application_assign_to_banks.status as bstatus", 'application_assign_to_banks.id as bapp_id', 'applications.*')
        ->join('applications', 'applications.id', 'application_assign_to_banks.application_id')
        ->where('application_assign_to_banks.status', '2')
        ->where('application_assign_to_banks.bank_id',$authUser->id)
        ->orderBy('application_assign_to_banks.id', 'desc')
        ->take(5)
        ->get();

        $authUser = auth()->guard('bank_user')->user();

        return view('bank.dashboard', compact( 'pandingApplication', 'approvedApplication','rejectedApplication',

        ));
    }

    public function profile()
    {
        $data = \DB::table('bank_users')->where('id', auth()->guard('bank_user')->user()->id)->first();
        return view('bank.profile', compact('data'));
    }

    public function updateProfile(Request $request, $id)
    {
        $this->validate($request, [
            'bank_name' => 'required',
            'email' => ['required', Rule::unique('bank_users')->ignore($id)],
        ]);
        $input = \Arr::except($request->all(), array('_token', '_method'));
        $banks = \DB::table('bank_users')->where('id', $id)->first();
        $authUser = auth()->guard('bank_user')->user();
        //dd($authUser);
        if ($banks->email != $input["email"]) {
            if (empty($banks->token)) {
                $data["id"] = $banks->id;
                $data['token'] = \Str::random(40) . time();
                $input['token'] = $data['token'];
                // $input["email_changes"] = $input["email"];
                $data["bank_name"] = $input["bank_name"];
                $data["email"] = $input["email"];
                //Mail::to($banks->email)->send(new adminEmailChange($data));
                unset($input["email"]);
                \DB::table('bank_users')->where('id', $id)->update($input);
                //dd('You will shortly receive an email to activate your new email');
                \Session::put('success', 'You will shortly receive an email to activate your new email.');
            } else {
                \Session::put('error', 'We already received an email change request.');
            }
        } else {
            \DB::table('bank_users')->where('id', $id)->update($input);
            Session::put('user_name', $input["bank_name"]);

            \Session::put('success', 'Profile Update Successfully!');
            addToLog('Details Update Successfully.', $input, 'general');
        }
        return redirect()->back();
    }

    public function changePass(Request $request)
    {

        $this->validate(
            $request,
            [
                'password' => 'required|min:8|confirmed|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
                "current_password" => "required|min:8",
            ],
            ['password.regex' => 'Enter valid format.(One Upper,Lower,Numeric,and Special character.)']
        );

        $authUser = auth()->guard('bank_user')->user();
        // dd($authUser->id);
        $bank_user = BankUsers::where('id', $authUser->id)
            ->where('is_active', '!=' ,'0')
            ->first();

        if (!\Hash::check($request->input('current_password'), $bank_user->password)) {
            \Session::put('error', 'Invalid current password, Please try again');
            return redirect()->back();
        }

        $input = \Arr::except($request->all(), array('_token', 'password_confirmation'));
        \DB::table('bank_users')
            ->where('id', auth()->guard('bank_user')->user()->id)
            ->update(['password' => bcrypt($input['password'])]);
        $notification = [
            'user_id' => auth()->guard('bank_user')->user()->id,
            'sendor_id' => auth()->guard('bank_user')->user()->id,
            'type' => 'bank',
            'title' => 'Password Reset',
            'body' => 'Password Updated successfully',
            'url' => 'bank/dashboard',
            'is_read' => '0'
        ];

        $realNotification = addNotification($notification);
        \Session::put('success', 'Your Password successfully Updated');
        return Redirect::back();
    }

    // public function applicationList()
    // {
    //     //$data = \DB::table('bank_users')->where('id', auth()->guard('bank_user')->user()->id)->first();
    //     $data='';
    //     return view('bank.applications', compact('data'));
    // }

    public function applicationList(Request $request)
    {
        $authUser = auth()->guard('bank_user')->user();
        $start_date = '';
        $end_date = '';
        $website_url = '';
        $agent_id = '';
        $name = '';
        $email = '';
        $monthly_volume = '';
        $where = array();
        if ($request->category) {
            $where['category_id'] = $request->category;
        }
        if ($request->monthly_volume) {
            $monthly_volume = $request->monthly_volume;
        }
        if ($request->country) {
            $where['country'] = $request->country;
        }
        if ($request->technology_partner_id) {
            $where['technology_partner_id'] = $request->technology_partner_id;
        }

        if ($request->user_id) {
            $where['user_id'] = $request->user_id;
        }
        if ($request->website_url) {
            $website_url = $request->website_url;
        }
        if ($request->status || $request->status == '0') {
            $where['application_assign_to_banks.status'] = $request->status;
        }

        if ($request->start_date) {
            $start_date = $request->start_date;
        }
        if ($request->end_date) {
            $end_date = $request->end_date;
        }
        if ($request->agent_id) {
            $agent_id = $request->agent_id;
            if ($agent_id != 'no-agent') {
                $agent_id = (int)$agent_id;
            }
        }
        if ($request->name) {
            $name = $request->name;
        }
        if ($request->email) {
            $email = $request->email;
        }

        $input = \Arr::except($request->all(), array('_token', '_method'));

        if (isset($input['noList'])) {
            $noList = $input['noList'];
        } else {
            $noList = 10;
        }
        $data['bankUser'] = $this->BankUser::get();
        $data['categories'] = $this->categories::orderBy('name')->get();
        $data['noList'] = $noList;
        //$data['agentName'] = Agent::all();
        $data['technologyPartner'] = TechnologyPartner::all();

        $data['businessNames'] = Application::select("applications.*", "application_assign_to_banks.*",  'application_assign_to_banks.id as bapp_id')
            ->join('application_assign_to_banks', 'application_assign_to_banks.application_id', 'applications.id')
            ->where('application_assign_to_banks.bank_id',$authUser->id)
            ->orderBy('applications.id', 'desc')
            ->groupBy('applications.business_name')
            ->get();
        $authUser = auth()->guard('bank_user')->user();
        //dd($authUser->id);
        $data['applications'] = ApplicationAssignToBank::select("application_assign_to_banks.*", "application_assign_to_banks.status as bstatus", 'application_assign_to_banks.id as bapp_id', 'applications.*')
            ->join('applications', 'applications.id', 'application_assign_to_banks.application_id')
            ->where('application_assign_to_banks.bank_id',$authUser->id)
            ->when($where != null, function ($query) use ($where) {
                return $query->where($where);
            })
            ->when($monthly_volume != null, function ($query) use ($monthly_volume) {
                return $query->where('applications.monthly_volume', '>=', $monthly_volume);
            })
            ->when($start_date != null, function ($query) use ($start_date) {
                return $query->whereDate('applications.created_at', '>=', $start_date);
            })
            ->when($end_date != null, function ($query) use ($end_date) {
                return $query->whereDate('applications.created_at', '<=', $end_date);
            })
            ->when($website_url != null, function ($query) use ($website_url) {
                return $query->where('applications.website_url', '=', $website_url);
            })
            ->paginate($noList);

        //dd($data);
        return view('bank.applications', $data);
    }

    public function changeStatus(Request $request)
    {

        //$status = $request->get('status');
        $status = '1';

        $update = ApplicationAssignToBank::where('id', $request->input('id'))->update([
            "status" => $status
        ]);

        $get_user = auth()->guard('bank_user')->user();
        $get_admin = Admin::where('id',1)->first();
        //dd($update);
        $data = [
            'email' => $get_admin['email'],
            'user_id' => $get_user['id'],
            'bank_name' => $get_user['bank_name'],
            'user_email' => $get_user['email'],
        ];
        try {
            Mail::to($data['email'])->send(new AssignBankReplyToAdmin($data));
        } catch (\Exception $e) {
        }
        notificationMsg('success', 'Application Approved Successfully!');

        //return redirect()->route('bank-application-list');
        return $update;
    }
    public function changeRejectStatus(Request $request)
    {
        //dd($request->input());


        $update = ApplicationAssignToBank::where('id', $request->input('id'))->update([
            "status" => '2',
            "reject_reason" => $request->input('reject_reason')

        ]);
        $get_user = auth()->guard('bank_user')->user();
        $get_admin = Admin::where('id',1)->first();
        //dd($update);
        $data = [
            'email' => $get_admin['email'],
            'user_id' => $get_user['id'],
            'bank_name' => $get_user['bank_name'],
            'user_email' => $get_user['email'],
        ];
        try {
            Mail::to($data['email'])->send(new AssignBankReplyToAdmin($data));
        } catch (\Exception $e) {
        }
        notificationMsg('success', 'Application rejected successfully!');
        return $update;
    }


    public function applicationView(Request $request)
    {
        $id = $request->id;
        $data['technologyPartner'] = TechnologyPartner::all();
        $data['agents'] = Agent::all();


        $data['data'] = Application::select('applications.*', 'users.name', 'users.email', 'users.agent_commission', 'agreement_document_upload.sent_files as agreement_send', 'agreement_document_upload.files as agreement_received', 'agreement_document_upload.reassign_reason as agreement_reassign_reason', 'application_assign_to_banks.*')
            ->join('users', 'users.id', 'applications.user_id')
            ->join('application_assign_to_banks', 'application_assign_to_banks.application_id', 'applications.id')
            ->leftjoin('agreement_document_upload', 'agreement_document_upload.application_id', 'applications.id')
            ->with('user')
            ->with('category')
            ->with('technology_partner')
            ->where('application_assign_to_banks.id', $id)
            ->first();
        $authUser = auth()->guard('bank_user')->user();

        //dd($data);
        if ($authUser != '') {
            addToAdminLog('applications', 'Application detail viewed', $authUser, 'success');
        }
        return view('bank.applicationsView', $data);
    }

    public function downloadDocumentsUploade(Request $request)
    {
        $authUser = auth()->guard('bank_user')->user();
        if ($authUser != '') {
            addToAdminLog('', 'Referral partner document download', $authUser, 'success');
        }
        return Storage::disk('s3')->download($request->file);
    }


    public function getApplicationNote(Request $request)
    {
        $authUser = auth()->guard('bank_user')->user();

        $data = ApplicationNote::where('application_id',$request->id)
            ->where('user_id',$authUser->id)
            ->where('user_type','1')
            ->latest()->get();


        $html = view('partials.application.note',compact('data'))->render();

        return response()->json([
            'success' => '1',
            'html' => $html
        ]);
    }

    public function storeApplicationNote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'note' => 'required',
        ]);

        $input = \Arr::except($request->all(), array('_token', '_method'));
        $authUser = auth()->guard('bank_user')->user();
        if ($validator->passes()) {

            $input['application_id'] = $request->get('id');
            $input['user_id'] = $authUser->id;
            $input['note'] = $request->get('note');
            $input['user_type'] ='1';

            if (ApplicationNote::create($input)) {
                return response()->json(['success' => '1', 'id' => $request->get('id')]);
            } else {
                return response()->json(['success' => '0', 'id' => $request->get('id')]);
            }
        }
        return response()->json(['errors' => $validator->errors()]);
    }
}
