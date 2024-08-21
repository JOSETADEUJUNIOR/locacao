<?php

namespace sistema\Controlador\Admin;

use sistema\Modelo\PostModelo;
use sistema\Modelo\CategoriaModelo;
use sistema\Modelo\GaleriaModelo;
use sistema\Modelo\ProdutoModelo;
use sistema\Nucleo\Helpers;
use Verot\Upload\Upload;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use sistema\Modelo\CongregacaoModelo;
use sistema\Modelo\SetorModelo;

/**
 * Classe AdminPosts
 *
 * @author Jose Tadeu
 */
class AdminCongregacoes extends AdminControlador
{

    private string $capa;
    private string $capa_botao_1;
    private string $capa_botao_2;
    private string $capa_botao_3;
    private string $video;

    /**
     * Método responsável por exibir os dados tabulados utilizando o plugin datatables
     * @return void
     */
   
     public function listar(): void
    {
        $congregacoes = new CongregacaoModelo();

        echo $this->template->renderizar('congregacoes/listar.html', [
            'congregacoes' => $congregacoes->busca()->ordem('nome ASC')->resultado(true),
            'total' => [
                'congregacoes' => $congregacoes->busca(null, 'COUNT(id)', 'id')->total(),
                'congregacoesAtiva' => $congregacoes->busca('status = :s', 's=1 COUNT(status))', 'status')->total(),
                'congregacoesInativa' => $congregacoes->busca('status = :s', 's=0 COUNT(status)', 'status')->total(),
            ]
        ]);
    }

    /**
     * Cadastra posts
     * @return void
     */
    public function cadastrar(): void
    {
        $setores = (new SetorModelo())->busca("status = 1")->resultado(true);
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {
    
            if ($this->validarDados($dados)) {
                $congregacao = new CongregacaoModelo();
    
                $congregacao->usuario_id = $this->usuario->id;
                $congregacao->nome = $dados['nome'];
                $congregacao->setor_id = $dados['setor_id'];
                $congregacao->endereco = $dados['endereco'];
                $congregacao->bairro = $dados['bairro'];
                $congregacao->estado = $dados['estado'];
                $congregacao->cep = $dados['cep'];
                $congregacao->telefone = $dados['telefone'];
                $congregacao->email = $dados['email'];
                $congregacao->ministerio = $dados['ministerio'];
                $congregacao->quantidade_irmaos = $dados['quantidade_irmaos'];
                $congregacao->numero_livro_manutencao = $dados['numero_livro_manutencao'];
                $congregacao->horarios_cultos = $dados['horarios_cultos'];
                $congregacao->observacao = $dados['observacao'];
                $congregacao->status = $dados['status'];
                $congregacao->cadastrado_em = date('Y-m-d H:i:s');
                
                if ($congregacao->salvar()) {
    
                    if (!empty($_FILES['fotos'])) {
                        foreach ($_FILES['fotos']['tmp_name'] as $indice => $tmp_name) {
                            $foto_nome = $_FILES['fotos']['name'][$indice];
                            $foto_tmp = $_FILES['fotos']['tmp_name'][$indice];
    
                            // Realizar o upload da foto
                            $upload = new Upload($foto_tmp, 'pt_BR');
                            if ($upload->uploaded) {
                                $titulo_foto = Helpers::slug($dados['nome'] . '-' . $foto_nome);
                                $upload->file_new_name_body = $titulo_foto;
                                $upload->image_resize = true;
                                $upload->image_x = 800;
                                $upload->image_y = 600;
                                $upload->jpeg_quality = 90;
                                $upload->image_convert = 'jpg';
                                $upload->process('uploads/congregacoes/');
    
                                if ($upload->processed) {
                                    // Salvar o nome da foto na galeria
                                    $upload->file_new_name_body = $titulo_foto;
                                    $upload->image_resize = true;
                                    $upload->image_x = 540;
                                    $upload->image_y = 304;
                                    $upload->jpeg_quality = 70;
                                    $upload->image_convert = 'jpg';
                                    $upload->process('uploads/congregacoes/thumbs/');
                                    $upload->clean();
                                    $foto = new GaleriaModelo();
                                    $foto->foto = $upload->file_dst_name;
                                    $foto->congregacao_id = $congregacao->id; // Relacionar a foto com a congregação
                                    $foto->usuario_id = $this->usuario->id;
                                    $foto->tela = 'congregacao';
                                    $foto->status = 'ativo'; // Definir o status da foto
                                    $foto->salvar();
                                } else {
                                    // Lidar com erros de upload, se necessário
                                    $this->mensagem->alerta($upload->error)->flash();
                                }
                            }
                        }
                    }
                    $this->mensagem->sucesso('Congregação cadastrada com sucesso')->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/congregacoes/listar'));
                } else {
                    $this->mensagem->erro($congregacao->erro())->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/congregacoes/listar'));
                }
            }
        }
    
        echo $this->template->renderizar('congregacoes/formulario.html', [
            'post'      => $dados,
            'setores'   => $setores
        ]);
    }
    

