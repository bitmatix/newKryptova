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
use Session;
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

        $userIds = \DB::table('users')->where('agent_id', auth()->guard('agentUser')->user()->id)->pluck('id');
        
        $transaction = DB::table("tx_transactions")
            ->selectRaw(
                '
                        sum(VOLs) as successfullV,
                        sum(TXs) as successfullC,
                        (100*sum(TXs))/(sum(TXd)+sum(TXs)) as successfullP,
                        
                        sum(VOLd) as declinedV,
                        sum(TXd) as declinedC,
                        (100*sum(TXd))/(sum(TXd)+sum(TXs)) as declinedP,
                        
                        sum(CBV) as chargebackV,
                        sum(CBTX) as chargebackC,
                        (100*sum(CBTX))/sum(TXs) as chargebackP,
                        
                        sum(FLGV) as suspiciousV,
                        sum(FLGTX) as suspiciousC,
                        (100*sum(FLGTX))/sum(TXs) as suspiciousP,
                        
                        sum(REFV) as refundV,
                        sum(REFTX) as refundC,
                        (100*sum(REFTX))/sum(TXs) as refundP',
            )
            ->whereIn('user_id', $userIds)
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
        \Session::put('success', 'Profile Updated Successfully!');
        return redirect()->route('profile-rp');
    }

    public function getUserManagement(Request $request)
    {
        $input = \Arr::except($request->all(), array('_token', '_method'));
        if (isset($input['type']) && $input['type'] == 'xlsx') {
            return Excel::download(new AgentsMerchantExport, 'Merchant_Excel_' . date('d-m-Y') . '.xlsx');
        }
        if (isset($input['noList'])) {
            $noList = $input['noList'];
        } else {
            $noList = 10;
        }
        $merchantManagementData = $this->user->getUserDataForAgent($input, $noList);
        $businessName = Application::join('users', 'users.id', 'applications.user_id')
            ->where('users.agent_id', auth()->guard('agentUser')->user()->id)
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
                $token_api = $user->createToken('kryptova')->plainTextToken;
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
