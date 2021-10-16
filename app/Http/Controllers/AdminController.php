<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Auth;
use Session;
use View;
use Redirect;
use App\User;
use App\Agent;
use App\Bank;
use App\Ticket;
use App\Warning;
use App\SendMail;
use App\SubUsers;
use App\Notification;
use App\Transaction;
use App\Application;
use Carbon\Carbon;
use App\Http\Requests;
use Illuminate\Http\Request;
use App\Exports\ActiveMerchantExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Mail\adminEmailChange;
use Illuminate\Support\Facades\Mail;

class AdminController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        view()->share('adminTheme', 'layouts.appAdmin');
        view()->share('adminLogin', 'layouts.adminLogin');
        view()->share('projectTitle', 'PaymentDemo');

        $this->user = new User;
        $this->ticket = new Ticket;
        $this->Warning = new Warning;
        $this->SendMail = new SendMail;
        $this->Transaction = new Transaction;
        $this->Notification = new Notification;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard(Request $request)
    {
        $date = \Carbon\Carbon::today()->subDays(7)->format("Y-m-d");
        $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');
        $payment_gateway_id = (env('PAYMENT_GATEWAY_ID')) ? explode(",", env('PAYMENT_GATEWAY_ID')) : [];

        if (!empty($slave_connection)) {
            \DB::setDefaultConnection($slave_connection);
            $getDatabaseName = \DB::connection()->getDatabaseName();
            _WriteLogsInFile($getDatabaseName . " connection from admin dashboard", 'slave_connection');
        }

        $TransactionSummary = DB::table("transactions as t")
            ->select("currency", DB::raw("sum(if(t.status = '1', amount, 0.00)) as successAmount"), DB::raw("sum(if(t.status = '1', 1, 0)) as successCount"), DB::raw("sum(if(t.status = '0' , amount,0.00 )) as declinedAmount"), DB::raw("sum(if(t.status = '0', 1, 0)) as declinedCount"), DB::raw("sum(if(t.status = '1' and t.chargebacks = '1' and t.chargebacks_remove = '0', amount, 0)) as chargebackAmount"), DB::raw("sum(if(t.status = '1' and t.chargebacks = '1' and t.chargebacks_remove = '0', 1, 0)) as chargebackCount"), DB::raw("sum(if(t.status = '1' and t.refund = '1' and t.refund_remove='0', amount, 0)) as refundAmount"), DB::raw("sum(if(t.status = '1' and t.refund = '1' and t.refund_remove='0', 1, 0)) as refundCount"), DB::raw("sum(if(t.status = '1' and t.is_flagged = '1' and t.is_flagged_remove= '0', amount, 0)) as flagAmount"), DB::raw("sum(if(t.status = '1' and t.is_flagged = '1' and t.is_flagged_remove= '0', 1, 0)) as flagCount"))
            ->where('transaction_date', '>=', $date)->whereNotIn('t.payment_gateway_id', $payment_gateway_id)->where('t.deleted_at', NULL)->groupBy("currency")->take(7)->get();

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
            )->whereNotIn('t.payment_gateway_id', $payment_gateway_id)->where('t.deleted_at', NULL)->first();
        $Transaction = Transaction::select("transactions.*", "users.name as userName", "middetails.bank_name")
            ->join('middetails', 'middetails.id', 'transactions.payment_gateway_id')
            ->join('users', 'users.id', 'transactions.user_id')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->whereNotIn('transactions.payment_gateway_id', ['1','2'])
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        $TransactionRefund = Transaction::select("transactions.*", "users.name as userName", "middetails.bank_name")
            ->join('middetails', 'middetails.id', 'transactions.payment_gateway_id')
            ->join('users', 'users.id', 'transactions.user_id')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->whereNotIn('transactions.payment_gateway_id', ['1','2'])
            ->where('transactions.refund', '1')
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        $TransactionChargebacks = Transaction::select("transactions.*", "users.name as userName", "middetails.bank_name")
            ->join('middetails', 'middetails.id', 'transactions.payment_gateway_id')
            ->join('users', 'users.id', 'transactions.user_id')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->whereNotIn('transactions.payment_gateway_id', ['1','2'])
            ->where('transactions.chargebacks', '1')
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        $TransactionFlagged = Transaction::select("transactions.*", "users.name as userName", "middetails.bank_name")
            ->join('middetails', 'middetails.id', 'transactions.payment_gateway_id')
            ->join('users', 'users.id', 'transactions.user_id')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->whereNotIn('transactions.payment_gateway_id', ['1','2'])
            ->where('transactions.chargebacks', '0')
            ->where('transactions.is_flagged', '1')
            ->where('transactions.is_flagged_remove', '0')
            ->whereNull('transactions.deleted_at')
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        $Ticket = Ticket::select("tickets.*")
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        $RateDecline = Application::select('applications.*', 'users.email as email')
            ->join('users', 'users.id', 'applications.user_id')
            ->where('applications.status', '9')
            ->orderBy('applications.id', 'desc')
            ->take(5)
            ->get();

        $RateAccepted = Application::select('applications.*', 'users.email as email')
            ->join('users', 'users.id', 'applications.user_id')
            ->where('users.is_rate_sent', '2')
            ->where('applications.status', '10')
            ->orderBy('applications.id', 'desc')
            ->take(5)
            ->get();

        $SignedAgreement = Application::select('applications.*', 'users.email as email')
            ->join('users', 'users.id', 'applications.user_id')
            ->where('applications.status', '11')
            ->orderBy('applications.id', 'desc')
            ->take(5)
            ->get();

        $value = '7';
        return view('admin.dashboard', compact(
            'Transaction',
            'TransactionRefund',
            'TransactionChargebacks',
            'TransactionFlagged',
            'Ticket',
            'TransactionSummary',
            'transaction',
            'RateDecline',
            'RateAccepted',
            'SignedAgreement',
            'value'
        ));
    }

    public function transactionSummaryFilter(Request $request)
    {
        if ($request->ajax()) {
            $value = $request->value;
            $date = \Carbon\Carbon::today()->subDays($value)->format("Y-m-d");

            $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');
            $payment_gateway_id = (env('PAYMENT_GATEWAY_ID')) ? explode(",", env('PAYMENT_GATEWAY_ID')) : [];

            if (!empty($slave_connection)) {
                \DB::setDefaultConnection($slave_connection);
                $getDatabaseName = \DB::connection()->getDatabaseName();
                _WriteLogsInFile($getDatabaseName . " connection from admin dashboard", 'slave_connection');
            }
        
            $TransactionSummary = DB::table("transactions as t")
            ->select("currency", DB::raw("sum(if(t.status = '1', amount, 0.00)) as successAmount"), DB::raw("sum(if(t.status = '1', 1, 0)) as successCount"), DB::raw("sum(if(t.status = '0' , amount,0.00 )) as declinedAmount"), DB::raw("sum(if(t.status = '0', 1, 0)) as declinedCount"), DB::raw("sum(if(t.status = '1' and t.chargebacks = '1' and t.chargebacks_remove = '0', amount, 0)) as chargebackAmount"), DB::raw("sum(if(t.status = '1' and t.chargebacks = '1' and t.chargebacks_remove = '0', 1, 0)) as chargebackCount"), DB::raw("sum(if(t.status = '1' and t.refund = '1' and t.refund_remove='0', amount, 0)) as refundAmount"), DB::raw("sum(if(t.status = '1' and t.refund = '1' and t.refund_remove='0', 1, 0)) as refundCount"), DB::raw("sum(if(t.status = '1' and t.is_flagged = '1' and t.is_flagged_remove= '0', amount, 0)) as flagAmount"), DB::raw("sum(if(t.status = '1' and t.is_flagged = '1' and t.is_flagged_remove= '0', 1, 0)) as flagCount"))
                ->where('t.transaction_date', '>=', $date)->whereNotIn('t.payment_gateway_id', $payment_gateway_id)->where('t.deleted_at', NULL)->groupBy("t.currency")->take(7)->get();
            $html = view('partials.adminDashboard.dashboardTransactionSummary', compact('TransactionSummary', 'value'))->render();
            return response()->json(['status' => 200, 'html' => $html]);
        }
    }
    public function changeStatus(Request $request, $id)
    {
        if (checkAdmin(auth()->guard('admin')->user()->id) == 0) {

            notificationMsg('info', 'Only Super Admin Can Do This!');

            return redirect()->back();
        }
        $status = $request->get('status');

        if ($status == 0) {
            \DB::table('payments')
                ->where('id', $id)
                ->update(['status' => $status]);
        } elseif ($status == 1) {
            \DB::table('payments')
                ->where('id', $id)
                ->update(['status' => $status]);
        } else {
            \DB::table('payments')
                ->where('id', $id)
                ->update(['status' => $status]);
        }

        \Session::put('success', 'Payment Status Updated Successfully!');
        return redirect()->back();
    }
    public function profile()
    {
        $data = \DB::table('admins')->where('id', auth()->guard('admin')->user()->id)->first();
        return view('admin.profile', compact('data'));
    }

    public function updateProfile(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:admins,email,' . $id,
        ]);
        $input = \Arr::except($request->all(), array('_token', '_method'));
        $admins = \DB::table('admins')->where('id', $id)->first();
        if ($admins->email != $input["email"]) {
            if (empty($admins->token)) {
                $data["id"] = $admins->id;
                $data['token'] = \Str::random(40) . time();
                $input['token'] = $data['token'];
                $input["email_changes"] = $input["email"];
                $data["name"] = $input["name"];
                $data["email"] = $input["email"];
                Mail::to($admins->email)->send(new adminEmailChange($data));
                unset($input["email"]);
                \DB::table('admins')->where('id', $id)->update($input);
                notificationMsg('success', 'You will shortly receive an email to activate your new email.');
            } else {
                notificationMsg('error', 'We already received an email change request.');
            }
        } else {
            \DB::table('admins')->where('id', $id)->update($input);
            Session::put('user_name', $input["name"]);
            notificationMsg('success', 'Profile Update Successfully!');
        }
        return redirect()->back();
    }

    public function resendMail()
    {
        $adminId = auth()->guard('admin')->user()->id;
        $admins = \DB::table('admins')->where('id', $adminId)->first();
        if (!is_null($admins)) {
            $data["id"] = $admins->id;
            $data['token'] = \Str::random(40) . time();
            $data["name"] = $admins->name;
            $data["email"] = $admins->email_changes;
            \DB::table('admins')->where('id', $adminId)->update(['token' => $data['token']]);
            Mail::to($admins->email)->send(new adminEmailChange($data));
            notificationMsg('success', 'You will shortly receive an email to activate your new email.');
        } else {
            notificationMsg('error', 'Something went Wrong.!');
        }
        return redirect()->back();
    }

    public function changePass(Request $request)
    {

        $this->validate(
            $request,
            [
                'password' => 'required|min:8|confirmed|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
            ],
            ['password.regex' => 'Enter valid format.(One Upper,Lower,Numeric,and Special character.)']
        );

        $input = \Arr::except($request->all(), array('_token', 'password_confirmation'));
        \DB::table('admins')
            ->where('id', auth()->guard('admin')->user()->id)
            ->update(['password' => bcrypt($input['password'])]);
        $notification = [
            'user_id' => auth()->guard('admin')->user()->id,
            'sendor_id' => auth()->guard('admin')->user()->id,
            'type' => 'admin',
            'title' => 'Password Reset',
            'body' => 'Password Updated successfully',
            'url' => '/dashboard',
            'is_read' => '0'
        ];

        $realNotification = addNotification($notification);
        \Session::put('success', 'Your Password successfully Updated');
        return Redirect::back();
    }

    public function userLoginByAdmin(Request $request)
    {
        if (auth()->guard('admin')->user()) {
            $user = User::where('email', $request->input('email'))->first();
            if (isset($user)) {
                Auth::login($user);
                notificationMsg('success', 'User Login Successful!');
                return redirect()->route('dashboardPage');
            } else {
                notificationMsg('warning', 'This User is not available.');
                return redirect()->back();
            }
        } else {
            return redirect()->route('login');
        }
    }

    public function subUserLoginByAdmin(Request $request)
    {
        if (auth()->guard('admin')->user()) {
            $user = SubUsers::where('email', $request->input('email'))->first();
            if (isset($user)) {
                Auth::guard('subUsers')->login($user);
                notificationMsg('success', 'User Login Successful!');
                return redirect()->route('dashboard');
            } else {
                notificationMsg('warning', 'This User is not available.');
                return redirect()->back();
            }
        } else {
            return redirect()->route('login');
        }
    }

    public function agentLoginByAdmin(Request $request)
    {
        if (auth()->guard('admin')->user()) {
            $agent = Agent::where('email', $request->input('email'))->first();
            if (isset($agent)) {
                Auth::guard('agentUser')->login($agent);
                notificationMsg('success', 'Agent Login Successfully!');
                return redirect()->route('rp.dashboard');
            } else {
                notificationMsg('warning', 'This Agent is not available.');
                return redirect()->back();
            }
        } else {
            return redirect()->route('login');
        }
    }

    public function bankLoginByAdmin(Request $request)
    {
        if (auth()->guard('admin')->user()) {
            $bank = Bank::where('email', $request->input('email'))->first();
            if (isset($bank)) {
                Auth::guard('bankUser')->login($bank);
                notificationMsg('success', 'Bank Login Successfully!');
                return redirect()->route('bank.dashboard');
            } else {
                notificationMsg('warning', 'This Bank is not available.');
                return redirect()->back();
            }
        } else {
            return redirect()->route('login');
        }
    }

    public function verifyAdminChangeEmail(Request $request)
    {
        $check = DB::table('admins')->where('id', $request->id)->first();
        if (!is_null($check)) {
            if (!empty($check->email_changes)) {
                \DB::table('admins')->where('id', $request->id)->update(['email' => $check->email_changes, 'token' => '', "email_changes" => '']);
                \Session::put('success', 'Your new email has been changed successfully.');
                return redirect()->to('admin/profile');
            } else {
                \Session::put('error', 'Email already changed');
                return redirect()->to('admin/profile');
            }
        } else {
            return redirect()->to('login')->with('error', "Don't find your record.");
        }
    }

    public function saveLocalTimezone(Request $request)
    {
        if (!empty($request->timezone)) {
            \Session::put('localtimezone', $request->timezone);
        }

        return response([
            'status' => true,
            'message' => 'timezone save successfully.'
        ]);
    }
}
