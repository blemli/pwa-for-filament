<?php

namespace Blemli\Pwa\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    protected $table = 'test_models';

    protected $guarded = [];
}
