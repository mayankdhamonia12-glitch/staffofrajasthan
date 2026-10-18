<?php

namespace App;

enum PackageType: string
{
    case JobPosting = 'job_posting';
    case CandidateAccess = 'candidate_access';
}
