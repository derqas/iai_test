<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Invoice extends Model
{
    use HasFactory;

    const FILTERS = [
        'number' => ['func'=>'filter_var','type'=>FILTER_SANITIZE_SPECIAL_CHARS],
        'date' => ['func'=>'validateDate'],
        'total' => ['func'=>'filter_var','type'=>FILTER_SANITIZE_NUMBER_FLOAT]
    ];
    protected $fillable = ['number','date','total'];

    public static function prepare($invoice,$filters=[]){
        $errors = [];
        $invoice_to_return = [];
        if (isset($invoice['positions'])){
            unset($invoice['positions']);
        }
        function validateDate($date, $format = 'Y-m-d'){
            $d = \DateTime::createFromFormat($format, $date);
            return $d && $d->format($format) == $date;
        }
        foreach ($filters as $field=>$filter){
            if (isset($invoice[$field])) {
                if ($filter['func'] == 'validateDate') {
                    if (!validateDate($invoice[$field])){
                        $invoice_to_return[$field] = '1000-01-01';
                    }else{
                        $invoice_to_return[$field] = $invoice[$field];
                    }
                }
                if ($filter['func'] == 'filter_var') {
                    if ($filter['type'] == FILTER_SANITIZE_NUMBER_FLOAT) {
                        $invoice_to_return[$field] = filter_var($invoice[$field], $filter['type'],FILTER_FLAG_ALLOW_FRACTION);
                    }else {
                        $invoice_to_return[$field] = filter_var($invoice[$field], $filter['type']);
                    }
                }
            }else{
                $errors[] = $field.' is required for invoice';
            }
        }
        return ['invoice'=>$invoice_to_return,'errors'=>$errors];
    }
}
