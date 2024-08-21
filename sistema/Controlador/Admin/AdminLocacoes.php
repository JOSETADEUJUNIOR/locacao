<?php

namespace sistema\Controlador\Admin;

use DateTime;
use sistema\Modelo\CategoriaModelo;
use sistema\Nucleo\Helpers;
use sistema\Modelo\PostModelo;
use Verot\Upload\Upload;
use TCPDF;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use sistema\Modelo\CongregacaoModelo;
use sistema\Modelo\LocacaoEquipamentoModelo;
use sistema\Modelo\LocacaoModelo;
use sistema\Modelo\ProdutoModelo;
use sistema\Modelo\SetorModelo;
use sistema\Modelo\UsuarioModelo;

/**
 * Classe AdminCategorias
 *
 * @author Jose Tadeu
 */
class AdminLocacoes extends AdminControlador
{

    /**
     * Lista categorias
     * @return void
     */

    private string $capa;
    private string $arquivo;

    public function listar(): void
    {
        $locacoes            = new LocacaoModelo();
        $congregacoes        = new CongregacaoModelo();
        $responsavelRetirada = new UsuarioModelo();
        echo $this->template->renderizar('locacoes/listar.html', [
            'locacoes'             => $locacoes->busca()->ordem('data_locacao DESC')->resultado(true),
            'congregacoes'         => $congregacoes->busca()->ordem('nome DESC')->resultado(true),
            'responsavelRetirada'  => $responsavelRetirada->busca()->resultado(true),
            'total' => [
                'locacoes'            => $locacoes->total(),
                'locacoesAtivas'      => $locacoes->busca('status = "ativa"')->total(),
                'locacoesFinalizadas' => $locacoes->busca('status = "finalizada"')->total(),
            ]
        ]);
    }

    public function produtosLocados1(): void
    {
        //preciso buscar todos os produtos que estão locados
        $produtos = new ProdutoModelo();
        $locacao  = new LocacaoModelo();
        $usuarios = new UsuarioModelo();
        echo $this->template->renderizar('locacoes/produtos-locados.html', [
            'produtos'                => $produtos->busca("estado_atual = 2")->ordem('data_devolucao DESC')->resultado(true),
            'locacoes'                => $locacao->busca('status = "ativa"')->ordem('data_devolucao DESC')->resultado(true),
            'usuarios'                => $usuarios->busca('status = 1')->ordem('nome DESC')->resultado(true),
            'total' => [
                'produtos'            => $produtos->total(),
                'produtosLocados'     => $produtos->busca('estado_atual = 2')->total(),
                'produtosManutencao'  => $produtos->busca('estado_atual = 3')->total(),
            ]
        ]);
    }

    public function produtosLocados(): void
    {
        $produtos = new ProdutoModelo();
        $locacao  = new LocacaoModelo();
        $usuarios = new UsuarioModelo();
    
        $produtosLocados = $produtos->busca("estado_atual = 2")->ordem('data_devolucao DESC')->resultado(true) ?? [];
        $locacoesAtivas = $locacao->busca('status = "ativa"')->ordem('data_devolucao DESC')->resultado(true) ?? [];
        $usuariosAtivos = $usuarios->busca('status = 1')->ordem('nome DESC')->resultado(true) ?? [];
    
        // Criar um mapa para associar locações aos responsáveis
        $usuarioMap = [];
        foreach ($usuariosAtivos as $usuario) {
            $usuarioMap[$usuario->id] = $usuario->nome;
        }
    
        // Criar um mapa para associar produtos às suas locações
        $locacaoMap = [];
        foreach ($locacoesAtivas as $loc) {
            $locacaoMap[$loc->id] = [
                'data_locacao' => $loc->data_locacao ?? 'N/A',
                'data_devolucao' => $loc->data_devolucao ?? 'N/A',
                'retirado_por' => $usuarioMap[$loc->retirado_por] ?? 'N/A'
            ];
        }
    
        foreach ($produtosLocados as $produto) {
            if (isset($locacaoMap[$produto->locacao_id])) {
                $produto->data_locacao = $locacaoMap[$produto->locacao_id]['data_locacao'];
                $produto->data_devolucao = $locacaoMap[$produto->locacao_id]['data_devolucao'];
                $produto->retirado_por = $locacaoMap[$produto->locacao_id]['retirado_por'];
            } else {
                $produto->data_locacao = 'N/A';
                $produto->data_devolucao = 'N/A';
                $produto->retirado_por = 'N/A';
            }
        }
    
        echo $this->template->renderizar('locacoes/produtos-locados.html', [
            'produtos' => $produtosLocados,
            'total' => [
                'produtos' => $produtos->total(),
                'produtosLocados' => count($produtosLocados),
                'produtosManutencao' => $produtos->busca('estado_atual = 3')->total(),
            ]
        ]);
    }
    
