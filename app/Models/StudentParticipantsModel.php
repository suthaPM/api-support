<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelHelper;

class StudentParticipantsModel extends Model
{
    use HasFactory, ModelHelper;
    protected $table = "career_support_models_studentparticipants";

    protected $hidden = [
        "is_deleted",
        "created",
        "modified",
        "creator_id",
        "modifier_id",
    ];

    protected $fillable = [
        'webinar_id',
        'school_id',
        'student_id'

    ];
}
