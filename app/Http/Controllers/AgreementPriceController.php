<?php
namespace App\Http\Controllers;
use App\Models\Agreement; use App\Models\AgreementPrice; use App\Models\Exam; use Illuminate\Http\RedirectResponse; use Illuminate\Http\Request; use Illuminate\Validation\Rule; use Illuminate\View\View;
class AgreementPriceController extends Controller{
 public function index(Request $request):View{ $search=trim((string)$request->query('search')); $prices=AgreementPrice::with(['agreement','exam'])->when($search!=='',fn($q)=>$q->whereHas('agreement',fn($qq)=>$qq->where('nombre_institucion','like',"%{$search}%"))->orWhereHas('exam',fn($qq)=>$qq->where('nombre_examen','like',"%{$search}%")))->latest()->paginate(10)->withQueryString(); $agreements=Agreement::where('activo',true)->orderBy('nombre_institucion')->get(); $exams=Exam::where('activo',true)->orderBy('nombre_examen')->get(); return view('agreement-prices.index',compact('prices','agreements','exams','search'));}
 public function store(Request $request):RedirectResponse{ AgreementPrice::create($this->validatedData($request)); return redirect()->route('agreement-prices.index')->with('success','Precio pactado creado correctamente.');}
 public function update(Request $request, AgreementPrice $agreementPrice):RedirectResponse{ $agreementPrice->update($this->validatedData($request,$agreementPrice)); return redirect()->route('agreement-prices.index')->with('success','Precio pactado actualizado correctamente.');}
 public function destroy(AgreementPrice $agreementPrice):RedirectResponse{ $agreementPrice->delete(); return redirect()->route('agreement-prices.index')->with('success','Precio pactado eliminado correctamente.');}
 private function validatedData(Request $request, ?AgreementPrice $price=null):array{return $request->validate(['agreement_id'=>['required','exists:agreements,id'],'exam_id'=>['required','exists:exams,id'],'tipo_contraste'=>['required',Rule::in(['Con contraste','Sin contraste'])],'precio_pactado'=>['required','numeric','min:0'],]);}
}