    public function duplicar($id = null): void
    {
        
        if (!empty($id)) {
            $produto = (new ProdutoModelo())->buscaPorId($id);
        }
        // Buscar as imagens da galeria associadas ao produto
        $imagens = (new GaleriaModelo())->busca("produto_id = $produto->id AND tela = 'produto'")->resultado(true);
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {


            if ($this->validarDados($dados)) {
                $post = new ProdutoModelo();

                $post->usuario_id = $this->usuario->id;
                $post->categoria_id = $dados['categoria_id'];
                $post->slug = Helpers::slug($dados['titulo']);
                $post->titulo = $dados['titulo'];
                $post->sub_titulo = $dados['sub_titulo'];
                $post->forma_convenio = $dados['forma_convenio'];
                $post->ordenacao = $dados['ordenacao'];
                $post->texto = $dados['texto']; //descricao
                $post->indicacoes = $dados['indicacoes'];
                $post->status = $dados['status'];
                $post->capa = $this->capa ?? null;
                $post->capa_botao_1 = $this->capa_botao_1 ?? null;
                $post->capa_botao_2 = $this->capa_botao_2 ?? null;
                $post->capa_botao_3 = $this->capa_botao_3 ?? null;
                $post->texto_botao_1 = $dados['texto_botao_1'];
                $post->texto_botao_2 = $dados['texto_botao_2'];
                $post->texto_botao_3 = $dados['texto_botao_3'];

                if ($post->salvar()) {

                    if (!empty($_FILES['fotos'])) {
                        foreach ($_FILES['fotos']['tmp_name'] as $indice => $tmp_name) {
                            $foto_nome = $_FILES['fotos']['name'][$indice];
                            $foto_tmp = $_FILES['fotos']['tmp_name'][$indice];

                            // Realizar o upload da foto
                            $upload = new Upload($foto_tmp, 'pt_BR');
                            if ($upload->uploaded) {
                                $titulo_foto = Helpers::slug($dados['titulo'] . '-' . $foto_nome);
                                $upload->file_new_name_body = $titulo_foto;
                                $upload->image_resize = true;
                                $upload->image_x = 800;
                                $upload->image_y = 600;
                                $upload->jpeg_quality = 90;
                                $upload->image_convert = 'jpg';
                                $upload->process('uploads/produtos/');

                                if ($upload->processed) {
                                    // Salvar o nome da foto na galeria
                                    $upload->file_new_name_body = $titulo_foto;
                                    $upload->image_resize = true;
                                    $upload->image_x = 540;
                                    $upload->image_y = 304;
                                    $upload->jpeg_quality = 70;
                                    $upload->image_convert = 'jpg';
                                    $upload->process('uploads/produtos/thumbs/');
                                    $upload->clean();
                                    $foto = new GaleriaModelo();
                                    $foto->foto = $upload->file_dst_name;
                                    $foto->produto_id = $post->id; // Relacionar a foto com o post
                                    $foto->usuario_id = $this->usuario->id;
                                    $foto->tela = 'produto';
                                    $foto->status = 'ativo'; // Definir o status da foto
                                    $foto->salvar();
                                } else {
                                    // Lidar com erros de upload, se necessário
                                    $this->mensagem->alerta($upload->error)->flash();
                                }
                            }
                        }
                    }
                    $this->mensagem->sucesso('Produto cadastrado com sucesso')->flash();
                    Helpers::redirecionar('admin/produtos/listar');
                } else {
                    $this->mensagem->erro($post->erro())->flash();
                    Helpers::redirecionar('admin/produtos/listar');
                }
            }
        }

        echo $this->template->renderizar('produtos/duplicar.html', [
            'produto'       => $produto,
            'categorias'    => (new CategoriaModelo())->busca('status = 1')->resultado(true),
            'fotos'         => $imagens // Passar as imagens antigas para exibição no formulário de edição
        ]);
    }