    public function produtosManutencao(): void
    {
        $produtos = new ProdutoModelo();
        $usuarios = new UsuarioModelo();
    
        // Busca produtos em manutenção (estado_atual = 3)
        $produtosManutencao = $produtos->busca("estado_atual = 3")->resultado(true);
    
        // Se a consulta não retornar nada, garantimos que seja um array vazio
        if (!$produtosManutencao) {
            $produtosManutencao = [];
        }
    
        $usuariosAtivos = $usuarios->busca('status = 1')->ordem('nome DESC')->resultado(true);
    
        // Criar um mapa para associar usuários responsáveis
        $usuarioMap = [];
        foreach ($usuariosAtivos as $usuario) {
            $usuarioMap[$usuario->id] = $usuario->nome;
        }
    
        foreach ($produtosManutencao as $produto) {
            // Aqui o produto não deve estar associado a nenhuma locação, mas ainda assim, podemos exibir o responsável por ele.
            $produto->retirado_por = isset($usuarioMap[$produto->retirado_por]) ? $usuarioMap[$produto->retirado_por] : 'N/A';
        }
    
        echo $this->template->renderizar('locacoes/produtos-manutencao.html', [
            'produtos'                => $produtosManutencao,
            'total' => [
                'produtos'            => $produtos->total(),
                'produtosManutencao'  => count($produtosManutencao),
            ]
        ]);
    }
    




    /**
     * Cadastra uma locação
     * @return void
     */
    public function cadastrar(): void
    {
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {

            if ($this->validarDados($dados)) {
                $locacao = new LocacaoModelo();

                // Definindo os dados da locação
                $locacao->usuario_id       = $this->usuario->id;
                $locacao->setor_id         = $dados['setor_id'];
                $locacao->igreja_id        = $dados['igreja_id'];
                $locacao->retirado_por     = $dados['usuario_id'];
                $locacao->data_locacao     = date('Y-m-d');
                #calcular a data de devolução
                // Calcular a data de devolução
                $duracao = (int) $dados['duracao_locacao'];
                $dataDevolucao = date('Y-m-d', strtotime("+$duracao days", strtotime($locacao->data_locacao)));
                $locacao->data_devolucao   = $dataDevolucao;
                $locacao->status           = $dados['status'];
                $locacao->observacoes      = $dados['observacoes'] ?? null;

                if ($locacao->salvar()) {
                    // Salvando os equipamentos associados à locação
                    $locacao_id = $locacao->ultimoId();
                    if (isset($dados['equipamentos']) && is_array($dados['equipamentos'])) {
                        foreach ($dados['equipamentos'] as $equipamento_id) {
                            $locacaoEquipamento = new LocacaoEquipamentoModelo();
                            $locacaoEquipamento->locacao_id = $locacao->id;
                            $locacaoEquipamento->equipamento_id = $equipamento_id;
                            //precisa atualizar o status do equipamento
                            if ($locacaoEquipamento->salvar()) {
                                $atualizarProduto = (new ProdutoModelo())->buscaPorId($locacaoEquipamento->equipamento_id);
                                $atualizarProduto->estado_atual = 2; //locando o produto.
                                $atualizarProduto->locacao_id   = $locacao_id; //inserindo contrato no produto

                                $atualizarProduto->salvar();
                            }
                        }
                    }
                    $this->gerarPDF($locacao_id);
                    $this->mensagem->sucesso('Locação cadastrada com sucesso')->flash();
                    Helpers::redirecionar('admin/locacoes/listar');
                } else {
                    $this->mensagem->erro($locacao->erro())->flash();
                    Helpers::redirecionar('admin/locacoes/cadastrar');
                }
            }
        }
        echo $this->template->renderizar('locacoes/formulario.html', [
            'locacao' => $dados,
            'setores' => (new SetorModelo())->busca()->resultado(true),
            'igrejas' => (new CongregacaoModelo())->busca()->resultado(true),
            'usuarios' => (new UsuarioModelo())->busca()->resultado(true),
            'equipamentos' => (new ProdutoModelo())->busca("estado_atual = 1")->resultado(true)
        ]);
    }

