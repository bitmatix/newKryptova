<?php

namespace App\Http\Controllers\WLAgent;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use App\Exports\WLRPUserExport;
use App\WLAgent;
use App\Application;
use App\Categories;
use App\User;
use View;
use Redirect;
use Hash;
use Auth;

class WLAgentUserBaseController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $agentUserWL;
    public function __construct()
    {
        view()->share('WLAgentUserTheme', 'layouts.WLAgent.default');

        $this->middleware(function ($request, $next) {
            $userData = WLAgent::where('wl_agents.id', auth()->guard('agentUserWL')->user()->id)
                ->first();

            view()->share('userData', $userData);
            return $next($request);
        });

        $this->wlAgentUser = new WLAgent;
        $this->User = new User;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard(Request $request)
    {
        return view('WLAgent.dashboard');
    }

    public function merchantManagement(Request $request)
    {
        $input = \Arr::except($request->all(), array('_token', '_method'));

        if (isset($input['noList'])) {
            $noList = $input['noList'];
        } else {
            $noList = 10;
        }

        $dataT = $this->User->getMainWLUserData($input, $noList);

        $companyName = Application::select('applications.user_id', 'applications.business_name')
                            ->join('users','users.id','applications.user_id')
                            ->orderBy('users.id', 'desc')
                            ->where('users.is_white_label','1')
                            ->where('users.white_label_agent_id', auth()->guard('agentUserWL')->user()->id)
                            ->get();

        $payment_gateway_id = \DB::table('middetails')->get();

        $categories = Categories::orderBy('name')->get();

        return view('WLAgent.merchantManagement.index',compact('dataT','noList','companyName','payment_gateway_id','categories'));
    }

    public function export(Request $request)
    {
        return Excel::download(new WLRPUserExport, 'User_List_Excel_' . date('d-m-Y') . '.xlsx');
    }

    public function toDelimitedString($array)
    {
        $data = '';
        foreach ($array as $value) {
            $data .= '"' . implode(",", $value) . ' \n" + ';
        }
        $data = rtrim($data, ' ');
        return rtrim($data, '+');
    }

    public function profile()
    {
        $data = WLAgent::where('wl_agents.id', auth()->guard('agentUserWL')->user()->id)
            ->first();

        return view('WLAgent.profile.index', compact('data'));
    }

    public  function updateProfile(Request $request)
    {
        $input = $request->all();

        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:wl_agents,email,' . auth()->guard('agentUserWL')->user()->id,
            'password' => 'confirmed',
        ]);

        $input = \Arr::except($input, array('_token', 'password_confirmation'));
        if ($input['password'] != null) {
            $input['token'] = $input['password'];
            $input['password'] = bcrypt($input['password']);
        } else {
            $input = \Arr::except($input, array('password'));
        }

        $this->wlAgentUser->updateData(auth()->guard('agentUserWL')->user()->id, $input);

        notificationMsg('success', 'Profile Updated Successfully!');

        return redirect()->route('wl-profile-rp');
    }

    public function rateFee(Request $request)
    {
        return view('WLAgent.merchantManagement.rateFee');
    }
}
