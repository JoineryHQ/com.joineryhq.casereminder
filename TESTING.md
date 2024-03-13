# CiviCRM: Case Reminders: Testing

## External code

Some tests in the test suite will cover code that is outside of this extension, 
and some of this code is known to trigger failures in those tests.

However, those tests are part of complete covereage for this extension.

### Enabling these tests: `env CASEREMINDER_TESTING_COVER_EXTERNAL=1`

By default, these tests will be skipped and will simply generate a warning:  
`CASEREMINDER_TESTING_COVER_EXTERNAL is NOT set; this test has been skipped. See TESTING.md`

By setting the environment variable `CASEREMINDER_TESTING_COVER_EXTERNAL`, these tests will be executed.
For example, if your phpunit command is usually like `env CIVICRM_UF=UnitTest phpunit`, you may enable
these tests with a command line like `env CASEREMINDER_TESTING_COVER_EXTERNAL=1 CIVICRM_UF=UnitTest phpunit`  

### Patching external code
If these tests are run without patching external code, they will fail. To prevent failure of
these tests, the following patches must be made to other people's code:

1. **EmailApi extension:**  
   Ensure [PR 62](https://lab.civicrm.org/extensions/emailapi/-/merge_requests/62) is applied.
2. **CiviCRM Core:**  
   In the file `[civicrm-core]/tests/events/hook_civicrm_alterMailParams.evch.php`, the
   function `hook_civicrm_alterMailParams()` will throw an exception with message
   "... Unrecognized keys ...", becaues emailapi extension uses non-standard parameters
   in its implementation of this hook. I'm unable to find any way to cause phpunit to
   skip this core test, so the only way I can get tests to run without that exception
   is to comment out (or early-return at the top of) that function.
