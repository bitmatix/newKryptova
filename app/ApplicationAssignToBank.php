<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationAssignToBank extends Model
{
    use SoftDeletes;

    protected $table = 'application_assign_to_banks';
    protected $guarded = array();

    protected $fillable = [
        'application_id',
        'bank_user_id',
        'status'
    ];

    public function storeData($input)
    {
    	return static::create($input);
    }

    public function applicationDeclined($applications_id, $bank_user_id)
    {
        return static::where(['application_id' => $applications_id, 'bank_user_id' => $bank_user_id])->update(['status' => '2']);
    }

    public function applicationApproved($applications_id, $bank_user_id)
    {
    	return static::where(['application_id' => $applications_id, 'bank_user_id' => $bank_user_id])->update(['status' => '1']);
    }
}
