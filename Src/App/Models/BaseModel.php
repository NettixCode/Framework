<?php

namespace Nettixcode\App\Models;

use Illuminate\Database\Eloquent\Model;
use Nettixcode\App\Traits\Crud\CrudBasic;
use Nettixcode\App\Traits\Crud\withExcel;

abstract class BaseModel extends Model
{
    use CrudBasic, withExcel;
}
