<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'servers';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;


    public function configs()
    {
        return $this->hasMany(ServerConfiguration::class, 'server_id', 'id');
    }

}
