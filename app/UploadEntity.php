<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UploadEntity extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'uploads';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * @inheritdoc
     */
    protected $fillable = ['server', 'original_name', 'upload_name', 'status', 'status_message'];

}
