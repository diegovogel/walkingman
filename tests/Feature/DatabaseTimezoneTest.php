<?php

test('the mysql connection reads timestamps in the timezone the app writes', function () {
    // A guard rather than a reproduction: the suite runs on SQLite, which has no
    // session timezone, so the failure this prevents cannot be provoked here.
    // Left unset, MySQL takes the app's UTC strings as local time, shifting every
    // stored timestamp and rejecting outright the hour a spring-forward skips.
    expect(config('database.connections.mysql.timezone'))->toBe('+00:00')
        ->and(config('app.timezone'))->toBe('UTC');
});
