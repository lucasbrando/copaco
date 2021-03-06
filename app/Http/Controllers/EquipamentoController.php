<?php

namespace App\Http\Controllers;


use App\Equipamento;
use Carbon\Carbon;
use App\Rede;
use Illuminate\Http\Request;
use App\Utils\NetworkOps;

class EquipamentoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $equipamentos = Equipamento::all();
        return view('equipamentos.index', compact('equipamentos'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $redes = Rede::all();
        return view('equipamentos.create' , compact('redes')); 
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $mensagem = ['macaddress.regex' => 'O Formato do MAC ADDRESS tem que ser xx:xx:xx:xx:xx:xx"'];
        $this->validate(request(), ['macaddress' => 'regex:/([a-fA-F0-9]{2}[:]?){6}/'], $mensagem);

        // monta array com ips já em uso nesta rede
        $rede = new Rede;
        $rede = $rede->find($request->rede_id);
        $ips_alocados = $rede->equipamentos->pluck('ip')->all();
        ($ips_alocados != null) ? :$ips_alocados = [];

        // aloca ip para a rede escolhida
        $ops = new NetworkOps();
        $ip = $ops->nextIpAvailable($ips_alocados, $rede->iprede, $rede->cidr, $rede->gateway);
      
        Equipamento::create([
          'naopatrimoniado' => $request->naopatrimoniado,
          'patrimonio' => $request->patrimonio,
          'descricaosempatrimonio' => $request->descricaosempatrimonio,
          'macaddress' => $request->macaddress,
          'local' => $request->local,
          'ip' => $ip,
          'rede_id' => $request->rede_id,
          'vencimento' => Carbon::createFromFormat('d/m/Y', $request->vencimento),
        ]);

        // Melhorar este redirecionamento...
        session()->flash('alert-success', 'Equipamento cadastrado com sucesso!');
        return redirect('/equipamentos');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Equipamento  $equipamento
     * @return \Illuminate\Http\Response
     */
    public function show(Equipamento $equipamento)
    {
        dd($equipamento)   ;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Equipamento  $equipamento
     * @return \Illuminate\Http\Response
     */
    public function edit(Equipamento $equipamento)
    {
        /* Rota gerada pelo laravel:
        http://devserver:porta/equiapmento/{id}/edit
        */
        $equipamento->vencimento = Carbon::createFromFormat('Y-m-d', $equipamento->vencimento)->format('d/m/Y');
        $redes = Rede::all();
        return view ('equipamentos.edit', compact('equipamento','redes'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Equipamento  $equipamento
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $mensagem = ['macaddress.regex' => 'O Formato do MAC ADDRESS tem que ser xx:xx:xx:xx:xx:xx"'];
        $this->validate(request(), ['macaddress' => 'regex:/([a-fA-F0-9]{2}[:]?){6}/'], $mensagem);
        $equipamento = Equipamento::findOrFail($id);

        $equipamento->naopatrimoniado  = $request->naopatrimoniado;
        $equipamento->patrimonio    = $request->patrimonio;
        $equipamento->macaddress    = $request->macaddress;
        $equipamento->local         = $request->local;
        $equipamento->vencimento    = Carbon::createFromFormat('d/m/Y', $request->vencimento);
        
        //Caso alterar a rede, pegar o proximo ip livre daquela rede.
        if($equipamento->rede_id != $request->rede_id){
            $equipamento->rede_id       = $request->rede_id;
            // monta array com ips já em uso nesta rede
            $rede = new Rede;
            $rede = $rede->find($request->rede_id);
            $ips_alocados = $rede->equipamentos->pluck('ip')->all();
            ($ips_alocados != null) ? :$ips_alocados = [];
            // aloca ip para a rede escolhida
            $ops = new NetworkOps();
            $ip = $ops->nextIpAvailable($ips_alocados, $rede->iprede, $rede->cidr, $rede->gateway);
            $equipamento->ip = $ip;
        }

        try {            
            $equipamento->save();
            $request->session()->flash('alert-success', 'Equipamento atualizado com sucesso!');
            return redirect()->route('equipamentos.index');
        } catch (Exception $e) {
            $request->session()->flash('alert-danger', 'Houve um erro.');
            return back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Equipamento  $equipamento
     * @return \Illuminate\Http\Response
     */
    public function destroy(Equipamento $equipamento)
    {
        try {            
            $equipamento->delete();
            $request->session()->flash('alert-danger', 'Equipamento deletado com sucesso!');
            return redirect()->route('equipamentos.index');
        } catch (Exception $e) {
            $request->session()->flash('alert-danger', 'Houve um erro.');
            return back();
        }
    }

    public function search(Request $request)
    {
       $equipamentos = Equipamento::where('macaddress', 'LIKE',  '%' . $request->pesquisar . '%')->get();
       if ($equipamentos->isEmpty()){  
        $request->session()->flash('alert-danger', 'Não há registros com esta busca.');
       }
       return view('equipamentos.index', compact('equipamentos'));
    }
    
}
