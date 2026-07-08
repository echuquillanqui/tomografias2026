<?php
namespace App\Http\Controllers;
use App\Models\Reagent; use Illuminate\Http\RedirectResponse; use Illuminate\Http\Request; use Illuminate\Validation\Rule; use Illuminate\View\View;
class ReagentController extends Controller{
 private const UNIDADES=['ml','unidad','frasco','caja'];
 public function index(Request $request):View{ $search=trim((string)$request->query('search')); $reagents=Reagent::withCount(['exams','stockMovements'])->when($search!=='',fn($q)=>$q->where('nombre','like',"%{$search}%"))->orderBy('nombre')->paginate(10)->withQueryString(); $unidades=self::UNIDADES; return view('reagents.index',compact('reagents','unidades','search'));}
 public function store(Request $request):RedirectResponse{ Reagent::create($this->validatedData($request)); return redirect()->route('reagents.index')->with('success','Reactivo creado correctamente.');}
 public function update(Request $request, Reagent $reagent):RedirectResponse{ $reagent->update($this->validatedData($request,$reagent)); return redirect()->route('reagents.index')->with('success','Reactivo actualizado correctamente.');}
 public function destroy(Reagent $reagent):RedirectResponse{ if($reagent->stockMovements()->exists()||$reagent->exams()->exists()) return back()->with('error','No se puede eliminar: tiene relaciones registradas.'); $reagent->delete(); return redirect()->route('reagents.index')->with('success','Reactivo eliminado correctamente.');}
 private function validatedData(Request $request, ?Reagent $reagent=null):array{ $data=$request->validate(['nombre'=>['required','string','max:255',Rule::unique('reagents')->ignore($reagent?->id)],'stock_actual'=>['required','numeric','min:0'],'unidad'=>['required',Rule::in(self::UNIDADES)],'stock_minimo'=>['required','numeric','min:0'],'activo'=>['nullable','boolean']]); $data['activo']=$request->boolean('activo'); return $data;}
}
