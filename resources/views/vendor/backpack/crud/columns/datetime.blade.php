{{ ($entry->{$column['name']}) ? $entry->{$column['name']}->format(config('backpack.base.default_datetime_format')) : null }}