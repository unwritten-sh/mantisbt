<?php
# MantisBT - A PHP based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Database-free consistency tests between config_defaults_inc.php,
 * core/constant_inc.php and lang/strings_english.txt.
 *
 * The files are loaded inside a function scope, so their $g_ and $s_
 * variables do not leak into the global scope. core.php is not booted.
 *
 * @package    Tests
 * @subpackage Unit
 * @copyright Copyright 2026  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 */

namespace Mantis\tests\Unit;

use MantisEnum;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/core/constant_inc.php';
require_once dirname( __DIR__, 2 ) . '/core/classes/MantisEnum.class.php';

class ConfigDefaultsUnitTest extends TestCase {

	/**
	 * @var array Variables defined by config_defaults_inc.php
	 */
	private static $defaults;

	/**
	 * @var array Variables defined by lang/strings_english.txt
	 */
	private static $strings;

	public static function setUpBeforeClass(): void {
		self::$defaults = self::loadVariables( dirname( __DIR__, 2 ) . '/config_defaults_inc.php' );
		self::$strings = self::loadVariables( dirname( __DIR__, 2 ) . '/lang/strings_english.txt' );
	}

	/**
	 * Include a PHP file in an isolated scope and return the variables it defines.
	 * @param string $p_file Path of the file to include.
	 * @return array
	 */
	private static function loadVariables( $p_file ) {
		return ( static function() use ( $p_file ) {
			require $p_file;
			return get_defined_vars();
		} )();
	}

	/**
	 * @return array Names of all enum settings in config_defaults_inc.php
	 */
	private static function enumSettings() {
		return array_values( array_filter(
			array_keys( self::$defaults ),
			static function( $p_name ) {
				return substr( $p_name, 0, 2 ) == 'g_' && substr( $p_name, -12 ) == '_enum_string';
			}
		) );
	}

	public function testDefaultsDefineTheCoreEnums() {
		$t_enums = self::enumSettings();
		$this->assertContains( 'g_status_enum_string', $t_enums );
		$this->assertContains( 'g_access_levels_enum_string', $t_enums );
		$this->assertContains( 'g_severity_enum_string', $t_enums );
	}

	public function testEveryEnumHasAnEnglishTranslationWithTheSameValues() {
		foreach( self::enumSettings() as $t_setting ) {
			$t_string = 's_' . substr( $t_setting, 2 );
			$this->assertArrayHasKey( $t_string, self::$strings, "Missing \$$t_string in strings_english.txt" );
			$this->assertSame(
				MantisEnum::getValues( self::$defaults[$t_setting] ),
				MantisEnum::getValues( self::$strings[$t_string] ),
				"\$$t_setting and \$$t_string do not define the same values"
			);
		}
	}

	public function testAccessLevelEnumMatchesConstants() {
		$t_enum = self::$defaults['g_access_levels_enum_string'];
		$this->assertSame(
			array( VIEWER, REPORTER, UPDATER, DEVELOPER, MANAGER, ADMINISTRATOR ),
			MantisEnum::getValues( $t_enum )
		);
	}

	public function testStatusEnumContainsWorkflowConstants() {
		$t_enum = self::$defaults['g_status_enum_string'];
		foreach( array( NEW_, FEEDBACK, ACKNOWLEDGED, CONFIRMED, ASSIGNED, RESOLVED, CLOSED ) as $t_status ) {
			$this->assertTrue( MantisEnum::hasValue( $t_enum, $t_status ), "Status $t_status missing" );
		}
	}

	public function testDefaultDatabaseTypeIsMysqli() {
		$this->assertSame( 'mysqli', self::$defaults['g_db_type'] );
	}
}
