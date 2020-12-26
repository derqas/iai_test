<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice_position extends Model
{
    use HasFactory;
    const FILTERS = [
        'name' => ['func'=>'filter_var','type'=>FILTER_SANITIZE_SPECIAL_CHARS],
        'unit' => ['func'=>'filter_var','type'=>FILTER_SANITIZE_SPECIAL_CHARS],
        'quantity' => ['func'=>'filter_var','type'=>FILTER_SANITIZE_NUMBER_FLOAT],
        'price' => ['func'=>'filter_var','type'=>FILTER_SANITIZE_NUMBER_FLOAT],
        'sum' => ['func'=>'filter_var','type'=>FILTER_SANITIZE_NUMBER_FLOAT]
    ];
    protected $fillable = ['name','unit','quantity','price','sum','invoice_id'];

    public static function prepare($position,$filters=[]){
        $errors = [];
        $position_to_return = [];
        foreach ($filters as $field=>$filter){
            if (isset($position[$field])) {
                if ($filter['func'] == 'filter_var') {
                    if ($filter['type'] == FILTER_SANITIZE_NUMBER_FLOAT) {
                        $position_to_return[$field] = filter_var($position[$field], $filter['type'],FILTER_FLAG_ALLOW_FRACTION);
                    }else {
                        $position_to_return[$field] = filter_var($position[$field], $filter['type']);
                    }
                }
            }else{
                $errors[] = $field.' is required for invoice';
            }
        }
        return ['position'=>$position_to_return,'errors'=>$errors];
    }
}
