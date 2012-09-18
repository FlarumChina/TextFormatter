#!/bin/bash
DIR=$(dirname $(dirname $(realpath $0)))
TARGET=$(dirname $DIR)/s9e.github.com/TextFormatter/coverage

cd $DIR/tests

rm -rf $TARGET
phpunit -d memory_limit=256M -c $DIR/tests/phpunit.xml --coverage-html $TARGET

REGEXP=s/`echo $(dirname $(dirname $DIR)) | sed -e 's/\\//\\\\\//g'`//g
sed -i $REGEXP $TARGET/*.html

SHA1=`git rev-parse HEAD`
REGEXP='s/(<small>Generated by .*? at )[^.]+/\1<a href="https:\/\/github.com\/s9e\/TextFormatter\/tree\/'$SHA1'">'$SHA1'<\/a>/'
sed -i -r "$REGEXP" $TARGET/*.html
