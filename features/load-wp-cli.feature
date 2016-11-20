Feature: Test if dbsnap works.

  Scenario: Backup a db
    Given a WP install
	
	When I run `wp dbsnap`
	Then STDOUT should contain:
	  """
	  Success: Checkpoint Saved!
	  """