    /**
     * Edita post pelo ID
     * @param int $id
     * @return void
     */
    public function editar(int $id): void
{
    $congregacao = (new CongregacaoModelo())->buscaPorId($id);
    $setores = (new SetorModelo())->busca("status = 1")->resultado(true);
    // Verificar se a congregação existe
    if (!$congregacao) {
        Helpers::redirecionar('404');
    }
    // Buscar as imagens da galeria associadas à congregação
    $imagens = (new GaleriaModelo())->busca("congregacao_id = $congregacao->id AND tela = 'congregacao'")->resultado(true);
    $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
    if (isset($dados)) {
        if ($this->validarDados($dados)) {
            $congregacao->usuario_id = $this->usuario->id;
            $congregacao->nome = $dados['nome'];
            $congregacao->setor_id = $dados['setor_id'];
            $congregacao->endereco = $dados['endereco'];
            $congregacao->bairro = $dados['bairro'];
            $congregacao->estado = $dados['estado'];
            $congregacao->cep = $dados['cep'];
            $congregacao->telefone = $dados['telefone'];
            $congregacao->email = $dados['email'];
            $congregacao->ministerio = $dados['ministerio'];
            $congregacao->quantidade_irmaos = $dados['quantidade_irmaos'];
            $congregacao->numero_livro_manutencao = $dados['numero_livro_manutencao'];
            $congregacao->horarios_cultos = $dados['horarios_cultos'];
            $congregacao->observacao = $dados['observacao'];
            $congregacao->status = $dados['status'];
            $congregacao->atualizado_em = date('Y-m-d H:i:s');

            if (!empty($_POST['remover_fotos'])) {
                foreach ($_POST['remover_fotos'] as $idFoto) {
                    $imagemDeletar = (new GaleriaModelo())->buscaPorId($idFoto);
                    $caminho_imagem = "uploads/congregacoes/{$imagemDeletar->foto}";
                    $caminho_thumb = "uploads/congregacoes/thumbs/{$imagemDeletar->foto}";

                    if (file_exists($caminho_imagem)) {
                        unlink($caminho_imagem);
                    }

                    if (file_exists($caminho_thumb)) {
                        unlink($caminho_thumb);
                    }

                    $imagemDeletar->deletar();
                }
            }

            // Gravar as novas fotos

            foreach ($_FILES['fotos']['tmp_name'] as $indice => $tmp_name) {
                $foto_nome = $_FILES['fotos']['name'][$indice];
                $foto_tmp = $_FILES['fotos']['tmp_name'][$indice];

                // Realizar o upload da foto
                $upload = new Upload($foto_tmp, 'pt_BR');
                if ($upload->uploaded) {
                    $titulo_foto = Helpers::slug($dados['nome'] . '-' . $foto_nome);
                    $upload->file_new_name_body = $titulo_foto;
                    $upload->image_resize = true;
                    $upload->image_x = 800;
                    $upload->image_y = 600;
                    $upload->jpeg_quality = 90;
                    $upload->image_convert = 'jpg';
                    $upload->process('uploads/congregacoes/');

                    if ($upload->processed) {
                        // Salvar o nome da foto na galeria

                        $upload->file_new_name_body = $titulo_foto;
                        $upload->image_resize = true;
                        $upload->image_x = 540;
                        $upload->image_y = 304;
                        $upload->jpeg_quality = 70;
                        $upload->image_convert = 'jpg';
                        $upload->process('uploads/congregacoes/thumbs/');
                        $upload->clean();
                        $foto = new GaleriaModelo();
                        $foto->foto = $upload->file_dst_name;
                        $foto->congregacao_id = $congregacao->id; // Relacionar a foto com a congregação
                        $foto->usuario_id = $this->usuario->id;
                        $foto->tela = 'congregacao';
                        $foto->status = 'ativo'; // Definir o status da foto
                        $foto->salvar();
                    } else {
                        // Lidar com erros de upload, se necessário
                        $this->mensagem->alerta($upload->error)->flash();
                    }
                }
            }

            if ($congregacao->salvar()) {
                $this->mensagem->sucesso('Congregação atualizada com sucesso')->flash();
            Helpers::json('redirecionar', Helpers::url('admin/congregacoes/listar'));
            } else {
                $this->mensagem->erro($congregacao->erro())->flash();
                Helpers::json('redirecionar', Helpers::url('admin/congregacoes/listar'));
            }
        }
    }

    echo $this->template->renderizar('congregacoes/formulario.html', [
        'congregacao' => $congregacao,
        'setores'     => $setores,
        'fotos'       => $imagens // Passar as imagens antigas para exibição no formulário de edição
    ]);
}



