#!/bin/bash

cd `dirname $0`
cd php/tests

ls *.php | while read file
do
    echo "Executing: $file"
    php "$file"
    current_exit_code="${?}"
    if [ "${current_exit_code}" -ne "0" ]
    then
        echo "   -> broken! (Exit code: $current_exit_code)"
        exit $current_exit_code
    else
        echo "   -> ok!"
    fi
done
