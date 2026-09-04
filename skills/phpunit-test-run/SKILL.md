---
name: phpunit-test-run
description: Run PHPUnit through Mate and read the result, for the whole suite, one file, one class, or a single method. Use whenever tests should be executed, a failing test has to be narrowed down, or the test covering a class has to be found, in a project that already has a PHPUnit configuration or test suite. Not for static analysis (phpstan static analysis), refactoring (rector refactoring), or a standalone script/algorithm task with no test suite to run.
---

# PHPUnit test runs

Runs PHPUnit through Mate's CLI and returns the parsed JUnit result instead of PHPUnit's own output. Two tools:

- `phpunit-run` (opt `file`, `class`, `method`, `filter`, `configuration`, `stopOnFailure`, `mode`): the suite or any subset of it. `method` requires `class`; both are compiled into an anchored `--filter` for you. `configuration` defaults to `phpunit.xml`, `phpunit.xml.dist`, or `phpunit.dist.xml` in the project root.
- `phpunit-list-tests` (opt `directory`): discovered tests as `{file, class, method}`, scanning the `<directory>` entries of the detected configuration, or `tests` when there is none.

These commands accept `--format`: `json` to parse the result, `toon` (when `helgesverre/toon` is installed) for the smallest context footprint.

## Workflow

1. Run the narrowest scope that answers the question, then widen.
   - What you just changed: `vendor/bin/mate tools:call phpunit-run --file=tests/Service/InvoiceTest.php`
   - One known failure: `--class='App\Tests\Service\InvoiceTest' --method=testTotalIsRounded --mode=detailed`
   - Confirming a suite is green: no scope parameters, `--mode=summary`.
2. When you only know the class under test, find the test first: `vendor/bin/mate tools:call phpunit-list-tests --directory=tests/Service`, then run the class it names.
3. After a fix, re-run the narrow scope, then the full suite once.

`filter` takes a raw PHPUnit regex for what `class` and `method` cannot express. Combined with them it is ORed, so it widens the run rather than narrowing it. `stopOnFailure` hides everything after the first failure: useful to find one cause in a broadly broken suite, misleading while checking whether a fix is complete.

## Reading

- `status` is `OK` or `FAILED`; `summary` carries `tests`, `passed`, `failed`, `errors`, `warnings`, `skipped`, `time`.
- `tests: 0` with `status: OK` is not a green run. Nothing matched the scope you passed: a misspelled class, a method that does not exist, a filter matching nothing. Check the count before believing the status.
- `failures` are assertion failures, `errors` are exceptions. An error usually means the test never really ran, which makes it the more serious of the two.
- `mode` decides the detail. `summary`: counts only. `default`: failures and errors with the short class name and the base file name. `detailed`: the fully-qualified class and the full path, which is what you need before opening a file.
- `phpunit-list-tests` is an index, not the authority on what runs. It matches `*Test.php`, a class declared with `extends`, and `public function test…` methods, so tests using the `#[Test]` attribute or inherited from a base class are absent yet run fine. Never conclude from it that a test does not exist.

## Failure paths

- "Test file not found" or "class parameter is required": the tool rejected the call before PHPUnit started. Fix the call, do not retry it unchanged.
- Raw PHPUnit text instead of the structured payload: the JUnit XML could not be parsed, so the run crashed (fatal error, bootstrap or configuration failure) rather than reporting test failures. Read the text for the fatal and fix that first.
- No tests and no configuration detected: the config files are looked up in the project root only, so a call from the wrong working directory finds none.
- PHPUnit missing, or reachable only through Docker or DDEV: the extension needs `matesofmate_phpunit.custom_command`. That is project setup, not a reason to shell out to `vendor/bin/phpunit`.
