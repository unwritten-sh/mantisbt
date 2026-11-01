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
 * Database-free tests for ConfigParser and Tokenizer.
 *
 * ConfigParser turns the text an administrator types in adm_config_report.php
 * into a PHP value without eval(). These tests do not boot core.php.
 *
 * @package    Tests
 * @subpackage Unit
 * @copyright Copyright 2026  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 */

namespace Mantis\tests\Unit;

use ConfigParser;
use Exception;
use PHPUnit\Framework\TestCase;
use Tokenizer;

require_once dirname( __DIR__, 2 ) . '/core/constant_inc.php';
require_once dirname( __DIR__, 2 ) . '/core/classes/Tokenizer.class.php';
require_once dirname( __DIR__, 2 ) . '/core/classes/ConfigParser.class.php';

class ConfigParserUnitTest extends TestCase {

	public function testScalarValues() {
		$this->assertSame( 42, ( new ConfigParser( '42' ) )->parse() );
		$this->assertSame( 'text', ( new ConfigParser( "'text'" ) )->parse() );
		$this->assertSame( -1.5, ( new ConfigParser( '-1.5' ) )->parse() );
		$this->assertNull( ( new ConfigParser( 'null' ) )->parse() );
	}

	public function testConstantsAreReplacedByTheirValue() {
		$this->assertSame( DEVELOPER, ( new ConfigParser( 'DEVELOPER' ) )->parse() );
		$this->assertSame( ON, ( new ConfigParser( 'ON' ) )->parse() );
	}

	public function testUnknownConstantIsRejected() {
		$this->expectException( Exception::class );
		( new ConfigParser( 'NOT_A_MANTIS_CONSTANT' ) )->parse();
	}

	public function testThresholdArrayWithConstantKeys() {
		$t_parser = new ConfigParser( 'array( NEW_ => REPORTER, RESOLVED => DEVELOPER )' );
		$this->assertSame( array( NEW_ => REPORTER, RESOLVED => DEVELOPER ), $t_parser->parse() );
	}

	public function testNestedArrays() {
		$t_parser = new ConfigParser( "array( 'a' => array( 1, 2 ), 'b' => 'c' )" );
		$this->assertSame( array( 'a' => array( 1, 2 ), 'b' => 'c' ), $t_parser->parse() );
	}

	public function testExtraTokensThrowByDefault() {
		$this->expectException( Exception::class );
		( new ConfigParser( '1 + 2' ) )->parse();
	}

	public function testExtraTokensCanBeIgnored() {
		$t_parser = new ConfigParser( '1 + 2' );
		$this->assertSame( 1, $t_parser->parse( ConfigParser::EXTRA_TOKENS_IGNORE ) );
	}

	public function testInvalidPhpIsRejected() {
		$this->expectException( Exception::class );
		new Tokenizer( 'array( 1, ' );
	}

	public function testTokenizerDropsWhitespaceAndTrailingSemicolons() {
		$t_tokens = new Tokenizer( ' 1 ;; ' );
		$this->assertSame( T_LNUMBER, $t_tokens->type() );
		$this->assertSame( '1', $t_tokens->value() );
		$t_tokens->pop();
		$this->assertTrue( $t_tokens->is_empty() );
	}
}
