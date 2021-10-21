<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class BankUsers extends Authenticatable
{
    use SoftDeletes;

    protected $table = 'bank_users';

    protected $guarded = array();

    protected $fillable = [
        'bank_name', 'email','password','token_password', 'extra_email', 'referral_code', 'country', 'categories_id','is_active'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [

    ];

    public function getData($input, $noList)
    {
        $data = static::select("bank_users.*");

        if (isset($input['name']) && $input['name'] != '') {
            $data = $data->where('bank_users.bank_name', 'like', '%' . $input['name'] . '%');
        }
        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('bank_users.email', 'like', '%' . $input['email'] . '%');
        }
        if (isset($input['category']) && $input['category'] != '') {
            $data = $data->where('bank_users.categories_id', 'like', '%' . $input['category'] . '%');
        }
        if (isset($input['name']) && $input['name'] != '' && isset($input['email']) && $input['email'] != '' && isset($input['category']) && $input['category'] != '') {
            $data = $data->where('bank_users.bank_name', 'like', '%' . $input['name'] . '%')->orWhere('bank_users.email', 'like', '%' . $input['email'] . '%')->orWhere('bank_users.categories_id', 'like', '%' . $input['category'] . '%');
        }
        $authUser = auth()->guard('bank_user')->user();
        // if ($authUser != '') {
        //     addToAdminLog($this->table, 'Bank users get', $authUser, 'success');
        // }

        $data = $data->orderBy("bank_users.id", "DESC")
            ->paginate($noList);

        return $data;
    }

    public function getBankUserData()
    {
        $data = static::select('bank_users.*')
            ->get();
        return $data;
    }


    public function findData($id)
    {
        $authUser = auth()->guard('bank_user')->user();
        if($authUser != ''){
            addToAdminLog($this->table,'Bank user details show',$authUser,'success');
        }
        return static::find($id);
    }

    public function getBankUserById($id)
    {
        $data = static::select('bank_users.*')
            ->where('bank_users.id', $id)
            ->first();
        return $data;
    }

    public function storeData($input)
    {
        $authUser = auth()->guard('bank_user')->user();
        // if ($authUser != '') {
        //     addToAdminLog($this->table, 'Bank users added', $authUser, 'success');
        // }

        return static::create($input);
    }

    public function destroyData($id)
    {
        $authUser = auth()->guard('bank_user')->user();
        // if ($authUser != '') {
        //     addToAdminLog($this->table, 'Bank users deleted', $authUser, 'success');
        // }
        return static::find($id)->delete();
    }

    public function updateData($id, $input)
    {
        $authUser = auth()->guard('bank_user')->user();
        // if ($authUser != '') {
        //     addToAdminLog($this->table, 'Bank users updated', $authUser, 'success');
        // }
        return static::find($id)->update($input);
    }
}
