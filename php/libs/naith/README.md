# naith

* Version: 1.0.0
* Date: 2012/03/25

Naith is a small (~100 loc) php test runner and report generator (with code coverage) for the command line.

## Usage

Executing `/path/to/naith_folder/naith run` in a directory, will execute all tests within `tests/`
folder and requires the file `_before_test.php` (if it's available).

If you want to to run your tests as soon as a file changes, just type
`/path/to/naith_folder/naith run-constant`.

## Writing a test

Just create a folder `tests` and create a file: `my_test.php` with the contents:

    <?php
    assert(2 == 2);
    assert(3 == 2 + 1);
    
Now you can run your tests with:

    $ naith run

Result is:

     Running Tests 
    ===============
    
      [OK] my_test.php
    
     Code Coverage (for each File)
    ===============================
    
    
    Everything is tested. Awesome!

That's it.

## Todo

* Cleanup the test code

## License

This work is copyright by DracoBlue (<http://dracoblue.net>) and licensed under the terms of MIT License.