    public function congregacoesPorSetor(int $setorId): void
    {
        $congregacoes = (new CongregacaoModelo())->busca("setor_id = :setor_id", "setor_id={$setorId}")->resultado(true);

        // Formate o retorno para que seja um array simples com 'id' e 'nome'
        $congregacoesArray = array_map(function ($congregacao) {
            return [
                'id' => $congregacao->id,
                'nome' => $congregacao->nome,
            ];
        }, $congregacoes);

        header('Content-Type: application/json');
        echo json_encode($congregacoesArray);
    }



    /**
     * Edita uma categoria pelo ID
     * @param int $id
     * @return void
     */
    public function editar(int $id): void
    {
        // Carrega a locação existente pelo ID
        $locacao = (new LocacaoModelo())->buscaPorId($id);

        // Verifica se os dados foram enviados via POST
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {
            if ($this->validarDados($dados)) {
                // Atualiza os dados da locação com as informações fornecidas
                $locacao->usuario_id       = $this->usuario->id;
                $locacao->setor_id         = $dados['setor_id'];
                $locacao->igreja_id        = $dados['igreja_id'];
                $locacao->retirado_por     = $dados['usuario_id'];
                $locacao->data_locacao     = $locacao->data_locacao;

                // Calcular a nova data de devolução com base na duração
                $duracao = (int) $dados['duracao_locacao'];
                $dataDevolucao = date('Y-m-d', strtotime("+$duracao days", strtotime($locacao->data_locacao)));
                $locacao->data_devolucao   = $dataDevolucao;

                $locacao->status           = $dados['status'];
                $locacao->observacoes      = $dados['observacoes'] ?? null;

                // Tenta salvar as alterações na locação
                if ($locacao->salvar()) {
                    // Remove os equipamentos atuais associados à locação
                    $ferramentas = (new LocacaoEquipamentoModelo())->busca('locacao_id = :id', "id={$locacao->id}")->resultado(true);
                    if ($ferramentas) {
                        foreach ($ferramentas as $ferramenta) {
                            $ferramenta->deletar();
                        }
                    }

                    // Salva os novos equipamentos associados à locação
                    if (isset($dados['equipamentos']) && is_array($dados['equipamentos'])) {
                        foreach ($dados['equipamentos'] as $equipamento_id) {
                            $locacaoEquipamento = new LocacaoEquipamentoModelo();
                            $locacaoEquipamento->locacao_id = $locacao->id;
                            $locacaoEquipamento->equipamento_id = $equipamento_id;
                            $locacaoEquipamento->salvar();
                        }
                    }

                    $this->mensagem->sucesso('Locação atualizada com sucesso')->flash();
                    Helpers::redirecionar('admin/locacoes/listar');
                } else {
                    $this->mensagem->erro($locacao->erro())->flash();
                    Helpers::redirecionar('admin/locacoes/editar/' . $locacao->id);
                }
            }
        }

        // Carrega os equipamentos atualmente associados à locação
        $equipamentosAtuais = (new LocacaoEquipamentoModelo())->busca('locacao_id = :id', "id={$locacao->id}")->resultado(true);

        $locacao_equipamentos = is_array($equipamentosAtuais) ? array_column($equipamentosAtuais, 'equipamento_id') : [];

        // Calcular a duração da locação em dias para exibir no select
        $dataLocacao = new DateTime($locacao->data_locacao);
        $dataDevolucao = new DateTime($locacao->data_devolucao);
        $intervalo = $dataLocacao->diff($dataDevolucao);
        $diasDeLocacao = $intervalo->days;

        // Renderiza o formulário de edição da locação
        echo $this->template->renderizar('locacoes/formulario.html', [
            'locacao' => $locacao,
            'dias_de_locacao' => $diasDeLocacao,  // Passa a duração calculada para o select
            'setores' => (new SetorModelo())->busca()->resultado(true),
            'igrejas' => (new CongregacaoModelo())->busca()->resultado(true),
            'usuarios' => (new UsuarioModelo())->busca()->resultado(true),
            'equipamentos' => (new ProdutoModelo())->busca()->resultado(true),
            'locacao_equipamentos' => $locacao_equipamentos
        ]);
    }


