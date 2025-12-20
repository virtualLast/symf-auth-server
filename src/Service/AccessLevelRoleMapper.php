<?php

declare(strict_types=1);

namespace App\Service;

class AccessLevelRoleMapper
{
    /**
     * Maps HierCode strings to Symfony roles
     *
     * @param array<string> $hierCodes
     * @return array<string>
     */
    public function mapToRoles(array $hierCodes): array
    {
        $roles = ['ROLE_USER']; // Base role for all users

        foreach ($hierCodes as $hierCode) {
            // HierCode format: "GG-XX-TescoGlobal-HierCode UK01001"
            // Extract the code part (e.g., "UK01001", "UKGP001")

            // Extract code from HierCode (everything after "HierCode ")
            if (preg_match('/HierCode\s+([A-Z0-9]+)/', $hierCode, $matches)) {
                $code = $matches[1];

                // Map store codes (UK01001, UK01101, etc.)
                if (preg_match('/^UK(\d+)$/', $code, $storeMatches)) {
                    $storeNumber = $storeMatches[1];
                    $roles[] = sprintf('ROLE_STORE_%s', $storeNumber);
                }

                // Map group codes (UKGP001, etc.)
                if (preg_match('/^UKGP(\d+)$/', $code, $groupMatches)) {
                    $groupNumber = $groupMatches[1];
                    $roles[] = sprintf('ROLE_GROUP_%s', $groupNumber);
                }
            }
        }

        return array_unique($roles);
    }
}

