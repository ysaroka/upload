<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServerConfiguration extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'server_configurations';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;


    public function server()
    {
        return $this->belongsTo(Server::class, 'server_id', 'id');
    }

}
