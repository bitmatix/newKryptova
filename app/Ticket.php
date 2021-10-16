<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use Cachable;
    use SoftDeletes;

    protected $fillable = [
        'title', 'body', 'files', 'user_id', 'status', 'department'
    ];
    protected $table = 'tickets';

    // status = 0 panding, 1 done, 2 in review, 3 close, 4 other

    public function storeData($input)
    {
        return static::create($input);
    }

    public function destroyWithUserId($id)
    {
        return static::where('user_id', $id)->delete();
    }

    public function getData()
    {
        if (\Auth::user()->main_user_id != '0')
            $userID = \Auth::user()->main_user_id;
        else
            $userID = \Auth::user()->id;
        return static::orderBy('created_at', 'desc')->where('user_id', $userID)->get();
    }

    public function getTicketsDash()
    {
        if (\Auth::user()->main_user_id != 0 && \Auth::user()->is_sub_user == '1')
            $userID = \Auth::user()->main_user_id;
        else
            $userID = \Auth::user()->id;

        return static::where('user_id', $userID)
            ->latest()
            ->take(5)
            ->get();
    }

    public function getTicketsAdminDash()
    {
        return static::latest()
            ->take(5)
            ->get();
    }

    public function getAdminTickets()
    {
        return static::orderBy('created_at', 'desc')->get();
    }

    public function findData($id)
    {
        return static::find($id);
    }

    public function replies()
    {
        return $this->hasMany('App\TicketReply', 'ticket_id');
    }

    public function user()
    {
        return $this->hasOne('App\User', 'id', 'user_id');
    }

    public function updateStatus($id, $status)
    {
        return static::where('id', $id)->update(['status' => $status]);
    }
}

// user create

// list, status , action - delete, show

// admin,

// listing, action - assign to opretor, bank, -re-assign, close replay
