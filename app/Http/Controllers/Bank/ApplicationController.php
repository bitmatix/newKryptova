<?php

namespace App\Http\Controllers\Bank;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Application;
use App\Admin;
use App\Categories;
use App\TechnologyPartner;
use App\ApplicationAssignToBank;
use App\Bank;
use App\Agent;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Mail\ApplicationApprovedToBankMail;
use App\Mail\ApplicationDeclinedToBankMail;
use View;
use Redirect;
use Storage;
use Hash;
use Auth;
use Mail;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use ZipArchive;
use League\Flysystem\Filesystem;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use App\Exports\AllApplicationsForBankExport;
use App\Exports\ApprovedApplicationsForBankExport;
use App\Exports\DeclinedApplicationsForBankExport;
use App\Exports\PendingApplicationsForBankExport;

class ApplicationController extends BankUserBaseController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->moduleTitleP = 'bank.applications';
        
        $this->user = new User;
        $this->bankUser = new Bank;
        $this->Application = new Application;
        $this->ApplicationAssignToBank = new ApplicationAssignToBank;
    }

    public function list(Request $request)
    {
        $input = \Arr::except($request->all(), array('_token', '_method'));

        if (isset($input['noList'])) {
            $noList = $input['noList'];
        } else {
            $noList = 15;
        }

        $categories = Categories::all();

        $data = $this->Application->getBankApplications($input,$noList);

        return view($this->moduleTitleP.'.index', compact('data','categories'));
    }

    public function listApproved(Request $request)
    {
        $input = \Arr::except($request->all(), array('_token', '_method'));

        if (isset($input['noList'])) {
            $noList = $input['noList'];
        } else {
            $noList = 15;
        }

        $categories = Categories::all();

        $data = $this->Application->getBankApplicationsApproved($input,$noList);

        return view($this->moduleTitleP.'.approved', compact('data','categories'));
    }

    public function listDeclined(Request $request)
    {
        $input = \Arr::except($request->all(), array('_token', '_method'));

        if (isset($input['noList'])) {
            $noList = $input['noList'];
        } else {
            $noList = 15;
        }

        $categories = Categories::all();

        $data = $this->Application->getBankApplicationsDeclined($input,$noList);

        return view($this->moduleTitleP.'.declined', compact('data','categories'));
    }

    public function listPending(Request $request)
    {
        $input = \Arr::except($request->all(), array('_token', '_method'));

        if (isset($input['noList'])) {
            $noList = $input['noList'];
        } else {
            $noList = 15;
        }

        $categories = Categories::all();

        $data = $this->Application->getBankApplicationsPending($input,$noList);

        return view($this->moduleTitleP.'.pending', compact('data','categories'));
    }

    public function applicationReview(Request $request)
    {
        $data['data'] = Application::select('applications.*', 'users.name', 'users.email', 'users.agent_commission')
            ->join('users', 'users.id', 'applications.user_id')
            ->where('applications.id', $request->id)
            ->first();

        return view('bank.applications.show', $data);
    }

    public function downloadDocumentsUploade(Request $request)
    {
        return Storage::disk('s3')->download($request->file);
    }

    public function downloadPDF(Request $request, $id)
    {
        $data = $this->Application->findData($id);
        view()->share('data', $data);
        
        $options = new Options();
        $options->setIsRemoteEnabled(true);
        $dompdf = new Dompdf($options);

        $dompdf->loadHtml(view('bank.applications.application_PDF'));

        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'landscape');
        // $dompdf->setPaper([0, 0, 1000.98, 900.85], 'landscape');
        $dompdf->setPaper([0, 0, 800.98, 700.85], 'landscape');

        // Render the HTML as PDF
        $dompdf->render();
        // Output the generated PDF to Browser
        $dompdf->stream($data->business_name.'.pdf');
    }

    public function downloadDOCS(Request $request, $id)
    {
        $zipData = Application::find($id);
        $users = User::where('id', $zipData->user_id)->first();

        if (!$zipData) {
            \Session::put('warning', 'Not Found Any Documents !');
            return redirect()->back();
        }
        // see laravel's config/filesystem.php for the source disk
        $file_names = Storage::disk('s3')->files('uploads/application-' . $users->id);

        $zip = new Filesystem(new ZipArchiveAdapter(public_path($users->name . '_document.zip')));

        foreach ($file_names as $file_name) {
            $file_content = Storage::disk('s3')->get($file_name);
            $zip->put($file_name, $file_content);
        }

        if ($zip->getAdapter()->getArchive()->close()) {
            return response()->download(public_path($users->name . '_document.zip'))->deleteFileAfterSend(true);
        } else {
            \Session::put('warning', 'Please try again..');
            return redirect()->back();
        }
    }

    public function applicationDeclined(Request $request)
    {
        if($this->ApplicationAssignToBank->applicationDeclined($request->get('applications_id'), $request->get('bank_users_id')))
        {
            \DB::beginTransaction();
            try {
                $bank = Bank::where('id',$request->get('bank_users_id'))->first();
                $application = Application::where('id',$request->get('applications_id'))->first();
                $admin = Admin::where('id','1')->first();
                Mail::to($admin->email)->send(new ApplicationDeclinedToBankMail($bank, $application));
            } catch(\Exception $e) {
                \DB::rollBack();
            }
            \DB::commit();
            
            return response()->json(['success' => '1', 'datas' => $request->all()]);
        } else {
            return response()->json(['success' => '0', 'datas' => $request->all()]);
        }
    }

    public function applicationApproved(Request $request)
    {
        if($this->ApplicationAssignToBank->applicationApproved($request->get('applications_id'), $request->get('bank_users_id')))
        {
            \DB::beginTransaction();
            try {
                $bank = Bank::where('id',$request->get('bank_users_id'))->first();
                $application = Application::where('id',$request->get('applications_id'))->first();
                $admin = Admin::where('id','1')->first();
                Mail::to($admin->email)->send(new ApplicationApprovedToBankMail($bank, $application));
            } catch(\Exception $e) {
                \DB::rollBack();
            }
            \DB::commit();

            return response()->json(['success' => '1', 'datas' => $request->all()]);
        } else {
            return response()->json(['success' => '0', 'datas' => $request->all()]);
        }
    }

    public function exportAllApplications(Request $request)
    {
        return Excel::download(new AllApplicationsForBankExport($request->ids), '.xlsx');
    }

    public function exportApprovedApplications(Request $request)
    {
        return Excel::download(new ApprovedApplicationsForBankExport($request->ids), '.xlsx');
    }

    public function exportDeclinedApplications(Request $request)
    {
        return Excel::download(new DeclinedApplicationsForBankExport($request->ids), '.xlsx');
    }

    public function exportPendingApplications(Request $request)
    {
        return Excel::download(new PendingApplicationsForBankExport($request->ids), '.xlsx');
    }
}
