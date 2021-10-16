<?php

namespace App\Http\Controllers;

use Hash;
use Auth;
use DB;
use URL;
use View;
use Input;
use Session;
use Redirect;
use Validator;
use App\User;
use App\Ticket;
use App\Warning;
use App\SendMail;
use App\MIDDetail;
use App\Transaction;
use App\ImageUpload;
use App\Notification;
use App\PayoutSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use App\Mail\userEmailChange;
use App\Http\Requests\UserBankDetailFormRequest;
use App\UserBankDetails;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $user;

    public function __construct()
    {
        $this->middleware('auth')->except(['directpayapi', 'gettransactiondetailsapi', 'hostedpayapi', 'cryptopayapi','bankpayapi', 'cardtokenizationapi']);
        $this->middleware(function ($request, $next) {
            $this->user = \Auth::user();
            return $next($request);
        });

        $this->user = new User;
        $this->ticket = new Ticket;
        $this->Warning = new Warning;
        $this->SendMail = new SendMail;
        $this->Transaction = new Transaction;
        $this->Notification = new Notification;
        $this->payoutSchedule = new PayoutSchedule;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function home(Request $request)
    {
        $input = \Arr::except($request->all(), array('_token', '_method'));

        $start_date = date(\Carbon\Carbon::today()->subDays(6));
        $end_date = date('Y-m-d 23:59:59');

        if (auth()->user()->main_user_id == 0) {
            $user_id = auth()->user()->id;
        } else {
            $user_id = auth()->user()->main_user_id;
        }

        $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');
        $payment_gateway_id = (env('PAYMENT_GATEWAY_ID')) ? explode(",", env('PAYMENT_GATEWAY_ID')) : [];

        if (!empty($slave_connection)) {
            \DB::setDefaultConnection($slave_connection);
            $getDatabaseName = \DB::connection()->getDatabaseName();
            _WriteLogsInFile($getDatabaseName . " connection from merchant dashboard", 'slave_connection');
        }

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
            ->where('t.user_id', $user_id)
            ->whereNotIn('t.payment_gateway_id', $payment_gateway_id)
            ->where('t.deleted_at', NULL)
            ->first();

        $transactionWeek = DB::table("transactions as t")
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
            ->where('t.user_id', $user_id)
            ->whereNotIn('t.payment_gateway_id', $payment_gateway_id)
            ->where('t.deleted_at', NULL)
            ->where('t.transaction_date', '>=', $start_date)
            ->where('t.transaction_date', '<=', $end_date)
            ->first();

        $transactionsLine = DB::table("transactions as t")->select([
            DB::raw('DATE_FORMAT(DATE(transaction_date), "%d-%b") AS date'),
            DB::raw("sum(if(t.status = '1', 1, 0)) as successTransactions"),
            DB::raw("sum(if(t.status = '0', 1, 0)) as declinedTransactions"),
        ])
            ->where('t.transaction_date', '>=', $start_date)
            ->where('t.transaction_date', '<=', $end_date)
            ->where('t.user_id', $user_id)
            ->whereNotIn('t.payment_gateway_id', $payment_gateway_id)
            ->where('t.deleted_at', NULL)
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->toArray();

        $date = \Carbon\Carbon::today()->subDays(6)->format("Y-m-d");
        $TransactionSummary = DB::table("transactions as t")
            ->select("currency", DB::raw("sum(if(t.status = '1', amount, 0.00)) as successAmount"), DB::raw("sum(if(t.status = '1', 1, 0)) as successCount"), DB::raw("sum(if(t.status = '0' , amount,0.00 )) as declinedAmount"), DB::raw("sum(if(t.status = '0', 1, 0)) as declinedCount"), DB::raw("sum(if(t.status = '1' and t.chargebacks = '1' and t.chargebacks_remove = '0', amount, 0)) as chargebackAmount"), DB::raw("sum(if(t.status = '1' and t.chargebacks = '1' and t.chargebacks_remove = '0', 1, 0)) as chargebackCount"), DB::raw("sum(if(t.status = '1' and t.refund = '1' and t.refund_remove='0', amount, 0)) as refundAmount"), DB::raw("sum(if(t.status = '1' and t.refund = '1' and t.refund_remove='0', 1, 0)) as refundCount"), DB::raw("sum(if(t.status = '1' and t.is_flagged = '1' and t.is_flagged_remove= '0', amount, 0)) as flagAmount"), DB::raw("sum(if(t.status = '1' and t.is_flagged = '1' and t.is_flagged_remove= '0', 1, 0)) as flagCount"))
            ->where('t.transaction_date', '>=', $date)
            ->where('t.user_id', $user_id)
            ->whereNotIn('t.payment_gateway_id', $payment_gateway_id)
            ->where('t.deleted_at', NULL)
            ->groupBy("currency")
            ->get();

        return view('home', compact('transaction', 'transactionWeek', 'transactionsLine', 'TransactionSummary'));
    }

    public function getTransactionBreakUp(Request $request)
    {
        $input = $request->except('_token');

        if ($input['selectedValue'] == 1) {
            $start_date = date('Y-m-d 23:59:59');
        } elseif ($input['selectedValue'] == 2) {
            $start_date = date(\Carbon\Carbon::today()->subDays(6));
        } elseif ($input['selectedValue'] == 3) {
            $start_date = date(\Carbon\Carbon::today()->subDays(31));
        }

        $end_date = date('Y-m-d 23:59:59');

        if (auth()->user()->main_user_id == 0) {
            $user_id = auth()->user()->id;
        } else {
            $user_id = auth()->user()->main_user_id;
        }

        $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');
        $payment_gateway_id = (env('PAYMENT_GATEWAY_ID')) ? explode(",", env('PAYMENT_GATEWAY_ID')) : [];

        if (!empty($slave_connection)) {
            \DB::setDefaultConnection($slave_connection);
            $getDatabaseName = \DB::connection()->getDatabaseName();
            _WriteLogsInFile($getDatabaseName . " connection from merchant dashboard", 'slave_connection');
        }

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
            ->where('t.user_id', $user_id)
            ->whereNotIn('t.payment_gateway_id', $payment_gateway_id)
            ->where('t.deleted_at', NULL);
        if ($input['selectedValue'] == 1) {
            $transaction = $transaction->where('transaction_date', '<=', date('Y-m-d 23:59:59'))
                ->where('transaction_date', '>=', date('Y-m-d 00:00:00'));
        } else {
            $transaction = $transaction->where('transaction_date', '>=', $start_date)
                ->where('transaction_date', '<=', $end_date);
        }
        $transaction = $transaction->first();

        return response()->json([
            'status' => '1',
            'successfullC' => $transaction->successfullC,
            'declinedC' => $transaction->declinedC,
            'chargebackC' => $transaction->chargebackC,
            'suspiciousC' => $transaction->suspiciousC,
            'refundC' => $transaction->refundC
        ]);
    }

    public function transactionSummaryReport(Request $request)
    {
        $input = \Arr::except($request->all(), array('_token', '_method'));

        if (isset($input['start_date'])) {
            $start_date = date('Y-m-d 00:00:00', strtotime($input['start_date']));
        } else {
            $start_date = date(\Carbon\Carbon::today()->subDays(6));
        }

        if (isset($input['end_date'])) {
            $end_date = date('Y-m-d 23:59:59', strtotime($input['end_date']));
        } else {
            $end_date = date('Y-m-d 23:59:59');
        }

        if (auth()->user()->main_user_id == 0) {
            $user_id = auth()->user()->id;
        } else {
            $user_id = auth()->user()->main_user_id;
        }

        $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');

        if (!empty($slave_connection)) {
            \DB::setDefaultConnection($slave_connection);
            $getDatabaseName = \DB::connection()->getDatabaseName();
            _WriteLogsInFile($getDatabaseName . " connection from merchant transaction summary report", 'slave_connection');
        }

        $payment_gateway_id = (env('PAYMENT_GATEWAY_ID')) ? explode(",", env('PAYMENT_GATEWAY_ID')) : [];

        $TransactionSummary = DB::table("transactions as t")
            ->select("currency", 
                DB::raw("sum(if(t.status = '1', amount, 0.00)) as successAmount"), 
                DB::raw("sum(if(t.status = '1', 1, 0)) as successCount"),
                DB::raw("round((sum(if(t.status = '1', 1, 0))*100)/(sum(if(t.status = '1', 1, 0))+sum(if(t.status = '0', 1, 0))), 2) AS success_percentage"),
                DB::raw("sum(if(t.status = '0' , amount,0.00 )) as declinedAmount"),
                DB::raw("sum(if(t.status = '0', 1, 0)) as declinedCount"), 
                DB::raw("round((sum(if(t.status = '0', 1, 0))*100)/(sum(if(t.status = '1', 1, 0))+sum(if(t.status = '0', 1, 0))), 2) AS declined_percentage"),
                DB::raw("sum(if(t.status = '1' and t.chargebacks = '1' and t.chargebacks_remove = '0', amount, 0)) as chargebackAmount"), 
                DB::raw("sum(if(t.status = '1' and t.chargebacks = '1' and t.chargebacks_remove = '0', 1, 0)) as chargebackCount"), 
                DB::raw("round(sum(if(t.status = '1' and t.chargebacks = '1' and t.chargebacks_remove = '0', 1, 0))*100 / sum(if(t.status = '1', 1, 0)), 2) AS chargebacks_percentage"),
                DB::raw("sum(if(t.status = '1' and t.refund = '1' and t.refund_remove='0', amount, 0)) as refundAmount"), 
                DB::raw("sum(if(t.status = '1' and t.refund = '1' and t.refund_remove='0', 1, 0)) as refundCount"), 
                DB::raw("round(sum(if(t.status = '1' and t.refund = '1' and t.refund_remove='0', 1, 0)) / sum(if(t.status = '1', 1, 0)), 2) AS refund_percentage"),
                DB::raw("sum(if(t.status = '1' and t.is_flagged = '1' and t.is_flagged_remove= '0', amount, 0)) as flagAmount"), 
                DB::raw("sum(if(t.status = '1' and t.is_flagged = '1' and t.is_flagged_remove= '0', 1, 0)) as flagCount"),
                DB::raw("round(sum(if(t.status = '1' and t.is_flagged = '1' and t.is_flagged_remove= '0', 1, 0)) / sum(if(t.status = '1', 1, 0)),2) AS flagged_percentage"),
                DB::raw("sum(if(t.status = '1' and t.is_retrieval  = '1' and t.is_retrieval_remove= '0', 1, 0)) AS retrieval_count"),
                DB::raw("sum(if(t.status = '1' and t.is_retrieval  = '1' and t.is_retrieval_remove= '0', amount, 0)) AS retrieval_amount"),
                DB::raw("sum(if(t.status = '1' and t.is_retrieval  = '1' and t.is_retrieval_remove= '0', 1, 0)) / sum(if(t.status = '1', 1, 0)) AS retrieval_percentage"),
                DB::raw("sum(if(t.status = '5', 1, 0)) AS block_count"),
                DB::raw("sum(if(t.status = '5', amount, 0.00)) AS block_amount"),
                DB::raw("round(sum(if(t.status = '5', 1, 0)) / sum(if(t.status = '1', 1, 0)), 2) AS block_percentage")

                )

            ->where('t.transaction_date', '>=', $start_date)
            ->where('t.transaction_date', '<=', $end_date)
            ->where('t.user_id', $user_id)
            ->whereNotIn('t.payment_gateway_id', $payment_gateway_id)
            ->where('t.deleted_at', NULL);

        if (isset($input['currency']) && $input['currency'] != '') {
            $TransactionSummary = $TransactionSummary->where('currency', $input['currency']);
        }

        $TransactionSummary = $TransactionSummary->groupBy("currency")->get();
        //echo "<pre>";print_r($TransactionSummary);exit();
        return view('front.transaction_summary.report', compact('TransactionSummary'));
    }

    public function transactionSummary(Request $request)
    {
        $input = \Arr::except($request->all(), array('_token', '_method'));

        if (isset($input['start_date'])) {
            $start_date = date('Y-m-d 00:00:00', strtotime($input['start_date']));
        } else {
            $start_date = date(\Carbon\Carbon::today()->subDays(6));
        }

        if (isset($input['end_date'])) {
            $end_date = date('Y-m-d 23:59:59', strtotime($input['end_date']));
        } else {
            $end_date = date('Y-m-d 23:59:59');
        }


        if (auth()->user()->main_user_id == 0) {
            $user_id = auth()->user()->id;
        } else {
            $user_id = auth()->user()->main_user_id;
        }

        $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');

        if (!empty($slave_connection)) {
            \DB::setDefaultConnection($slave_connection);
            $getDatabaseName = \DB::connection()->getDatabaseName();
            _WriteLogsInFile($getDatabaseName . " connection from merchant transaction summary report", 'slave_connection');
        }

        $payment_gateway_id = (env('PAYMENT_GATEWAY_ID')) ? explode(",", env('PAYMENT_GATEWAY_ID')) : [];

        $transactionsLine = DB::table("transactions as t")->select([
            DB::raw('DATE_FORMAT(DATE(transaction_date), "%d-%b") AS date'),
            DB::raw("sum(if(t.status = '1', 1, 0)) as successTransactions"),
            DB::raw("sum(if(t.status = '0', 1, 0)) as declinedTransactions"),
        ])
            ->where('transaction_date', '>=', $start_date)
            ->where('transaction_date', '<=', $end_date)
            ->where('user_id', $user_id)
            ->whereNotIn('t.payment_gateway_id', $payment_gateway_id)
            ->where('t.deleted_at', NULL)
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->toArray();

        return view('front.transaction_summary.index', compact('transactionsLine'));
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

    public function getDashboardData(Request $request)
    {

        $input = \Arr::except($request->all(), array('_token', '_method'));
        if (\Auth::user()->main_user_id != '0')
            $userID = \Auth::user()->main_user_id;
        else
            $userID = \Auth::user()->id;

        // Latest Transactions Data
        $latestTransactionsData = $this->Transaction->getLatestTransactionsDash();
        $html_latestTransactionsData = view('latestTransactionsData', compact('latestTransactionsData'))->render();

        return response()->json([
            'success' => 1,
            'latestTransactionsData' => $html_latestTransactionsData,
        ]);
    }

    public function profile()
    {
        $data = $this->user::where('id', \Auth::user()->id)->first();

        return view('front.profile', compact('data'));
    }

    public  function updateProfile(Request $request, $id)
    {
        $user = \DB::table('users')->where('id', $id)->first();
        $this->validate($request, [
            'name' => 'required',
            'email' => ['required', Rule::unique('users')->ignore($id)->whereNull('deleted_at')],
        ]);
        $input = \Arr::except($request->all(), array('_token', '_method'));
        if ($user->email != $input["email"]) {
            if (empty($user->token)) {
                $data["id"] = $user->id;
                $data['token'] = Str::random(40) . time();
                $input['token'] = $data['token'];
                $input["email_changes"] = $input["email"];
                $data["name"] = $input["name"];
                $data["email"] = $input["email"];
                Mail::to($user->email)->send(new userEmailChange($data));
                unset($input["email"]);
                $this->user->updateData($id, $input);
                notificationMsg('success', 'You will shortly receive an email to activate your new email.');
            } else {
                notificationMsg('error', 'We already received an email change request.');
            }
        } else {
            $this->user->updateData($id, $input);
            notificationMsg('success', 'Details Updated Successfully!');
            addToLog('Details Update Successfully.', $input, 'general');
        }
        return redirect()->back();
    }

    public function resendEmailProfile()
    {
        $user = \DB::table('users')->where('id', auth()->user()->id)->first();
        if (!is_null($user)) {
            $data["id"] = $user->id;
            $data['token'] = \Str::random(40) . time();
            $data["name"] = $user->name;
            $data["email"] = $user->email_changes;
            \DB::table('users')->where('id', auth()->user()->id)->update(['token' => $data['token']]);
            Mail::to($user->email)->send(new userEmailChange($data));
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
                'current_password' => 'required',
                'password' => 'required|min:8|confirmed|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
            ],
            ['password.regex' => 'Enter valid format.(One Upper,Lower,Numeric,and Special character.)']
        );

        if (Hash::check($request->input('current_password'), auth()->user()->password)) {
            $input = \Arr::except($request->all(), array('_token', 'password_confirmation', 'current_password'));

            \DB::table('users')
                ->where('id', auth()->guard('web')->user()->id)
                ->update(['password' => bcrypt($input['password'])]);
            $notification = [
                'user_id' => auth()->guard('web')->user()->id,
                'sendor_id' => auth()->guard('web')->user()->id,
                'type' => 'user',
                'title' => 'Password Reset',
                'body' => 'Password Updated successfully',
                'url' => '/dashboard',
                'is_read' => '0'
            ];

            $realNotification = addNotification($notification);
            \Session::put('success', 'Your Password successfully Updated');

            addToLog('Password Change Successfully.', $input, 'general');

            return Redirect::route('setting');
        } else {
            $input = \Arr::except($request->all(), array('_token', 'password_confirmation'));

            \Session::put('error', 'Your old password is wrong!');

            addToLog('Your old password is wrong!', $input, 'general');

            return Redirect::route('setting');
        }
    }

    public function pagenotfound()
    {
        return view('front.pagenotfound');
    }

    public function userBankdetails(Request $request)
    {
        $bank = UserBankDetails::where('user_id', Auth::user()->id)->first();
        return view('front.bankDetails')->with('bank', $bank);
    }

    public function updateUserBankDetail(UserBankDetailFormRequest $request)
    {
        $input = \Arr::except($request->all(), array('_token'));
        $input['user_id'] = Auth::user()->id;
        if(
            isset($input['name']) || isset($input['address']) ||
            isset($input['aba_routing']) || isset($input['swift_code']) ||
            isset($input['iban']) || isset($input['account_name']) ||
            isset($input['account_number']) || isset($input['account_holder_address']) ||
            isset($input['additional_information'])
        ){
          $getBankDetails = UserBankDetails::where('user_id', Auth::user()->id)->first();

            if ($getBankDetails) {
                UserBankDetails::where('user_id', Auth::user()->id)->update($input);
                return back()->with('success', 'Bank Details updated successfully!');
            } else {
                UserBankDetails::create($input);
                return back()->with('success', 'Bank Details saved successfully!');
            }
        }else{
            return back()->with('error', 'Something is wrong.!');
        }

    }
}