    public function atualizarStatus()
    {
        // Verifica se os dados foram enviados via POST
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {
            // Buscar o produto pelo ID
            $produto = (new ProdutoModelo())->buscaPorId($dados['produto_id']);
            
            if ($produto) {
                // Buscar o registro de locação de equipamento antes de alterar o locacao_id
                $locacaoEquipamento = (new LocacaoEquipamentoModelo())->busca("equipamento_id = :equipamento_id AND locacao_id = :locacao_id", "equipamento_id={$produto->id}&locacao_id={$produto->locacao_id}")->resultado(true);
                
                if ($locacaoEquipamento && isset($locacaoEquipamento[0])) {
                    // Atualizar o status do produto
                    $produto->estado_atual = $dados['status'];
                    $produto->locacao_id = 0;
                    $produto->salvar();
    
                    // Atualizar a devolução do equipamento na tabela de locação de equipamentos
                    $locacaoEquipamento[0]->data_devolucao = date('Y-m-d');
                    $locacaoEquipamento[0]->status = 1; // Status 1 indica devolvido
                    $locacaoEquipamento[0]->salvar();
                    
                    // Buscar o contrato para analisar se tem mais itens ativos
                    $contrato = (new LocacaoModelo())->buscaPorId($produto->locacao_id);
                    r($contrato);exit;
                    if ($contrato[0]) {
                        $produtosAtivos = (new LocacaoEquipamentoModelo())->busca("locacao_id = :locacao_id AND status = 0", "locacao_id={$contrato->id}")->resultado(true);
    
                        // Se não houver mais produtos ativos, finalizar o contrato
                        if (empty($produtosAtivos)) {
                            $contrato->status = 'finalizada';
                            $contrato->salvar();
                        }
                    } else {
                        $this->mensagem->erro('Erro: Contrato não encontrado.')->flash();
                        Helpers::redirecionar('admin/locacoes/listar');
                    }
    
                    // Exibir mensagem de sucesso e redirecionar
                    $this->mensagem->sucesso('Produto e locação atualizados com sucesso')->flash();
                    Helpers::redirecionar('admin/locacoes/listar');
                } else {
                    $this->mensagem->erro('Erro: Equipamento não encontrado na locação.')->flash();
                    Helpers::redirecionar('admin/locacoes/listar');
                }
            } else {
                $this->mensagem->erro('Erro: Produto não encontrado.')->flash();
                Helpers::redirecionar('admin/locacoes/listar');
            }
        } else {
            $this->mensagem->erro('Erro ao tentar atualizar o status')->flash();
            Helpers::redirecionar('admin/locacoes/listar');
        }
    }
    

    


