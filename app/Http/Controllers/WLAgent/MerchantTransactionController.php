<?php

namespace App\Http\Controllers\WLAgent;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use App\Application;
use App\Transaction;
use View;
use Storage;
use Redirect;
use Hash;
use Auth;
use Str;
use App\Exports\WLMerchantAllTransactionExport;
use App\Exports\WLMerchantCryptoTransactionExport;
use App\Exports\WLMerchantRefundTransactionExport;
use App\Exports\WLMerchantChargebacksTransactionExport;
use App\Exports\WLMerchantSuspiciousTransactionExport;
use App\Exports\WLMerchantDeclinedTransactionExport;
use App\Exports\WLMerchantRetrievalTransactionExport;
use App\Exports\WLMerchantTestTransactionExport;

class MerchantTransactionController extends WLAgentUserBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->transaction = new Transaction;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function allTransaction(Request $request)
    {
    	$input = \Arr::except($request->all(), array('_token', '_method'));

        if (isset($input['noList'])) {
            $noList = $input['noList'];
        } else {
            $noList = 10;
        }

        $data = $this->transaction->getAllMerchantTransactionDataWLAgent($input, $noList);

        $businessName = Application::join('users','users.id','applications.user_id')
                        ->orderBy('users.id', 'desc')
                        ->where('users.is_white_label','1')
                        ->where('users.white_label_agent_id', auth()->guard('agentUserWL')->user()->id)
                        ->pluck('applications.user_id', 'applications.business_name')
            			->toArray();
            			
        return view('WLAgent.merchantTransactions.index', compact('businessName', 'data'));
    }

    public function cryptoTransaction(Request $request)
    {
    	$input = \Arr::except($request->all(), array('_token', '_method'));

        if (isset($input['noList'])) {
            $noList = $input['noList'];
        } else {
            $noList = 10;
        }

        $data = $this->transaction->getMerchantCryptoTransactionDataWLAgent($input, $noList);

        $businessName = Application::join('users','users.id','applications.user_id')
                        ->orderBy('users.id', 'desc')
                        ->where('users.is_white_label','1')
                        ->where('users.white_label_agent_id', auth()->guard('agentUserWL')->user()->id)
                        ->pluck('applications.user_id', 'applications.business_name')
            			->toArray();

        return view('WLAgent.merchantTransactions.crypto', compact('businessName', 'data'));
    }

    public function refundTransaction(Request $request)
    {
    	$input = \Arr::except($request->all(), array('_token', '_method'));

        if (isset($input['noList'])) {
            $noList = $input['noList'];
        } else {
            $noList = 10;
        }

        $data = $this->transaction->getMerchantRefundTransactionDataWLAgent($input, $noList);

        $businessName = Application::join('users','users.id','applications.user_id')
                        ->orderBy('users.id', 'desc')
                        ->where('users.is_white_label','1')
                        ->where('users.white_label_agent_id', auth()->guard('agentUserWL')->user()->id)
                        ->pluck('applications.user_id', 'applications.business_name')
            			->toArray();

        return view('WLAgent.merchantTransactions.refund', compact('businessName', 'data'));
    }

    public function chargebacksTransaction(Request $request)
    {
    	$input = \Arr::except($request->all(), array('_token', '_method'));

        if (isset($input['noList'])) {
            $noList = $input['noList'];
        } else {
            $noList = 10;
        }

        $data = $this->transaction->getMerchantChargebacksTransactionDataWLAgent($input, $noList);

        $businessName = Application::join('users','users.id','applications.user_id')
                        ->orderBy('users.id', 'desc')
                        ->where('users.is_white_label','1')
                        ->where('users.white_label_agent_id', auth()->guard('agentUserWL')->user()->id)
                        ->pluck('applications.user_id', 'applications.business_name')
            			->toArray();

        return view('WLAgent.merchantTransactions.chargebacks', compact('businessName', 'data'));
    }

    public function retrievalTransaction(Request $request)
    {
    	$input = \Arr::except($request->all(), array('_token', '_method'));

        if (isset($input['noList'])) {
            $noList = $input['noList'];
        } else {
            $noList = 10;
        }

        $data = $this->transaction->getMerchantRetrievalTransactionDataWLAgent($input, $noList);

        $businessName = Application::join('users','users.id','applications.user_id')
                        ->orderBy('users.id', 'desc')
                        ->where('users.is_white_label','1')
                        ->where('users.white_label_agent_id', auth()->guard('agentUserWL')->user()->id)
                        ->pluck('applications.user_id', 'applications.business_name')
            			->toArray();

        return view('WLAgent.merchantTransactions.retrieval', compact('businessName', 'data'));
    }

    public function suspiciousTransaction(Request $request)
    {
    	$input = \Arr::except($request->all(), array('_token', '_method'));

        if (isset($input['noList'])) {
            $noList = $input['noList'];
        } else {
            $noList = 10;
        }

        $data = $this->transaction->getMerchantSuspiciousTransactionDataWLAgent($input, $noList);

        $businessName = Application::join('users','users.id','applications.user_id')
                        ->orderBy('users.id', 'desc')
                        ->where('users.is_white_label','1')
                        ->where('users.white_label_agent_id', auth()->guard('agentUserWL')->user()->id)
                        ->pluck('applications.user_id', 'applications.business_name')
            			->toArray();

        return view('WLAgent.merchantTransactions.suspicious', compact('businessName', 'data'));
    }

    public function declinedTransaction(Request $request)
    {
    	$input = \Arr::except($request->all(), array('_token', '_method'));

        if (isset($input['noList'])) {
            $noList = $input['noList'];
        } else {
            $noList = 10;
        }

        $data = $this->transaction->getMerchantDeclinedTransactionDataWLAgent($input, $noList);

        $businessName = Application::join('users','users.id','applications.user_id')
                        ->orderBy('users.id', 'desc')
                        ->where('users.is_white_label','1')
                        ->where('users.white_label_agent_id', auth()->guard('agentUserWL')->user()->id)
                        ->pluck('applications.user_id', 'applications.business_name')
            			->toArray();

        return view('WLAgent.merchantTransactions.declined', compact('businessName', 'data'));
    }

    public function testTransaction(Request $request)
    {
    	$input = \Arr::except($request->all(), array('_token', '_method'));

        if (isset($input['noList'])) {
            $noList = $input['noList'];
        } else {
            $noList = 10;
        }

        $data = $this->transaction->getMerchantTestTransactionDataWLAgent($input, $noList);

        $businessName = Application::join('users','users.id','applications.user_id')
                        ->orderBy('users.id', 'desc')
                        ->where('users.is_white_label','1')
                        ->where('users.white_label_agent_id', auth()->guard('agentUserWL')->user()->id)
                        ->pluck('applications.user_id', 'applications.business_name')
            			->toArray();

        return view('WLAgent.merchantTransactions.test', compact('businessName', 'data'));
    }

    public function exportAllTransaction(Request $request)
    {
        return Excel::download(new WLMerchantAllTransactionExport(), 'All_Transcation_Excel_' . date('d-m-Y') . '.xlsx');
    }

    public function exportCryptoTransaction(Request $request)
    {
        return Excel::download(new WLMerchantCryptoTransactionExport(), 'Crypto_Transcation_Excel_' . date('d-m-Y') . '.xlsx');
    }

    public function exportRefundTransaction(Request $request)
    {
        return Excel::download(new WLMerchantRefundTransactionExport(), 'Refund_Transcation_Excel_' . date('d-m-Y') . '.xlsx');
    }

    public function exportChargebacksTransaction(Request $request)
    {
        return Excel::download(new WLMerchantChargebacksTransactionExport(), 'Chargebacks_Transcation_Excel_' . date('d-m-Y') . '.xlsx');
    }

    public function exportSuspiciousTransaction(Request $request)
    {
        return Excel::download(new WLMerchantSuspiciousTransactionExport(), 'Suspicious_Transcation_Excel_' . date('d-m-Y') . '.xlsx');
    }

    public function exportDeclinedTransaction(Request $request)
    {
        return Excel::download(new WLMerchantDeclinedTransactionExport(), 'Declined_Transcation_Excel_' . date('d-m-Y') . '.xlsx');
    }

    public function exportRetrievalTransaction(Request $request)
    {
        return Excel::download(new WLMerchantRetrievalTransactionExport(), 'Retrieval_Transcation_Excel_' . date('d-m-Y') . '.xlsx');
    }

    public function exportTestTransaction(Request $request)
    {
        return Excel::download(new WLMerchantTestTransactionExport(), 'Test_Transcation_Excel_' . date('d-m-Y') . '.xlsx');
    }
}