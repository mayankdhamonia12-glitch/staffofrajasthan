<?php

return [
    'private_disk' => env('CANDIDATE_PRIVATE_DISK', 'local'),
    'private_directory' => trim((string) env('CANDIDATE_PRIVATE_DIRECTORY', 'candidate-private'), '/'),
];
