<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\core;

/**
 * @deprecated
 */
class MockStaticFilteringExtended extends \lithium\tests\mocks\core\MockStaticMethodFiltering {

	public static function childMethod() {
		return 'Working';
	}
}

?>