    /**
     * Valida os dados do formulário
     * @param array $dados
     * @return bool
     */
    public function validarDados(array $dados): bool
    {

        if (empty($dados['nome'])) {
            Helpers::json('erro', 'Informe um nome para a congregação!');
        }
        return true;
    }

    /**
     * Deleta posts por ID
     * @param int $id
     * @return void
     */
    public function deletar(int $id): void
    {
        if (is_int($id)) {
            $post = (new ProdutoModelo())->buscaPorId($id);
            if (!$post) {
                $this->mensagem->alerta('O post que você está tentando deletar não existe!')->flash();
                Helpers::redirecionar('admin/posts/listar');
            } else {
                if ($post->deletar()) {

                    if ($post->capa && file_exists("uploads/imagens/{$post->capa}")) {
                        unlink("uploads/imagens/{$post->capa}");
                        unlink("uploads/imagens/thumbs/{$post->capa}");
                    }
                    if ($post->capa_botao_1 && file_exists("uploads/capa_botao_1/{$post->capa_botao_1}")) {
                        unlink("uploads/capa_botao_1/{$post->capa_botao_1}");
                        unlink("uploads/capa_botao_1/thumbs/{$post->capa_botao_1}");
                    }
                    if ($post->capa_botao_2 && file_exists("uploads/capa_botao_2/{$post->capa_botao_2}")) {
                        unlink("uploads/capa_botao_2/{$post->capa_botao_2}");
                        unlink("uploads/capa_botao_2/thumbs/{$post->capa_botao_2}");
                    }
                    if ($post->capa_botao_3 && file_exists("uploads/capa_botao_3/{$post->capa_botao_3}")) {
                        unlink("uploads/capa_botao_3/{$post->capa_botao_3}");
                        unlink("uploads/capa_botao_3/thumbs/{$post->capa_botao_3}");
                    }

                    $this->mensagem->sucesso('Produto deletado com sucesso!')->flash();
                    Helpers::redirecionar('admin/produtos/listar');
                } else {
                    $this->mensagem->erro($post->erro())->flash();
                    Helpers::redirecionar('admin/produtos/listar');
                }
            }
        }
    }

