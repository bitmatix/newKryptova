<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
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
        'main_agent_id',
        'password'
    ];

    protected $hidden = [
        'password',
        'token',
        'remember_token'
    ];

    public function getData($input, $noList)
    {
        $data = static::select("agents.*", "rp_agreement_document_upload.sent_files as sent_files", "rp_agreement_document_upload.files as files")
            ->leftJoin('rp_agreement_document_upload', 'rp_agreement_document_upload.rp_id', 'agents.id')
            ->groupBy("agents.id")
            ->orderBy("agents.id", "DESC")
            ->where('agents.main_agent_id','0');

        if (isset($input['name']) && $input['name'] != '') {
            $data = $data->where('agents.name',  'like', '%' . $input['name'] . '%');
        }
        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('agents.email',  'like', '%' . $input['email'] . '%');
        }
        if (isset($input['agreement_status']) && $input['agreement_status'] != '') {
            $data = $data->where('agents.agreement_status',   $input['agreement_status']);
        }

        $data = $data->paginate($noList);

        return $data;
    }

    public function getAllSubAgent($input, $noList)
    {
        $data = static::select("agents.*")->where('main_agent_id','!=','0')
            ->orderBy("id", "DESC");

        if (isset($input['name']) && $input['name'] != '') {
            $data = $data->where('agents.name',  'like', '%' . $input['name'] . '%');
        }
        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('agents.email',  'like', '%' . $input['email'] . '%');
        }

        $data = $data->paginate($noList);

        return $data;
    }

    public function findData($id)
    {
        return static::find($id);
    }

    public function storeData($input)
    {
        return static::create($input);
    }

    public function destroyData($id)
    {
        return static::find($id)->delete();
    }

    public function updateData($id, $input)
    {
        return static::find($id)->update($input);
    }
}
