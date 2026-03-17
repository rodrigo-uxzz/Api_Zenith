<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Psicologo;
use App\Models\Paciente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{

//CADASTRO PSICOLOGO
    public function cadastroPsicologo(Request $request)
    {
        DB::beginTransaction();
        try {

            $validatedData = $request->validate([
                'nome' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users,username',
                'email' => 'required|string|email|max:255|unique:users,email',
                'telefone' => 'required|string|max:20',
                'genero' => 'required|in:MASCULINO,FEMININO,OUTRO,PREFIRO_NAO_INFORMAR',
                'senha' => 'required|string|min:6',
                'data' => 'required|date',
                'cpf' => 'required|string|size:11|unique:users,cpf',
                'crp' => 'required|string|max:20|unique:psicologo,crp',
                'cadastroEpsi' => 'required|boolean',
                'formacao' => 'required|in:GRADUACAO,BACHARELADO,LICENCIATURA,ESPECIALIZACAO,MESTRADO,DOUTORADO,POS_DOUTORADO',
                'termos' => 'required|boolean',
            ]);

            $user = User::create([
                'nome' => $validatedData['nome'],
                'username' => $validatedData['username'],
                'email'=> $validatedData['email'],
                'telefone'=>$validatedData['telefone'],
                'genero'=>$validatedData['genero'],
                'senha_hash'=>Hash::make($validatedData['senha']),
                'data_nascimento'=>$validatedData['data'],
                'cpf'=>$validatedData['cpf'],
                'tipo_usuario'=>'psicologo' ,
                'status_usuario'=>'ativo',
                'termos_aceitos'=>$validatedData['termos'],

            ]);

            Psicologo::create([
                'id_usuario' => $user->id_usuario,
                'crp'=> $validatedData['crp'],
                'cadastro_e-psi'=>$validatedData['cadastroEpsi'],
                'grau_formacao'=>$validatedData['formacao'],
                'status_psicologo' => 'pendente',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Psicologo cadastrado com sucesso',
                'user' => $user,
            ], 201);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => 'Erro ao cadastrar Psicologo',
                'message' => $e->getMessage()
            ], 500);

        }
    }

    public function cadastroPaciente(Request $request)
    {
        DB::beginTransaction();
        try{
            $validatedData = $request->validate([
                'nome' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users,username',
                'email' => 'required|string|email|max:255|unique:users,email',
                'telefone' => 'required|string|max:20',
                'genero' => 'required|in:MASCULINO,FEMININO,OUTRO,PREFIRO_NAO_INFORMAR',
                'senha' => 'required|string|min:6',
                'data' => 'required|date',
                'cpf' => 'required|string|size:11|unique:users,cpf',
                'termos' => 'required|boolean',
            ]);

            $user = User::create([
                'nome' => $validatedData['nome'],
                'username' => $validatedData['username'],
                'email' => $validatedData['email'],
                'telefone' => $validatedData['telefone'],
                'genero' => $validatedData['genero'],
                'senha_hash' =>Hash::make($validatedData['senha']),
                'data_nascimento' => $validatedData['data'],
                'cpf' => $validatedData['cpf'],
                'tipo_usuario' => 'paciente',
                'status_usuario' => 'ativo',
                'termos_aceitos' => $validatedData['termos'],

            ]);

            Paciente::create([
                'id_usuario' => $user->id_usuario,
                'status_paciente' => 'ativo'

            ]);

            DB::commit();

            return response()->json([
                'message' => 'Paciente cadastrado com sucesso',
                'user' => $user
            ], 200);

        }catch(\Exception $e){
            DB::rollback();

            return response()->json([
                'error' => 'Erro ao cadastrar paciente',
                'message' => $e->getMessage()
            ]);
        }
    }

}
