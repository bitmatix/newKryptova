<?php

namespace App\Http\Controllers;

use App\Events\AdminNotification;
use DB;
use URL;
use Auth;
use File;
use View;
use Mail;
use Input;
use Session;
use Redirect;
use Exception;
use Validator;
use App\User;
use App\Admin;
use App\Application;
use App\TechnologyPartner;
use App\Categories;
use App\ImageUpload;
use App\Mail\NewApplicationSubmitUser;
use App\Notifications\ApplicationResubmit;
use App\Notifications\NewApplicationSubmit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->moduleTitleS = 'Profile';
        $this->moduleTitleP = 'front.application';

        $this->application = new Application;

        view()->share('moduleTitleP', $this->moduleTitleP);
        view()->share('moduleTitleS', $this->moduleTitleS);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $category = Categories::orderBy("categories.id", "ASC")->pluck('name', 'id')->toArray();
        $technologypartners = TechnologyPartner::latest()->pluck('name', 'id')->toArray();
        return view($this->moduleTitleP . '.start', compact('category', 'technologypartners'));
    }

    public function startApplicationStore(Request $request)
    {
        $input = \Arr::except($request->all(), array('_token', '_method'));

        $oldApp = Application::where('user_id', auth()->user()->id)->first();
        if ($oldApp != null) {
            \Session::put('error', 'Your application is already submitted.');
            return redirect()->back()->withInput($request->all());
        }

        $this->validate(
            $request,
            [
                'business_type' => 'required',
                'accept_card' => 'required',
                'business_name' => 'required',
                'phone_no' => 'required|max:14',
                'skype_id' => 'required',
                'website_url' => 'required',
                'business_contact_first_name' => 'required',
                'business_contact_last_name' => 'required',
                'business_address1' => 'required',
                'residential_address' => 'required',
                'monthly_volume' => 'required',
                'country' => 'required',
                'country_code' => 'required',
                'processing_currency' => 'required',
                'technology_partner_id' => 'required',
                'processing_country' => 'required',
                'category_id' => 'required',
                'company_license' => 'required',
                'passport.*' => 'required|mimes:jpg,jpeg,png,pdf,txt,doc,docx,xls,xlsx,zip|max:35840',
                'company_incorporation_certificate' => 'required|mimes:jpg,jpeg,png,pdf,txt,doc,docx,xls,xlsx,zip|max:35840',
                'domain_ownership' => 'required|mimes:jpg,jpeg,png,pdf,txt,doc,docx,xls,xlsx,zip|max:35840',
                'latest_bank_account_statement.*' => 'required|mimes:jpg,jpeg,png,pdf,txt,doc,docx,xls,xlsx,zip|max:35840',
                'utility_bill.*' => 'required|mimes:jpg,jpeg,png,pdf,txt,doc,docx,xls,xlsx,zip|max:35840',
                'previous_processing_statement.*' => 'mimes:jpg,jpeg,png,pdf,txt,doc,docx,xls,xlsx,zip|max:35840',
                'extra_document.*' => 'mimes:jpg,jpeg,png,pdf,txt,doc,docx,xls,xlsx,zip|max:35840',
                'owner_personal_bank_statement' => 'mimes:jpg,jpeg,png,pdf,txt,doc,docx,xls,xlsx,zip|max:35840',
                'licence_document' => 'mimes:jpg,jpeg,png,pdf,txt,doc,docx,xls,xlsx,zip|max:35840',
                'moa_document' => 'mimes:jpg,jpeg,png,pdf,txt,doc,docx,xls,xlsx,zip|max:35840',
                'board_of_directors' => 'required'
            ],
            [
                'passport.*.max' => 'The passport size may not be greater than 35 MB.',
                'company_incorporation_certificate.max' => 'The company incorporation certificate size may not be greater than 35 MB.',
                'domain_ownership.max' => 'The domain ownership size may not be greater than 35 MB.',
                'latest_bank_account_statement.*.max' => 'The latest bank account statement size may not be greater than 35 MB.',
                'utility_bill.*.max' => 'The utility bill size may not be greater than 35 MB.',
                'previous_processing_statement.max' => 'The previous processing statement size may not be greater than 35 MB.',
                'extra_document.*.max' => 'The additional document size may not be greater than 35 MB.',
                'licence_document.max' => 'The Licence document size may not be greater than 35 MB.',
                'moa_document.max' => 'The MOA document size may not be greater than 35 MB.',
                'owner_personal_bank_statement.max' => 'The owner personal bank statement size may not be greater than 35 MB.',
            ],

        );
        $input['user_id'] = auth()->user()->id;
        $user = auth()->user();
        $input['processing_country'] = json_encode($input['processing_country']);
        $input['processing_currency'] = json_encode($input['processing_currency']);
        $input['technology_partner_id'] = json_encode($input['technology_partner_id']);
        $input['accept_card'] = json_encode($input['accept_card']);

        if ($request->hasFile('passport')) {
            $files = $request->file('passport');
            $passportArr = [];
            foreach ($files as $key => $value) {
                $imageDocument = time() . rand(0, 10000000000000) . pathinfo(rand(111111111111, 999999999999), PATHINFO_FILENAME);
                $imageDocument = $imageDocument . '.' . $value->getClientOriginalExtension();
                $filePath = 'uploads/application-' . $user->id . '/' . $imageDocument;
                Storage::disk('s3')->put($filePath, file_get_contents($value->getRealPath()));
                array_push($passportArr, $filePath);
            }
            $input['passport'] = json_encode($passportArr);
        }
        if ($request->hasFile('utility_bill')) {
            $files = $request->file('utility_bill');
            $utilityArr = [];
            foreach ($files as $key => $value) {
                $imageDocument = time() . rand(0, 10000000000000) . pathinfo(rand(111111111111, 999999999999), PATHINFO_FILENAME);
                $imageDocument = $imageDocument . '.' . $value->getClientOriginalExtension();
                $filePath = 'uploads/application-' . $user->id . '/' . $imageDocument;
                Storage::disk('s3')->put($filePath, file_get_contents($value->getRealPath()));
                array_push($utilityArr, $filePath);
            }
            $input['utility_bill'] = json_encode($utilityArr);
        }
        if ($request->hasFile('latest_bank_account_statement')) {
            $files = $request->file('latest_bank_account_statement');
            $bankStatementArr = [];
            foreach ($files as $key => $value) {
                $imageDocument = time() . rand(0, 10000000000000) . pathinfo(rand(111111111111, 999999999999), PATHINFO_FILENAME);
                $imageDocument = $imageDocument . '.' . $value->getClientOriginalExtension();
                $filePath = 'uploads/application-' . $user->id . '/' . $imageDocument;
                Storage::disk('s3')->put($filePath, file_get_contents($value->getRealPath()));
                array_push($bankStatementArr, $filePath);
            }
            $input['latest_bank_account_statement'] = json_encode($bankStatementArr);
        }

        if ($request->hasFile('company_incorporation_certificate')) {
            $imageNameCertificate = time() . rand(0, 10000000000000) . pathinfo(rand(111111111111, 999999999999), PATHINFO_FILENAME);
            $imageNameCertificate = $imageNameCertificate . '.' . $request->file('company_incorporation_certificate')->getClientOriginalExtension();
            $filePath = 'uploads/application-' . $user->id . '/' . $imageNameCertificate;
            Storage::disk('s3')->put($filePath, file_get_contents($request->file('company_incorporation_certificate')->getRealPath()));
            $input['company_incorporation_certificate'] = $filePath;
        }

        if ($request->hasFile('domain_ownership')) {
            $imageNamedomainownership = time() . rand(0, 10000000000000) . pathinfo(rand(111111111111, 999999999999), PATHINFO_FILENAME);
            $imageNamedomainownership = $imageNamedomainownership . '.' . $request->file('domain_ownership')->getClientOriginalExtension();
            $filePath = 'uploads/application-' . $user->id . '/' . $imageNamedomainownership;
            Storage::disk('s3')->put($filePath, file_get_contents($request->file('domain_ownership')->getRealPath()));
            $input['domain_ownership'] = $filePath;
        }

        if ($request->hasFile('licence_document')) {
            $imageNameCertificate = time() . rand(0, 10000000000000) . pathinfo(rand(111111111111, 999999999999), PATHINFO_FILENAME);
            $imageNameCertificate = $imageNameCertificate . '.' . $request->file('licence_document')->getClientOriginalExtension();
            $filePath = 'uploads/application-' . $user->id . '/' . $imageNameCertificate;
            Storage::disk('s3')->put($filePath, file_get_contents($request->file('licence_document')->getRealPath()));
            $input['licence_document'] = $filePath;
        }

        if ($request->hasFile('moa_document')) {
            $imageNameCertificate = time() . rand(0, 10000000000000) . pathinfo(rand(111111111111, 999999999999), PATHINFO_FILENAME);
            $imageNameCertificate = $imageNameCertificate . '.' . $request->file('moa_document')->getClientOriginalExtension();
            $filePath = 'uploads/application-' . $user->id . '/' . $imageNameCertificate;
            Storage::disk('s3')->put($filePath, file_get_contents($request->file('moa_document')->getRealPath()));
            $input['moa_document'] = $filePath;
        }

        if ($request->hasFile('previous_processing_statement')) {
            $files = $request->file('previous_processing_statement');
            foreach ($request->file('previous_processing_statement') as $key => $value) {
                $imageStatement = time() . rand(0, 10000000000000) . pathinfo(rand(111111111111, 999999999999), PATHINFO_FILENAME);
                $imageStatement = $imageStatement . '.' . $files[$key]->getClientOriginalExtension();
                $filePath = 'uploads/application-' . $user->id . '/' . $imageStatement;
                Storage::disk('s3')->put($filePath, file_get_contents($value->getRealPath()));
                $input['previous_processing_statements'][] = $filePath;
            }
            $input['previous_processing_statement'] = json_encode($input['previous_processing_statements']);
            unset($input['previous_processing_statements']);
        }

        if ($request->hasFile('extra_document')) {
            $files = $request->file('extra_document');
            foreach ($request->file('extra_document') as $key => $value) {
                $imageDocument = time() . rand(0, 10000000000000) . pathinfo(rand(111111111111, 999999999999), PATHINFO_FILENAME);
                $imageDocument = $imageDocument . '.' . $files[$key]->getClientOriginalExtension();
                $filePath = 'uploads/application-' . $user->id . '/' . $imageDocument;
                Storage::disk('s3')->put($filePath, file_get_contents($value->getRealPath()));
                $input['extra_documents'][] = $filePath;
            }

            $input['extra_document'] = json_encode($input['extra_documents']);
            unset($input['extra_documents']);
        }

        if ($request->hasFile('owner_personal_bank_statement')) {

            $imageOwnerBankStatement = time() . rand(0, 10000000000000) . pathinfo(rand(111111111111, 999999999999), PATHINFO_FILENAME);
            $imageOwnerBankStatement = $imageOwnerBankStatement . '.' . $request->file('owner_personal_bank_statement')->getClientOriginalExtension();
            $filePath = 'uploads/application-' . $user->id . '/' . $imageOwnerBankStatement;
            Storage::disk('s3')->put($filePath, file_get_contents($request->file('owner_personal_bank_statement')->getRealPath()));
            $input['owner_personal_bank_statement'] = $filePath;
        }

        DB::beginTransaction();
        try {
            $input['status'] = '1';
            $application = $this->application->storeData($input);

            $notification = [
                'user_id' => '1',
                'sendor_id' => auth()->user()->id,
                'type' => 'admin',
                'title' => 'Application Created',
                'body' => 'You have received a new application.',
                'url' => '/admin/applications-list/view/' . $application->id,
                'is_read' => '0'
            ];

            $realNotification = addNotification($notification);
            $realNotification->created_at_date = convertDateToLocal($realNotification->created_at, 'd/m/Y H:i:s');
            event(new AdminNotification($realNotification->toArray()));

            Admin::find('1')->notify(new NewApplicationSubmit($application));
            Mail::to($user->email)->send(new NewApplicationSubmitUser($user));

            DB::commit();
            \Session::put("successcustom", "Thank you for submitting your application.");
            return redirect('my-application')->with('success', 'Thank you for submitting your application. Your application is under review.');
        } catch (Exception $e) {
            DB::rollBack();
            \Session::put('error', 'Your application not submit.Try Again.');
            return redirect()->back()->withInput($request->all());
        }
    }
    public function status(Request $request)
    {
        if (\Auth::user()->main_user_id != '0')
            $userID = \Auth::user()->main_user_id;
        else
            $userID = \Auth::user()->id;
        $data = $this->application->FindDataFromUser($userID);

        $category = Categories::orderBy("categories.id", "ASC")->pluck('name', 'id')->toArray();
        $technologypartners = TechnologyPartner::latest()->pluck('name', 'id')->toArray();

        return view($this->moduleTitleP . '.status', compact('data','category','technologypartners'));
    }

    public function applicationsEdit(Request $request, $id)
    {
        $data = $this->application->findData($id);
        if ($data->user_id != \Auth::user()->id) {
            return redirect()->back();
        }
        if ($data) {
            $category = Categories::orderBy("categories.id", "ASC")->pluck('name', 'id')->toArray();
            $technologypartners = TechnologyPartner::latest()->pluck('name', 'id')->toArray();
            return view($this->moduleTitleP . '.applicationsEdit', compact('category', 'data', 'id', 'request', 'technologypartners'));
        }
        return redirect()->back();
    }

    public function applicationsUpdate(Request $request, $id)
    {
        $input = \Arr::except($request->all(), array('_token', '_method'));
        $user = User::where('id', $input['user_id'])->first();
        $application = Application::where('id', $id)->first();
        $this->validate(
            $request,
            [
                'business_type' => 'required',
                'accept_card' => 'required',
                'business_name' => 'required',
                'website_url' => 'required',
                'phone_no' => 'required|max:14',
                'skype_id' => 'required',
                'business_contact_first_name' => 'required',
                'business_contact_last_name' => 'required',
                'business_address1' => 'required',
                'residential_address' => 'required',
                'monthly_volume' => 'required',
                'country' => 'required',
                'processing_currency' => 'required',
                'technology_partner_id' => 'required',
                'processing_country' => 'required',
                'category_id' => 'required',
                'company_license' => 'required',
                'passport.*' => 'mimes:jpg,jpeg,png,pdf,txt,doc,docx,xls,xlsx,zip|max:35840',
                'company_incorporation_certificate' => 'mimes:jpg,jpeg,png,pdf,txt,doc,docx,xls,xlsx,zip|max:35840',
                'domain_ownership' => 'mimes:jpg,jpeg,png,pdf,txt,doc,docx,xls,xlsx,zip|max:35840',
                'latest_bank_account_statement.*' => 'mimes:jpg,jpeg,png,pdf,txt,doc,docx,xls,xlsx,zip|max:35840',
                'utility_bill.*' => 'mimes:jpg,jpeg,png,pdf,txt,doc,docx,xls,xlsx,zip|max:35840',
                'previous_processing_statement.*' => 'mimes:jpg,jpeg,png,pdf,txt,doc,docx,xls,xlsx,zip|max:35840',
                'extra_document.*' => 'mimes:jpg,jpeg,png,pdf,txt,doc,docx,xls,xlsx,zip|max:35840',
                'owner_personal_bank_statement' => 'mimes:jpg,jpeg,png,pdf,txt,doc,docx,xls,xlsx,zip|max:35840',
                'licence_document' => 'mimes:jpg,jpeg,png,pdf,txt,doc,docx,xls,xlsx,zip|max:35840',
                'moa_document' => 'mimes:jpg,jpeg,png,pdf,txt,doc,docx,xls,xlsx,zip|max:35840',
            ],
            [
                'passport.*.max' => 'The passport size may not be greater than 35 MB.',
                'company_incorporation_certificate.max' => 'The company incorporation certificate size may not be greater than 35 MB.',
                'domain_ownership.max' => 'The domain ownership size may not be greater than 35 MB.',
                'latest_bank_account_statement.*.max' => 'The latest bank account statement size may not be greater than 35 MB.',
                'utility_bill.*.max' => 'The utility bill size may not be greater than 35 MB.',
                'previous_processing_statement.max' => 'The previous processing statement size may not be greater than 35 MB.',
                'extra_document.*.max' => 'The additional document size may not be greater than 35 MB.',
                'owner_personal_bank_statement.max' => 'The owner personal bank statement size may not be greater than 35 MB.',
                'licence_document.max' => 'The Licence document size may not be greater than 35 MB.',
                'moa_document.max' => 'The MOA document size may not be greater than 35 MB.',
            ],
        );

        $input['processing_country'] = json_encode($input['processing_country']);
        $input['processing_currency'] = json_encode($input['processing_currency']);
        $input['technology_partner_id'] = json_encode($input['technology_partner_id']);

        // $input['customer_location'] = json_encode($input['customer_location']);
        // $input['settlement_currency'] = json_encode($input['settlement_currency']);
        $filePath = storage_path() . "/uploads/" . $user->name . '-' . $user->id . '/';

        if ($request->hasFile('passport')) {
            $old_passport_documents = json_decode($application->passport);
            $files = $request->file('passport');
            $passportArr = [];
            foreach ($files as $key => $value) {
                $imageDocument = time() . rand(0, 10000000000000) . pathinfo(rand(111111111111, 999999999999), PATHINFO_FILENAME);
                $imageDocument = $imageDocument . '.' . $value->getClientOriginalExtension();
                $filePath = 'uploads/application-' . $user->id . '/' . $imageDocument;
                Storage::disk('s3')->put($filePath, file_get_contents($value->getRealPath()));
                array_push($passportArr, $filePath);
            }
            $updated_passport_documents = array_merge($old_passport_documents, $passportArr);
            $input['passport'] = json_encode($updated_passport_documents);
        }
        if ($request->hasFile('latest_bank_account_statement')) {
            $old_bank_statement = json_decode($application->latest_bank_account_statement);
            $files = $request->file('latest_bank_account_statement');
            $bankStatementArr = [];
            foreach ($files as $key => $value) {
                $imageDocument = time() . rand(0, 10000000000000) . pathinfo(rand(111111111111, 999999999999), PATHINFO_FILENAME);
                $imageDocument = $imageDocument . '.' . $value->getClientOriginalExtension();
                $filePath = 'uploads/application-' . $user->id . '/' . $imageDocument;
                Storage::disk('s3')->put($filePath, file_get_contents($value->getRealPath()));
                array_push($bankStatementArr, $filePath);
            }
            $updated_bankStatement = array_merge($old_bank_statement, $bankStatementArr);
            $input['latest_bank_account_statement'] = json_encode($updated_bankStatement);
        }

        if ($request->hasFile('utility_bill')) {
            $old_utilityBill = json_decode($application->utility_bill);
            $files = $request->file('utility_bill');
            $utilityBillArr = [];
            foreach ($files as $key => $value) {
                $imageDocument = time() . rand(0, 10000000000000) . pathinfo(rand(111111111111, 999999999999), PATHINFO_FILENAME);
                $imageDocument = $imageDocument . '.' . $value->getClientOriginalExtension();
                $filePath = 'uploads/application-' . $user->id . '/' . $imageDocument;
                Storage::disk('s3')->put($filePath, file_get_contents($value->getRealPath()));
                array_push($utilityBillArr, $filePath);
            }
            $utilityBill = array_merge($old_utilityBill, $utilityBillArr);
            $input['utility_bill'] = json_encode($utilityBill);
        }

        if ($request->hasFile('licence_document')) {
            $imageNameCertificate = time() . rand(0, 10000000000000) . pathinfo(rand(111111111111, 999999999999), PATHINFO_FILENAME);
            $imageNameCertificate = $imageNameCertificate . '.' . $request->file('licence_document')->getClientOriginalExtension();
            $filePath = 'uploads/application-' . $user->id . '/' . $imageNameCertificate;
            Storage::disk('s3')->put($filePath, file_get_contents($request->file('licence_document')->getRealPath()));
            $input['licence_document'] = $filePath;
        } else if ($request->company_license == '1' || $request->company_license == '2') {
            Storage::disk('s3')->delete($application->licence_document);
            $input['licence_document'] = null;
        }


        if ($request->hasFile('moa_document')) {
            $imageNameCertificate = time() . rand(0, 10000000000000) . pathinfo(rand(111111111111, 999999999999), PATHINFO_FILENAME);
            $imageNameCertificate = $imageNameCertificate . '.' . $request->file('moa_document')->getClientOriginalExtension();
            $filePath = 'uploads/application-' . $user->id . '/' . $imageNameCertificate;
            Storage::disk('s3')->put($filePath, file_get_contents($request->file('moa_document')->getRealPath()));
            $input['moa_document'] = $filePath;
        }

        if ($request->hasFile('company_incorporation_certificate')) {
            Storage::disk('s3')->delete($application->company_incorporation_certificate);
            $imageNameCertificate = time() . rand(0, 10000000000000) . pathinfo(rand(111111111111, 999999999999), PATHINFO_FILENAME);
            $imageNameCertificate = $imageNameCertificate . '.' . $request->file('company_incorporation_certificate')->getClientOriginalExtension();
            $filePath = 'uploads/application-' . $user->id . '/' . $imageNameCertificate;
            Storage::disk('s3')->put($filePath, file_get_contents($request->file('company_incorporation_certificate')->getRealPath()));
            $input['company_incorporation_certificate'] = $filePath;
        }

        if ($request->hasFile('domain_ownership')) {
            Storage::disk('s3')->delete($application->domain_ownership);
            $imageNamedomainownership = time() . rand(0, 10000000000000) . pathinfo(rand(111111111111, 999999999999), PATHINFO_FILENAME);
            $imageNamedomainownership = $imageNamedomainownership . '.' . $request->file('domain_ownership')->getClientOriginalExtension();
            $filePath = 'uploads/application-' . $user->id . '/' . $imageNamedomainownership;
            Storage::disk('s3')->put($filePath, file_get_contents($request->file('domain_ownership')->getRealPath()));
            $input['domain_ownership'] = $filePath;
        }

        if ($request->hasFile('previous_processing_statement')) {
            // delete old records.
            if ($application->previous_processing_statement != null) {
                foreach (json_decode($application->previous_processing_statement) as $key => $value) {
                    Storage::disk('s3')->delete($value);
                }
            }
            $files = $request->file('previous_processing_statement');
            foreach ($request->file('previous_processing_statement') as $key => $value) {
                $imageStatement = time() . rand(0, 10000000000000) . pathinfo(rand(111111111111, 999999999999), PATHINFO_FILENAME);
                $imageStatement = $imageStatement . '.' . $files[$key]->getClientOriginalExtension();
                $filePath = 'uploads/application-' . $user->id . '/' . $imageStatement;
                Storage::disk('s3')->put($filePath, file_get_contents($value->getRealPath()));
                $input['previous_processing_statements'][] = $filePath;
            }

            $input['previous_processing_statement'] = json_encode($input['previous_processing_statements']);
            unset($input['previous_processing_statements']);
        }


        $old_extra_documents = json_decode(Application::find($id)->extra_document);
        if ($old_extra_documents) {
            if ($request->hasFile('extra_document')) {
                $files = $request->file('extra_document');
                foreach ($request->file('extra_document') as $key => $value) {
                    $imageDocument = time() . rand(0, 10000000000000) . pathinfo(rand(111111111111, 999999999999), PATHINFO_FILENAME);
                    $imageDocument = $imageDocument . '.' . $files[$key]->getClientOriginalExtension();
                    $filePath = 'uploads/application-' . $user->id . '/' . $imageDocument;
                    Storage::disk('s3')->put($filePath, file_get_contents($value->getRealPath()));
                    $input['extra_documents'][] = $filePath;
                }
                $input['extra_document'] = json_encode($input['extra_documents']);
                $new_extra_documents = json_decode($input['extra_document']);
                $updated_extra_documents = array_merge($old_extra_documents, $new_extra_documents);
                $input['extra_document'] = json_encode($updated_extra_documents);
                unset($input['extra_documents']);
            }
        } else {
            if ($request->hasFile('extra_document')) {
                $files = $request->file('extra_document');
                foreach ($request->file('extra_document') as $key => $value) {
                    $imageDocument = time() . rand(0, 10000000000000) . pathinfo(rand(111111111111, 999999999999), PATHINFO_FILENAME);
                    $imageDocument = $imageDocument . '.' . $files[$key]->getClientOriginalExtension();
                    $filePath = 'uploads/application-' . $user->id . '/' . $imageDocument;
                    Storage::disk('s3')->put($filePath, file_get_contents($value->getRealPath()));
                    $input['extra_documents'][] = $filePath;
                }
                $input['extra_document'] = json_encode($input['extra_documents']);
                unset($input['extra_documents']);
            }
        }

        if ($request->hasFile('owner_personal_bank_statement')) {
            File::delete(storage_path() . "/uploads/" . $user->name . '-' . $user->id . '/' . $application->owner_personal_bank_statement);
            $imageOwnerBankStatement = time() . rand(0, 10000000000000) . pathinfo(rand(111111111111, 999999999999), PATHINFO_FILENAME);
            $imageOwnerBankStatement = $imageOwnerBankStatement . '.' . $request->file('owner_personal_bank_statement')->getClientOriginalExtension();
            $filePath = 'uploads/application-' . $user->id . '/' . $imageOwnerBankStatement;
            Storage::disk('s3')->put($filePath, file_get_contents($request->file('owner_personal_bank_statement')->getRealPath()));
            $input['owner_personal_bank_statement'] = $filePath;
        }

        $this->application->updateApplication($id, $input);

        if ($application->status == '2') {
            $notification = [
                'user_id' => '1',
                'sendor_id' => auth()->user()->id,
                'type' => 'admin',
                'title' => 'Application Resubmitted',
                'body' => $application->business_name . ' application have been resubmitted.',
                'url' => '/admin/applications-list/view/' . $application->id,
                'is_read' => '0'
            ];
            $realNotification = addNotification($notification);

            $realNotification->created_at_date = convertDateToLocal($realNotification->created_at, 'd/m/Y H:i:s');
            event(new AdminNotification($realNotification->toArray()));

            $notification = [
                'user_id' => auth()->user()->id,
                'sendor_id' => '1',
                'type' => 'user',
                'title' => 'Application Resubmitted',
                'body' => 'Your application Resubmitted successfully.',
                'url' => '/my-application',
                'is_read' => '0'
            ];

            $realNotification = addNotification($notification);

            $this->application->updateApplication($id, ['status' => '1']);
        }

        DB::beginTransaction();
        try {
            if ($application->status == '2') {
                Admin::find('1')->notify(new ApplicationResubmit($application));
                $this->application->updateApplication($id, ['reason_reassign' => '']);

                notificationMsg('success', 'Your application has been resubmitted successfully.');
                return redirect()->route('my-application');
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }
        notificationMsg('success', 'Your application has been updated successfully.');

        return redirect()->route('my-application');
    }

    public function downloadDocumentsUploade(Request $request)
    {
        addToLog('application document download', [$request->file], 'general');
        return Storage::disk('s3')->download($request->file);
    }

    public function viewAppImage(Request $request)
    {
        //{{ Config('app.aws_path').'uploads/application-'.$user->id.'/'.$value }}
        // $user = auth()->user();
        // $path = storage_path('uploads/application-'.$user->id.'/'.$request->file);
        // if (!File::exists($path)) {
        //     abort(404);
        // }
        // $file = File::get($path);
        // $type = File::mimeType($path);
        // $response = \Response::make($file, 200);
        // $response->header("Content-Type", $type);
        // return $response;

    }
}
