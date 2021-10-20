<?php

namespace App\Http\Controllers\Agent;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Admin;
use App\Agent;
use View;
use Redirect;
use Hash;
use Auth;
use App\Transaction;
use App\Application;
use App\Exports\AgentsMerchantExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use App\AgentBankDetails;
use App\Http\Requests\UserBankDetailFormRequest;

class AgentUserBaseController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $agentUser;
    public function __construct()
    {
        view()->share('agentUserTheme', 'layouts.agent.default');

        $this->middleware(function ($request, $next) {
            $userData = Agent::where('agents.id', auth()->guard('agentUser')->user()->id)
                ->first();

            view()->share('userData', $userData);
            return $next($request);
        });
        $this->user = new User;
        $this->Transaction = new Transaction;
        $this->agentUser = new Agent;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard(Request $request)
    {
        $input = \Arr::except($request->all(), array('_token', '_method'));

        $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');
        $payment_gateway_id = (env('PAYMENT_GATEWAY_ID')) ? explode(",", env('PAYMENT_GATEWAY_ID')) : [];

        if (!empty($slave_connection)) {
            \DB::setDefaultConnection($slave_connection);
            $getDatabaseName = \DB::connection()->getDatabaseName();
            _WriteLogsInFile($getDatabaseName . " connection from RP dashboard", 'slave_connection');
        }

        if(auth()->guard('agentUser')->user()->main_agent_id == 0){
            $agentId = auth()->guard('agentUser')->user()->id;
        }else{
            $agentId = auth()->guard('agentUser')->user()->main_agent_id;
        }

        $userIds = \DB::table('users')->where('agent_id', $agentId)->pluck('id');
        
        $transaction = DB::table("transactions as t")
            ->selectRaw(
                "
                        sum(if(t.status = '1', amount, 0.00)) as successfullV,
                        sum(if(t.status = '1', 1, 0)) as successfullC,
                        round((100*sum(if(t.status = '1', 1, 0)))/(sum(if(t.status = '0', 1, 0))+sum(if(t.status = '1', 1, 0))) , 2) as successfullP,
                        
                        sum(if(t.status = '0' , amount,0.00 )) as declinedV,
                        sum(if(t.status = '0', 1, 0)) as declinedC,
                        round((100*sum(if(t.status = '0', 1, 0)))/(sum(if(t.status = '0', 1, 0))+sum(if(t.status = '1', 1, 0))) ,2) as declinedP,
                        
                        sum(if(t.status = '1' and t.chargebacks = '1' and t.chargebacks_remove = '0', amount, 0)) as chargebackV,
                        sum(if(t.status = '1' and t.chargebacks = '1' and t.chargebacks_remove = '0', 1, 0)) as chargebackC,
                        round((100*sum(if(t.status = '1' and t.chargebacks = '1' and t.chargebacks_remove = '0', 1, 0)))/sum(if(t.status = '1', 1, 0)) ,2) as chargebackP,
                        
                        sum(if(t.status = '1' and t.is_flagged = '1' and t.is_flagged_remove= '0', amount, 0)) as suspiciousV,
                        sum(if(t.status = '1' and t.is_flagged = '1' and t.is_flagged_remove= '0', 1, 0)) as suspiciousC,
                        round((100*sum(if(t.status = '1' and t.is_flagged = '1' and t.is_flagged_remove= '0', 1, 0)))/sum(if(t.status = '1', 1, 0)) ,2) as suspiciousP,
                        
                        sum(if(t.status = '1' and t.refund = '1' and t.refund_remove='0', amount, 0)) as refundV,
                        sum(if(t.status = '1' and t.refund = '1' and t.refund_remove='0', 1, 0)) as refundC,
                        round((100*sum(if(t.status = '1' and t.refund = '1' and t.refund_remove='0', 1, 0)))/sum(if(t.status = '1', 1, 0)) ,2) as refundP",
            )
            ->whereIn('t.user_id', $userIds)
            ->whereNotIn('t.payment_gateway_id', $payment_gateway_id)
            ->where('t.deleted_at', NULL)
            ->first();
        
        
        $latestMerchants = $this->user->getAgentUsers();
        $latest10Transactions = $this->Transaction->latest10TransactionsForAgent();

        return view('agent.dashboard', compact('latest10Transactions', 'latestMerchants', 'transaction'));
    }

    // ================================================
    /*  method : toDelimitedString
    * @ param  :
    * @ Description : multidimentional array to csv format for line chart
    */ // ==============================================
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
        $data = Agent::where('agents.id', auth()->guard('agentUser')->user()->id)
            ->first();

        return view('agent.profile.index', compact('data'));
    }

    public  function updateProfile(Request $request)
    {
        $input = $request->all();

        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:agents,email,' . auth()->guard('agentUser')->user()->id,
            'password' => 'confirmed',
        ]);

        $input = \Arr::except($input, array('_token', 'password_confirmation'));
        if ($input['password'] != null) {
            $input['token'] = $input['password'];
            $input['password'] = bcrypt($input['password']);
        } else {
            $input = \Arr::except($input, array('password'));
        }

        $this->agentUser->updateData(auth()->guard('agentUser')->user()->id, $input);

        notificationMsg('success', 'Profile Updated Successfully!');

        return redirect()->route('profile-rp');
    }

    public function getUserManagement(Request $request)
    {
        $input = \Arr::except($request->all(), array('_token', '_method'));
        if (isset($input['type']) && $input['type'] == 'xlsx') {
            return Excel::download(new AgentsMerchantExport, 'Merchant_Excel_' . date('d-m-Y') . '.xlsx');
        }

        if(auth()->guard('agentUser')->user()->main_agent_id == 0){
            $agentId = auth()->guard('agentUser')->user()->id;
        }else{
            $agentId = auth()->guard('agentUser')->user()->main_agent_id;
        }

        if (isset($input['noList'])) {
            $noList = $input['noList'];
        } else {
            $noList = 10;
        }
        $merchantManagementData = $this->user->getUserDataForAgent($input, $noList);
        $businessName = Application::join('users', 'users.id', 'applications.user_id')
            ->where('users.agent_id', $agentId)
            ->pluck('business_name', 'user_id')
            ->toArray();
        return view('agent.userManagement.index', compact('merchantManagementData', 'businessName'));
    }

    public function show($id)
    {
        $user = $this->user->findUserDataForAgent($id);
        if ($user->agent_id ==  auth()->guard('agentUser')->user()->id) {
            return view('agent.userManagement.show', compact('user'));
        } else {
            return redirect()->route('agent.dashboard');
        }
    }

    public function showBankDetails()
    {
        $bank = AgentBankDetails::where('agent_id', auth()->guard('agentUser')->user()->id)->first();
        return view('agent.bankDetail')->with('bank', $bank);
    }

    public function updateBankDetail(UserBankDetailFormRequest $request)
    {
        $input = \Arr::except($request->all(), array('_token'));
        $input['agent_id'] = auth()->guard('agentUser')->user()->id;
        
        if(
            isset($input['name']) || isset($input['address']) ||
            isset($input['aba_routing']) || isset($input['swift_code']) ||
            isset($input['iban']) || isset($input['account_name']) ||
            isset($input['account_number']) || isset($input['account_holder_address']) ||
            isset($input['additional_information'])
        ){
            $getBankDetails = AgentBankDetails::where('agent_id', auth()->guard('agentUser')->user()->id)->first();

            if ($getBankDetails) {
                AgentBankDetails::where('agent_id', auth()->guard('agentUser')->user()->id)->update($input);
                return back()->with('success', 'Bank Details updated successfully!');
            } else {
                AgentBankDetails::create($input);
                return back()->with('success', 'Bank Details saved successfully!');
            }
        }else{
            return back()->with('error', 'Something is wrong.!');
        }

    }

    public function userActiveDeactive(Request $request)
    {
        $user_id = $request->id;
        $is_active = $request->is_active;

        if ($is_active == 1) {
            $user = $this->user->where('id', $user_id)->first();
            $userT =  $user->Tokens()->first();
            if (empty($userT)) {
                $token_api = $user->createToken('paypound')->plainTextToken;
                $this->user->where('id', $user_id)->update(['email_verified_at' => date('Y-m-d H:i:s'), 'api_key' => $token_api]);
            }
        }

        if ($this->user->where('id', $user_id)->update(['is_active' => $is_active])) {
            return response()->json(['success' => 1]);
        } else {
            return response()->json(['success' => 0]);
        }
    }
}
