<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Invoice, Invoice_position};
use Illuminate\Support\Facades\Storage;

class ApiController extends Controller
{
    public function start(Request $request, $action = 'list', $id = 0){
        if ($action == 'list'){
            $data = $this->invoices_list();
        }elseif ($action == 'view'){
            $data = $this->invoice_view($id);
        }elseif ($action == 'update'){
            $data = $this->invoice_update($request);
        }elseif ($action == 'create'){
            $data = $this->invoice_create($request);
        }elseif ($action =='delete'){
            $data = $this->invoice_delete($id);
        }elseif ($action == 'position_delete'){
            $data = $this->invoice_position_delete($id, $request);
        }elseif ($action == 'get_templation'){
            $data = $this->get_templation($id);
        }else{
            $data = ['error'=>'Unknown action.'];
        }
        return response()->JSON($data);
    }

    private function get_templation($id){
        if (!Storage::disk('local')->exists('/public/'.$id.'.twig')){
            return ['templation'=>$id,'content'=>'',
                'errors'=>['File not found: '.$id.'.twig ']];
        }
        $content = Storage::disk('local')->get('/public/'.$id.'.twig');
        return ['templation'=>$id,'content'=>$content,'errors'=>[]];
    }

    private function invoice_delete($id){
        $invoice = Invoice::find((int)$id);
        if ($invoice === null){
            return $this->invoices_list(['Invoice not found.']);
        }else{
            $invoice->delete((int)$id);
            $positions = Invoice_position::where('invoice_id',(int)$id);
            foreach ($positions as $position){
                $position->delete();
            }
            return $this->invoices_list();
        }
    }

    private function invoice_position_delete($id, Request $request){
        $position = Invoice_position::find((int)$id);
        if ($position === null){
            return $this->invoice_view($request->position['invoice_id']);
        }else{
            Invoice_position::delete((int)$id);
            return $this->invoice_view($request->position['invoice_id']);
        }
    }

    private function invoice_create(Request $request){
        $invoice_data = $request->invoice;
        if ($invoice_data == null) return $this->invoices_list(['Incoming data error.']);
        $positions = $invoice_data['positions'];
        $invoice_data = Invoice::prepare($invoice_data,Invoice::FILTERS);
        if (count($invoice_data['errors'])>0){
            return $this->invoices_list($invoice_data['errors']);
        }
        $invoice_data = $invoice_data['invoice'];//var_dump($invoice_data);
        $invoice = Invoice::Create($invoice_data);
        $errors = [];
        foreach ($positions as $position){
            $position = Invoice_position::prepare($position,Invoice_position::FILTERS);
            //var_dump($position);
            if (count($position['errors'])>0){
                $errors[] = array_merge($position['errors'],$errors);
            }else{
                $position = $position['position'];
                $position['invoice_id'] = $invoice->id;
                //var_dump($position);
                Invoice_position::Create($position);
            }
        }
        return $this->invoices_list($errors);
    }

    private function invoice_update(Request $request){
        $invoice_data = $request->invoice;
        $invoice = Invoice::find((int)$invoice_data['id']);
        if ($invoice === null){
            return $this->invoices_list(['Invoice with this ID not found.']);
        }
        $positions = $invoice_data['positions'];
        $invoice_data = Invoice::prepare($invoice_data,Invoice::FILTERS);
        if (count($invoice_data['errors'])>0){
            return $this->invoices_list($invoice_data['errors']);
        }
        $invoice_data = $invoice_data['invoice'];
        foreach (Invoice::FILTERS as $field=>$none){
            $invoice->{$field} = $invoice_data[$field];
        }
        $invoice->save();
        $errors = [];
        foreach ($positions as $position_data){
            $position = Invoice_position::prepare($position_data,Invoice_position::FILTERS);
            if (count($position['errors'])>0){
                $errors[] = array_merge($position['errors'],$errors);
            }else{
                $position = $position['$position'];
                $position['invoice_id'] = $invoice->id;
                $position_find = Invoice_position::find((int)$position_data['id']);
                if ($position_find === null){
                    Invoice_position::Create($position);
                }else{
                    foreach (Invoice_position::FILTERS as $field=>$none){
                        $position_find->{$field} = $position[$field];
                    }
                    $position_find->save();
                }
            }
        }
        return $this->invoices_list($errors);
    }

    private function invoices_list($errors=[]){
        return ['invoices'=>Invoice::all(),'errors'=>$errors,'type'=>'list'];
    }

    private function invoice_view($id = 0,$errors = []){
        $id = (int)$id;
        if ($id == 0){
            $errors[] = '$id is incorrect';
            return $this->invoices_list($errors);
        }else{
            $invoice = Invoice::find($id);
            if ($invoice === null){
                return $this->invoices_list(['Invoice not found.']);
            }
            $invoice['positions'] = Invoice_position::where('invoice_id',$id)->get();
            return ['invoice'=>$invoice,'errors'=>$errors,'type'=>'view'];
        }
    }
}
