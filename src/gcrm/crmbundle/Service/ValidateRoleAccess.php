<?php

namespace GCRM\CRMBundle\Service;

use GCRM\CRMBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ValidateRoleAccess
{
    public function validateAccess($requiredRole, $user)
    {
        if (!$user) {
            throw new NotFoundHttpException();
        }

        $haveAccess = false;
        /** @var User $user */
        foreach ($user->getRoles() as $role) {
            if ($role == $requiredRole) {
                $haveAccess = true;
                break;
            }
        }

        if (!$haveAccess) {
            throw new AccessRestrictedException();
        }
    }

    public function checkIfHaveAccess($requiredRoles, $user)
    {
        if (!$user) {
            throw new NotFoundHttpException();
        }

        $rolesMatched = [];

        /** @var User $user */
        foreach ($user->getRoles() as $userRole) {
            foreach ($requiredRoles as $requiredRole) {
                if ($userRole == $requiredRole) {
                    $rolesMatched[] = $userRole;
                }
            }
        }

        if (count($rolesMatched) == count($requiredRoles)) {
            return true;
        }
        return false;

    }
}