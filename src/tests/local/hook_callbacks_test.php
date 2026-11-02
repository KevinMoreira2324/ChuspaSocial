<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tests for the ChuspaSocial primary navigation hook.
 *
 * @package    local_chuspasocial
 * @category   test
 * @copyright  2026 SIS-INF
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_chuspasocial\local;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use core\hook\navigation\primary_extend;
use navigation_node;

/**
 * Tests for hook_callbacks.
 *
 * @covers \local_chuspasocial\local\hook_callbacks::extend_primary_navigation
 */
class hook_callbacks_test extends advanced_testcase {
    /**
     * Reset Moodle state for each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Run the callback against a fresh primary navigation tree.
     *
     * @return navigation_node
     */
    private function extend_navigation(): navigation_node {
        $primaryview = navigation_node::create_root_node();
        $hook = $this->createMock(primary_extend::class);
        $hook->method('get_primaryview')->willReturn($primaryview);
        hook_callbacks::extend_primary_navigation($hook);
        return $primaryview;
    }

    /**
     * A logged-in user sees the ChuspaSocial link.
     */
    public function test_authenticated_user_sees_chuspasocial(): void {
        $this->setUser($this->getDataGenerator()->create_user());

        $navigation = $this->extend_navigation();
        $node = $navigation->find('chuspasocial', navigation_node::TYPE_CUSTOM);

        $this->assertNotFalse($node);
        $this->assertEquals(
            (new \moodle_url('/local/chuspasocial/index.php'))->out(false),
            $node->action->out(false)
        );
    }

    /**
     * A guest does not see the ChuspaSocial navigation node.
     */
    public function test_guest_does_not_see_chuspasocial(): void {
        $this->setGuestUser();

        $navigation = $this->extend_navigation();

        $this->assertFalse($navigation->find('chuspasocial', navigation_node::TYPE_CUSTOM));
    }

    /**
     * A user without a session does not see the ChuspaSocial node.
     */
    public function test_logged_out_user_does_not_see_chuspasocial(): void {
        $this->setUser(0);

        $navigation = $this->extend_navigation();

        $this->assertFalse($navigation->find('chuspasocial', navigation_node::TYPE_CUSTOM));
    }

    /**
     * An administrator sees Books when the marketplace is enabled.
     */
    public function test_marketplace_enabled_with_capability_shows_books(): void {
        $this->setAdminUser();
        set_config('enablemarketplace', 1, 'local_chuspasocial');

        $navigation = $this->extend_navigation();

        $this->assertNotFalse($navigation->find('chuspasocial_books', navigation_node::TYPE_CUSTOM));
    }

    /**
     * Books is hidden when the marketplace is disabled.
     */
    public function test_marketplace_disabled_hides_books(): void {
        $this->setAdminUser();
        set_config('enablemarketplace', 0, 'local_chuspasocial');

        $navigation = $this->extend_navigation();

        $this->assertFalse($navigation->find('chuspasocial_books', navigation_node::TYPE_CUSTOM));
    }
}
