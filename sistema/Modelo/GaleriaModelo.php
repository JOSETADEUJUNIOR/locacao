<?php

namespace sistema\Modelo;

use sistema\Nucleo\Conexao;
use sistema\Nucleo\Modelo;

/**
 * Classe CategoriaModelo
 *
 * @author Jose Tadeu
 */
class GaleriaModelo extends Modelo
{

    public function __construct()
    {
        parent::__construct('galeria');
    }
    /**
     * Salva o post com slug
     * @return bool
     */
    public function salvar(): bool
    {
        return parent::salvar();
    }

}
