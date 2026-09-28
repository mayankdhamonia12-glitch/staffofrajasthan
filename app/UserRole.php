<?php

namespace App;

enum UserRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Candidate = 'candidate';
    case Employer = 'employer';

    /**
     * Roles that may be chosen through the public registration flow.
     *
     * Administrative roles are deliberately absent: they can only be assigned
     * by an owner-managed workflow added later.
     *
     * @return array<int, self>
     */
    public static function publicRegistrationRoles(): array
    {
        return [self::Candidate, self::Employer];
    }
}
