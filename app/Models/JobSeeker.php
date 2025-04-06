<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobSeeker extends Model
{
    use HasFactory;
    public $timestamps = false;


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'job_seeker';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'profile_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];


    public function workExperienceInfo()
    {
        return $this->hasMany(JobSeekerExperience::class, 'profile_pid', 'profile_pid');
    }
    public function skillInfo()
    {
        return $this->hasMany(JobSeekerSkill::class, 'profile_pid', 'profile_pid');
    }
    public function achievementInfo()
    {
        return $this->hasMany(JobSeekerAchievement::class, 'profile_pid', 'profile_pid');
    }
    public function educationInfo()
    {
        return $this->hasMany(SeekerEduInfo::class, 'profile_pid', 'profile_pid');
    }
}
