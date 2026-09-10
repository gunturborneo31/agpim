<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Agenda;
use App\Models\User;

class AgendaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Agenda $agenda): bool
    {
        return $user->hasRole(UserRole::SuperAdmin, UserRole::AdminProkopim)
            || $agenda->submitted_by === $user->id
            || ($user->opd_id && $agenda->opd_id === $user->opd_id)
            || ($agenda->leader_target && $user->hasRole($agenda->leader_target))
            || ($user->hasRole(UserRole::Verifikator) && $agenda->submitter?->hasRole(UserRole::Opd));
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Opd, UserRole::Verifikator);
    }

    public function update(User $user, Agenda $agenda): bool
    {
        if ($agenda->status !== 'submitted') {
            return false;
        }

        return $agenda->submitted_by === $user->id;
    }
}
