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
 * Database-free tests for the MantisEnum class.
 *
 * Unlike tests/Mantis/MantisEnumTest.php, this test does not boot core.php,
 * so it runs without an installed MantisBT instance (fast gate).
 *
 * @package    Tests
 * @subpackage Unit
 * @copyright Copyright 2026  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 */

namespace Mantis\tests\Unit;

use MantisEnum;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/core/classes/MantisEnum.class.php';

class MantisEnumUnitTest extends TestCase {

	const STATUS_ENUM = '10:new,20:feedback,30:acknowledged,40:confirmed,50:assigned,80:resolved,90:closed';

	public function testGetLabelReturnsLabelForKnownValue() {
		$this->assertSame( 'resolved', MantisEnum::getLabel( self::STATUS_ENUM, 80 ) );
	}

	public function testGetLabelDecoratesUnknownValue() {
		$this->assertSame( '@60@', MantisEnum::getLabel( self::STATUS_ENUM, 60 ) );
	}

	public function testGetValueReturnsValueForLabel() {
		$this->assertSame( 50, MantisEnum::getValue( self::STATUS_ENUM, 'assigned' ) );
		$this->assertFalse( MantisEnum::getValue( self::STATUS_ENUM, 'unknown' ) );
	}

	public function testGetValuesKeepsDefinitionOrder() {
		$this->assertSame( array( 10, 20, 30, 40, 50, 80, 90 ), MantisEnum::getValues( self::STATUS_ENUM ) );
	}

	public function testHasValue() {
		$this->assertTrue( MantisEnum::hasValue( self::STATUS_ENUM, 90 ) );
		$this->assertTrue( MantisEnum::hasValue( self::STATUS_ENUM, '90' ) );
		$this->assertFalse( MantisEnum::hasValue( self::STATUS_ENUM, 100 ) );
	}

	public function testEmptyEnumHasNoValues() {
		$this->assertSame( array(), MantisEnum::getAssocArrayIndexedByValues( '' ) );
	}

	public function testWhitespaceAroundTuplesIsTrimmed() {
		$this->assertSame(
			array( 10 => 'viewer', 20 => 'reporter' ),
			MantisEnum::getAssocArrayIndexedByValues( '10 : viewer, 20 : reporter' )
		);
	}

	public function testLocalizedLabelFallsBackToEnumLabel() {
		$t_localized = '10:nouveau,20:retour';
		$this->assertSame( 'nouveau', MantisEnum::getLocalizedLabel( self::STATUS_ENUM, $t_localized, 10 ) );
		$this->assertSame( 'closed', MantisEnum::getLocalizedLabel( self::STATUS_ENUM, $t_localized, 90 ) );
	}
}
