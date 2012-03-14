#!/bin/bash

cd `dirname $0`

raw_code_coverage_file=`pwd`"/coverage_raw.txt";
clover_file=`pwd`"/clover.xml";

echo "  "
echo " Testing "
echo "  "

cd php
test_directory=`pwd`
cd tests
echo -n "" > $raw_code_coverage_file
ls *.php | while read file
do
    tests_count=`expr $tests_count + 1`
    php -dauto_prepend_file="$test_directory/bootstrap_for_test.php" "$file" -- $raw_code_coverage_file
    current_exit_code="${?}"
    if [ "${current_exit_code}" -ne "0" ]
    then
        echo "  [  ] $file"
        echo "   -> broken! (Exit code: $current_exit_code)"
        exit $current_exit_code
    else
        echo "  [OK] $file"
    fi
done

cd ../../
#enable clover for your project, if you need it. so long, disabled!
#php php/create_clover_xml_for_raw_coverage_data.php $raw_code_coverage_file $clover_file
echo ""
php php/output_summary_for_raw_coverage_data.php $raw_code_coverage_file 100
coverage_exit_code="${?}"
echo ""
rm $raw_code_coverage_file
if [ "${coverage_exit_code}" -ne "0" ]
then
    exit ${coverage_exit_code}
fi
