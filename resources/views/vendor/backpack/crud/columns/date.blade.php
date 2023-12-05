 {{ ($entry->{$column['name']}) ? $entry->{$column['name']}->format(config('backpack.base.default_date_format')) : null }}
