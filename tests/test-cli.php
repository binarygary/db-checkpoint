<?php

class DBCP_Cli_Test extends WP_UnitTestCase {

	function test_sample() {
		// replace this with some actual testing code
		$this->assertTrue( true );
	}

	function test_class_exists() {
		$this->assertTrue( class_exists( 'DBCP_Cli') );
	}

	function test_class_access() {
		$this->assertTrue( db_checkpoint()->cli instanceof DBCP_Cli );
	}
}
