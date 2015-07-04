#!/bin/bash

if [ -n "$COVERAGE" ]
then
	# Run the network tests in parallel to populate the cache
	CACHE_PRELOAD=1 phpunit --group needs-network tests/Plugins/MediaEmbed/ParserTest.php > /dev/null &
	phpunit --exclude-group needs-js --coverage-clover /tmp/clover.xml
else
	phpunit --exclude-group needs-network
fi