<?php

namespace App\Services;

use App\Models\Agenda;

class AgendaService
{
    public function getAgendaVigente($id_psicologo, $dia_semana, $data)
    {
        $ultimaVigencia = Agenda::where('id_psicologo', $id_psicologo)
            ->where('dia_semana', $dia_semana)
            ->where('status_agenda', 'disponivel')
            ->where('data_inicio_vigencia', '<=', $data)
            ->max('data_inicio_vigencia');

        if (! $ultimaVigencia) {
            return collect();
        }

        return Agenda::where('id_psicologo', $id_psicologo)
            ->where('dia_semana', $dia_semana)
            ->where('status_agenda', 'disponivel')
            ->where('data_inicio_vigencia', $ultimaVigencia)
            ->get();
    }
}
