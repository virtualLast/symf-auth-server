<?php

namespace App\Tests\Mapper;

use App\Mapper\AccessLevelRoleMapper;
use PHPUnit\Framework\TestCase;

final class AccessLevelRoleMapperTest extends TestCase
{

    private AccessLevelRoleMapper $mapper;

    protected function setUp() : void {
        $this->mapper = new AccessLevelRoleMapper();
    }
    /**
     * Ensures every mapped user always has ROLE_USER,
     * even when no hier codes are provided.
     */
    public function test_it_always_assigns_base_user_role(): void
    {
        $roles = $this->mapper->mapToRoles([]);

        $this->assertContains('ROLE_USER', $roles);
    }

    /**
     * Maps a valid UK store hier code to a ROLE_STORE_* role.
     */
    public function test_it_maps_store_hier_code_to_store_role(): void
    {
        $roles = $this->mapper->mapToRoles(['GG-XX-TescoGlobal-HierCode UK01001']);

        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_STORE_01001', $roles);
    }

    /**
     * Maps a valid UK group hier code to a ROLE_GROUP_* role.
     */
    public function test_it_maps_group_hier_code_to_group_role(): void
    {
        $roles = $this->mapper->mapToRoles(['GG-XX-TescoGlobal-HierCode UKGP001']);

        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_GROUP_001', $roles);
    }

    /**
     * Multiple hier codes should result in multiple roles being assigned.
     */
    public function test_it_maps_multiple_hier_codes_to_multiple_roles(): void
    {
        $roles = $this->mapper->mapToRoles([
            'GG-XX-TescoGlobal-HierCode UKGP001',
            'GG-XX-TescoGlobal-HierCode UK01001'
        ]);

        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_GROUP_001', $roles);
        $this->assertContains('ROLE_STORE_01001', $roles);
        $this->assertCount(3, $roles);
    }

    /**
     * Duplicate hier codes must not result in duplicate roles.
     */
    public function test_it_deduplicates_roles(): void
    {
        $roles = $this->mapper->mapToRoles([
            'GG-XX-TescoGlobal-HierCode UKGP001',
            'GG-XX-TescoGlobal-HierCode UK01001',
            'GG-XX-TescoGlobal-HierCode UK01001'
        ]);

        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_GROUP_001', $roles);
        $this->assertContains('ROLE_STORE_01001', $roles);
        $this->assertCount(3, $roles);
    }

    /**
     * Invalid hier codes should be ignored safely
     * and must not produce unexpected roles.
     */
    public function test_it_ignores_invalid_hier_codes(): void
    {
        $roles = $this->mapper->mapToRoles([
            'GG-XX-TescoGlobal-HierCode UKGP001',
            'GG-XX-TescoGlobal-HierCode UK01001',
            'GG-XX-TescoGlobal-HierCode UK01001',
            'GG-XX-TescoGlobal-HierCode INVALID100'
        ]);

        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_GROUP_001', $roles);
        $this->assertContains('ROLE_STORE_01001', $roles);
        $this->assertCount(3, $roles);
    }

    /**
     * Mixed valid and invalid hier codes should still
     * produce roles for the valid ones.
     */
    public function test_it_maps_valid_hier_codes_when_mixed_with_invalid_ones(): void
    {
        $roles = $this->mapper->mapToRoles([
            'GG-XX-TescoGlobal-HierCode UKGP001',
            'GG-XX-TescoGlobal-HierCode 001',
            'GG-XX-TescoGlobal-HierCode UK01001',
            'GG-XX-TescoGlobal-HierCode INVALID200',
            'GG-XX-TescoGlobal-HierCode UK01001',
            'GG-XX-TescoGlobal-HierCode INVALID100',
            'GG-XX-TescoGlobal-HeetCode INVALID100'
        ]);

        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_GROUP_001', $roles);
        $this->assertContains('ROLE_STORE_01001', $roles);
        $this->assertCount(3, $roles);
    }

    /**
     * Ensures unexpected formats do not break mapping
     * and do not remove the base ROLE_USER.
     */
    public function test_it_is_resilient_to_unexpected_hier_code_formats(): void
    {
        $roles = $this->mapper->mapToRoles([
            'GG-XX-TescoGlobal-HierCode UKGP001',
            'GG-XX-TescoGlobal-HierCode 001',
            'GG-XX-TescoGlobal-HierCode UK01001',
            'GG-XX-TescoGlobal-HierCode INVALID200',
            'GG-XX-TescoGlobal-HierCode UK01001',
            'GG-XX-TescoGlobal-HierCode INVALID100',
            'GG-XX-TescoGlobal-HierCode INVALID100',
            'something_wrong_here',
            'GG-zz-TescoGlobal-HierCode INVALID100',
            'gg-XX-TescoGlobal-HeetCode INVALID100'
        ]);

        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_GROUP_001', $roles);
        $this->assertContains('ROLE_STORE_01001', $roles);
        $this->assertCount(3, $roles);
    }
}