    public function gerarPDF($searchTerm)
    {
        // Use o valor do searchTerm na consulta ao banco de dados para buscar apenas os registros filtrados
        if ($searchTerm == 'todos') {
            $searchTerm = '';
        } else {
            $searchTerm;
        }
        $produtos = new ProdutoModelo();
        $categorias = new CategoriaModelo(); // Supondo que você tenha um modelo para categorias
    
        // Buscar os produtos com o termo de busca
        $produtosList = $produtos->busca("id LIKE '%{$searchTerm}%' OR titulo LIKE '%{$searchTerm}%' ")->resultado(true);
        
        // Buscar todas as categorias para mapear os nomes
        $categoriasList = $categorias->busca()->resultado(true);
        $categoriasMap = [];
        foreach ($categoriasList as $categoria) {
            $categoriasMap[$categoria->id] = $categoria->titulo;
        }
    
        // Adicionar o nome da categoria aos produtos
        foreach ($produtosList as $produto) {
            if (isset($categoriasMap[$produto->categoria_id])) {
                $produto->categoria_nome = $categoriasMap[$produto->categoria_id];
            } else {
                $produto->categoria_nome = 'N/A'; // Caso a categoria não seja encontrada
            }
        }
    
        // Crie um novo objeto mPDF
        $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 0]);
    
        // Renderize o arquivo HTML e adicione ao PDF
        $html = $this->template->renderizar('produtos/pdf.html', [
            'produtos' => $produtosList,
            'total' => [
                'produtos' => count($produtosList),
                'produtosAtivo' => $produtos->busca('status = 1')->total(),
                'produtosInativo' => $produtos->busca('status = 0')->total(),
            ]
        ]);
        $mpdf->WriteHTML($html);
    
        // Envie o PDF para o navegador para visualização em uma nova guia
        $mpdf->Output('relatorio_categorias.pdf', 'I');
    }
    
    


    public function gerarExcel($searchTerm): void
{
    if ($searchTerm == 'todos') {
        $searchTerm = '';
    } else {
        $searchTerm;
    }
    $dados = new ProdutoModelo();
    $categorias = new CategoriaModelo(); // Supondo que você tenha um modelo para categorias
    $totalDados = $dados->busca("id LIKE '%{$searchTerm}%' OR titulo LIKE '%{$searchTerm}%' ")->total();

    // Buscar os produtos com o termo de busca
    $produtosList = $dados->busca("id LIKE '%{$searchTerm}%' OR titulo LIKE '%{$searchTerm}%' ")->resultado(true);
    
    // Buscar todas as categorias para mapear os nomes
    $categoriasList = $categorias->busca()->resultado(true);
    $categoriasMap = [];
    foreach ($categoriasList as $categoria) {
        $categoriasMap[$categoria->id] = $categoria->titulo;
    }

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
    $sheet->setCellValue('B1', 'Produto');
    $sheet->setCellValue('C1', 'Categoria');
    $sheet->setCellValue('D1', 'Link');
    $sheet->setCellValue('E1', 'Status');
    $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);

    // Adicionar os dados dos produtos à planilha
    $row = 2;
    foreach ($produtosList as $dado) {
        $sheet->setCellValue('A' . $row, $dado->id);
        $sheet->setCellValue('B' . $row, $dado->titulo);
        $sheet->setCellValue('C' . $row, isset($categoriasMap[$dado->categoria_id]) ? $categoriasMap[$dado->categoria_id] : 'N/A');
        $sheet->setCellValue('D' . $row, Helpers::url('produto/'. $dado->slug));
        $sheet->setCellValue('E' . $row, $dado->status == 1 ? 'Ativo' : 'Inativo');
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
    header('Content-Disposition: attachment;filename="relatorio_produtos.xlsx"');
    header('Cache-Control: max-age=0');

    // Criar o objeto Writer e salvar a planilha
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
}

}
