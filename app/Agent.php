<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Agent extends Authenticatable
{
    use SoftDeletes;

    protected $table = 'agents';
    protected $guarded = array();

    protected $fillable = [
        'name',
        'email',
        'commission',
        'otp',
        'is_otp_required',
        'is_active',
        'login_otp',
        'agreement_status',
        'token',
        'password',
        'theme_css_file_name',
        'theme_type',
        'layout_type',
        'sidebar_type'
    ];

    protected $hidden = [
        'password',
        'token',
        'remember_token'
    ];

    public function getData($input, $noList)
    {
        $data = static::select("agents.*", "rp_agreement_document_upload.sent_files as sent_files", "rp_agreement_document_upload.files as files", "rp_agreement_document_upload.created_at as rpdoc_created_at")
            ->leftJoin('rp_agreement_document_upload', function($query) {
                $query->on('rp_agreement_document_upload.rp_id','=','agents.id')
                    ->whereRaw('rp_agreement_document_upload.id IN (select MAX(radu.id) from rp_agreement_document_upload as radu join agents as agts on agts.id = radu.rp_id group by agts.id)');
            });

            //->leftJoin('rp_agreement_document_upload', 'rp_agreement_document_upload.rp_id', '=', 'agents.id');

        if (isset($input['name']) && $input['name'] != '') {
            $data = $data->where('agents.name', 'like', '%' . $input['name'] . '%');
        }
        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('agents.email', 'like', '%' . $input['email'] . '%');
        }
        if (isset($input['agreement_status']) && $input['agreement_status'] != '') {
            $data = $data->where('agents.agreement_status', 'like', '%' . $input['agreement_status'] . '%');
        }
        if (isset($input['name']) && $input['name'] != '' && isset($input['email']) && $input['email'] != '' && isset($input['agreement_status']) && $input['agreement_status'] != '') {
            $data = $data->where('agents.name', 'like', '%' . $input['name'] . '%')->orWhere('agents.email', 'like', '%' . $input['email'] . '%')->orWhere('agents.agreement_status', 'like', '%' . $input['agreement_status'] . '%');
        }
        $authUser = auth()->guard('admin')->user();
        if ($authUser != '') {
            addToAdminLog($this->table, 'Referral partner get', $authUser, 'success');
        }

        $data = $data->orderBy("agents.id", "DESC")
            ->paginate($noList);

        return $data;
    }

    public function getAgentData()
    {
        $data = static::select("agents.*")
            ->orderBy("agents.id","DESC")
            ->get();
        return $data;
    }

    public function findData($id)
    {
        return static::find($id);
    }

    public function storeData($input)
    {
        $authUser = auth()->guard('admin')->user();
        return static::create($input);
    }

    public function destroyData($id)
    {
        RpAgreementDocumentUpload::where('rp_id',$id)->delete();
        return static::find($id)->delete();
    }

    public function updateData($id, $input)
    {
        return static::find($id)->update($input);
    }

    /**
     * @param string[] $fields
     * @return mixed
     */
    public function agentsList(array $fields = ['id', 'name', 'email'])
    {
        return static::get($fields);
    }
}