    /**
     * Valida os dados do formulário
     * @param array $dados
     * @return bool
     */
    private function validarDados(array $dados): bool
    {
        if (empty($dados['setor_id'])) {
            $this->mensagem->alerta('Escreva um título para a Categoria!')->flash();
            return false;
        }
        if (!empty($_FILES['capa'])) {
            $upload = new Upload($_FILES['capa'], 'pt_BR');
            if ($upload->uploaded) {
                $titulo = $upload->file_new_name_body = Helpers::slug($dados['titulo']);
                $upload->jpeg_quality = 90;
                $upload->image_convert = 'jpg';
                $upload->process('uploads/categoria/');

                if ($upload->processed) {
                    $this->capa = $upload->file_dst_name;
                    $upload->file_new_name_body = $titulo;
                    $upload->image_resize = true;
                    $upload->image_x = 540;
                    $upload->image_y = 304;
                    $upload->jpeg_quality = 70;
                    $upload->image_convert = 'jpg';
                    $upload->process('uploads/categoria/thumbs/');
                    $upload->clean();
                } else {
                    $this->mensagem->alerta($upload->error)->flash();
                    return false;
                }
            }
        }
        if (!empty($_FILES['arquivo'])) {
            $upload = new Upload($_FILES['arquivo'], 'pt_BR');
            if ($upload->uploaded) {
                $titulo = $upload->file_new_name_body = Helpers::slug($dados['titulo']);
                $upload->jpeg_quality = 90;
                $upload->image_convert = 'jpg';
                $upload->process('uploads/categoria/arquivo/');

                if ($upload->processed) {
                    $this->arquivo = $upload->file_dst_name;
                    $upload->file_new_name_body = $titulo;
                    $upload->image_resize = true;
                    $upload->image_x = 540;
                    $upload->image_y = 304;
                    $upload->jpeg_quality = 70;
                    $upload->image_convert = 'jpg';
                    $upload->process('uploads/categoria/arquivo/thumbs/');
                    $upload->clean();
                } else {
                    $this->mensagem->alerta($upload->error)->flash();
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Deleta uma categoria pelo ID
     * @param int $id
     * @return void
     */
    public function deletar(int $id): void
    {
        if (is_int($id)) {
            $categoria = (new CategoriaModelo())->buscaPorId($id);

            if (!$categoria) {
                $this->mensagem->alerta('A categoria que você está tentando deletar não existe!')->flash();
                Helpers::redirecionar('admin/categorias/listar');
            } else {
                if ($categoria->deletar()) {

                    if ($categoria->capa && file_exists("uploads/categoria/{$categoria->capa}")) {
                        unlink("uploads/categoria/{$categoria->capa}");
                        unlink("uploads/categoria/thumbs/{$categoria->capa}");
                    }

                    $this->mensagem->sucesso('categoria deletado com sucesso!')->flash();
                    Helpers::redirecionar('admin/categorias/listar');
                } else {
                    $this->mensagem->erro('Categoria não pode ser excluída, pois esta vinculada a notícas ou produtos')->flash();
                    Helpers::redirecionar('admin/categorias/listar');
                }
            }
        }
    }

    public function gerarPDF($locacaoId)
    {
        // Carrega a locação e os dados associados
        $locacao        = (new LocacaoModelo())->buscaPorId($locacaoId);
        $equipamentos   = (new LocacaoEquipamentoModelo())->busca('locacao_id = :id', "id={$locacaoId}")->resultado(true);
        $produtos       = (new ProdutoModelo())->busca()->resultado(true);
        $retiradoPor    = (new UsuarioModelo())->buscaPorId($locacao->retirado_por);

        // Mapeia os produtos para acesso rápido pelo ID
        $produtosMap = [];
        foreach ($produtos as $produto) {
            $produtosMap[$produto->id] = $produto->titulo;
        }

        // Adiciona o nome dos produtos aos equipamentos
        foreach ($equipamentos as $equipamento) {
            if (isset($produtosMap[$equipamento->equipamento_id])) {
                $equipamento->titulo = $produtosMap[$equipamento->equipamento_id];
            } else {
                $equipamento->titulo = 'N/A'; // Caso o produto não seja encontrado
            }
        }

        // Adiciona o nome do setor, igreja e usuário
        $locacao->setor_nome = (new SetorModelo())->buscaPorId($locacao->setor_id)->nome;
        $locacao->igreja_nome = (new CongregacaoModelo())->buscaPorId($locacao->igreja_id)->nome;
        $locacao->usuario_nome = (new UsuarioModelo())->buscaPorId($locacao->usuario_id)->nome;

        // Cria um novo objeto mPDF
        $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 0]);

        // Renderiza o arquivo HTML e adiciona ao PDF
        $html = $this->template->renderizar('locacoes/pdf.html', [
            'locacao'       => $locacao,
            'retiradoPor'   => $retiradoPor,
            'equipamentos'  => $equipamentos,
            'total' => [
                'equipamentos' => count($equipamentos)
            ]
        ]);
        $mpdf->WriteHTML($html);

        // Envia o PDF para o navegador para visualização em uma nova guia
        $mpdf->Output('invoice_locacao_' . $locacaoId . '.pdf', 'I');
    }




    public function gerarExcel($searchTerm): void
    {
        if ($searchTerm == 'todos') {
            $searchTerm = '';
        } else {
            $searchTerm;
        }
        $dados = new CategoriaModelo();
        $totalDados = $dados->busca("id LIKE '%{$searchTerm}%' OR titulo LIKE '%{$searchTerm}%' ")->total();

        // Criar uma nova planilha Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Definir os cabeçalhos e pintar a primeira linha
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '337AB7'],
            ],
        ];

        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Título');
        $sheet->setCellValue('C1', 'Tipo categoria');
        $sheet->setCellValue('D1', 'Status');
        $sheet->setCellValue('E1', 'Link');
        $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);

        // Adicionar os dados dos banners à planilha
        $row = 2;
        foreach ($dados->busca("id LIKE '%{$searchTerm}%' OR titulo LIKE '%{$searchTerm}%' ")->resultado(true) as $dado) {
            $sheet->setCellValue('A' . $row, $dado->id);
            $sheet->setCellValue('B' . $row, $dado->titulo);
            $sheet->setCellValue('C' . $row, $dado->tipo_categoria);
            $sheet->setCellValue('D' . $row, $dado->status == 1 ? 'Ativo' : 'Inativo');

            // Adicionar o link completo do arquivo
            $fileUrl = $dado->arquivo ? Helpers::url('uploads/categoria/arquivo/' . $dado->arquivo) : '';
            $sheet->setCellValue('E' . $row, $fileUrl);

            $row++;
        }

        // Definir estilo de alinhamento automático e auto dimensionar colunas
        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Inserir total de registros
        $sheet->setCellValue('A' . ($row + 1), 'Total de Registros:');
        $sheet->setCellValue('B' . ($row + 1), $totalDados);

        // Configurar cabeçalho do arquivo Excel para download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="relatorio_categorias.xlsx"');
        header('Cache-Control: max-age=0');

        // Criar o objeto Writer e salvar a planilha
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }
}
