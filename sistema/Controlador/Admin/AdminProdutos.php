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

/**
 * Classe AdminPosts
 *
 * @author Jose Tadeu
 */
class AdminProdutos extends AdminControlador
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
    public function datatable(): void
    {
        $datatable = $_REQUEST;
        $datatable = filter_var_array($datatable, FILTER_SANITIZE_SPECIAL_CHARS);

        $limite = $datatable['length'];
        $offset = $datatable['start'];
        $busca = $datatable['search']['value'];

        $colunas = [
            0 => 'id',
            2 => 'titulo',
            3 => 'categoria_id',
            4 => 'visitas',
            5 => 'status',
            6 => 'estado_atual',
        ];

        $ordem = " " . $colunas[$datatable['order'][0]['column']] . " ";
        $ordem .= " " . $datatable['order'][0]['dir'] . " ";

        $posts = new ProdutoModelo();

        if (empty($busca)) {
            $posts->busca()->ordem($ordem)->limite($limite)->offset($offset);
            $total = (new ProdutoModelo())->busca(null, 'COUNT(id)', 'id')->total();
        } else {
            $posts->busca("id LIKE '%{$busca}%' OR titulo LIKE '%{$busca}%' ")->limite($limite)->offset($offset);
            $total = $posts->total();
        }

        $dados = [];

        if ($posts->resultado(true)) {
            foreach ($posts->resultado(true) as $post) {
                $dados[] = [
                    $post->id,
                    $post->capa,
                    $post->titulo,
                    $post->categoria()->titulo ?? '-----',
                    Helpers::formatarNumero($post->visitas),
                    $post->status,
                    $post->estado_atual,
                    $post->slug
                ];
            }
        }


        $retorno = [
            "draw" => $datatable['draw'],
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "data" => $dados
        ];

        echo json_encode($retorno);
    }

    /**
     * Lista posts
     * @return void
     */
    public function listar(): void
    {
        $produtos = new ProdutoModelo();
       
        echo $this->template->renderizar('produtos/listar.html', [
            'total' => [
                'produtos'            => $produtos->busca(null, 'COUNT(id)', 'id')->total(),
                'produtosAtivo'       => $produtos->busca('status = :s', 's=1 COUNT(status))', 'status')->total(),
                'produtosInativo'     => $produtos->busca('status = :s', 's=0 COUNT(status)', 'status')->total(),
                'produtosLocados'     => $produtos->busca('estado_atual = :s', 's=2 COUNT(estado_atual)', 'estado_atual')->total(),
                'produtosManutencao'  => $produtos->busca('estado_atual = :s', 's=3 COUNT(estado_atual)', 'estado_atual')->total(),

            ]
        ]);
    }

    private function gerarPatrimonioUnico(): string
{
    do {
        $patrimonio = uniqid('pat_', true);
        $existe = (new ProdutoModelo())->busca('patrimonio = :patrimonio', "patrimonio={$patrimonio}")->resultado(true);
    } while ($existe);

    return $patrimonio;
}


public function importarXML(): void
{
    if (isset($_FILES['arquivo_xml']) && $_FILES['arquivo_xml']['error'] == 0) {
        $xmlString = file_get_contents($_FILES['arquivo_xml']['tmp_name']);
        $this->importarProdutosDoXML($xmlString);
    } else {
        $this->mensagem->erro("Erro ao carregar o arquivo XML.")->flash();
    }

    Helpers::redirecionar('admin/produtos/listar');
}



public function importarProdutosDoXML(string $xmlString): void
{
    // Carregar o XML
    $xml = simplexml_load_string($xmlString);

    // Extrair os produtos do XML
    $produtos = $xml->NFe->infNFe->det;

    foreach ($produtos as $produto) {
        // Mapear os dados do produto
        $dados = [
            'categoria_id' => $this->definirCategoria($produto->prod->xProd), // Função que pode definir a categoria baseado na descrição do produto
            'slug' => Helpers::slug((string) $produto->prod->xProd),
            'titulo' => (string) $produto->prod->xProd,
            'sub_titulo' => '', // Se não houver subtítulo no XML
            'data_aquisicao' => date('Y-m-d'), // Ajustar conforme necessário
            'numero_serie' => (string) $produto->prod->cProd,
            'estado_atual' => 1,
            'localizacao' => 'Default', // Ajustar conforme necessário
            'fabricante' => (string) $produto->prod->xProd, // Se houver uma maneira de identificar o fabricante
            'modelo' => '', // Se não houver modelo específico no XML
            'valor_aquisicao' => (float) $produto->prod->vProd,
            'data_ultima_manutencao' => null, // Se não disponível
            'proxima_manutencao' => null, // Se não disponível
            'patrimonio' => $this->gerarPatrimonioUnico(),
            'texto' => (string) $produto->prod->xProd,
            'status' => 'ativo',
            'texto_botao_1' => '',
            'texto_botao_2' => '',
            'texto_botao_3' => '',
        ];

        // Validar os dados
        if ($this->validarDados($dados)) {
            $post = new ProdutoModelo();

            $post->usuario_id = $this->usuario->id;
            $post->categoria_id = $dados['categoria_id'];
            $post->slug = $dados['slug'];
            $post->titulo = $dados['titulo'];
            $post->sub_titulo = $dados['sub_titulo'];
            $post->data_aquisicao = $dados['data_aquisicao'];
            $post->numero_serie = $dados['numero_serie'];
            $post->estado_atual = 1;
            $post->localizacao = $dados['localizacao'];
            $post->fabricante = $dados['fabricante'];
            $post->modelo = $dados['modelo'];
            $post->valor_aquisicao = $dados['valor_aquisicao'];
            $post->data_ultima_manutencao = $dados['data_ultima_manutencao'];
            $post->proxima_manutencao = $dados['proxima_manutencao'];
            $post->patrimonio = $dados['patrimonio'];
            $post->texto = $dados['texto'];
            $post->status = $dados['status'];

            // Salvar o produto no banco de dados
            if ($post->salvar()) {
                $this->mensagem->sucesso("Produto '{$post->titulo}' importado com sucesso")->flash();
            } else {
                $this->mensagem->erro("Erro ao salvar o produto '{$post->titulo}': " . $post->erro())->flash();
            }
        }
    }
    
    Helpers::redirecionar('admin/produtos/listar');
}

private function definirCategoria(string $descricaoProduto): int
{
    // Lógica para definir a categoria com base na descrição do produto
    // Por exemplo, uma busca no banco de dados por palavras-chave, etc.
    return 1; // Categoria padrão se não encontrar nenhuma
}



    /**
     * Cadastra posts
     * @return void
     */
    public function cadastrar(): void
    {
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {


            if ($this->validarDados($dados)) {
                $post = new ProdutoModelo();

                $post->usuario_id = $this->usuario->id;
                $post->categoria_id = $dados['categoria_id'];
                $post->slug = Helpers::slug($dados['titulo']);
                $post->titulo = $dados['titulo'];
                $post->sub_titulo = $dados['sub_titulo'];
                $post->data_aquisicao = $dados['data_aquisicao'];
                $post->numero_serie = $dados['numero_serie'];
                $post->estado_atual = 1;
                $post->localizacao = $dados['localizacao'];
                $post->fabricante = $dados['fabricante'];
                $post->modelo = $dados['modelo'];
                $post->valor_aquisicao = $dados['valor_aquisicao'];
                $post->data_ultima_manutencao = $dados['data_ultima_manutencao'];
                $post->proxima_manutencao = $dados['proxima_manutencao'];
                $post->patrimonio = $this->gerarPatrimonioUnico();
                $post->texto = $dados['texto']; //descricao
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

        echo $this->template->renderizar('produtos/formulario.html', [
            'categorias' => (new CategoriaModelo())->busca('status = 1')->resultado(true),
            'post' => $dados
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
        $produto = (new ProdutoModelo())->buscaPorId($id);
        // Verificar se o produto existe
        if (!$produto) {
            Helpers::redirecionar('404');
        }
        // Buscar as imagens da galeria associadas ao produto
        $imagens = (new GaleriaModelo())->busca("produto_id = $produto->id AND tela = 'produto'")->resultado(true);
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {
            if ($this->validarDados($dados)) {
                $produto = (new ProdutoModelo())->buscaPorId($id);
                $produto->usuario_id = $this->usuario->id;
                $produto->categoria_id = $dados['categoria_id'];
                $produto->slug = Helpers::slug($dados['titulo']);
                $produto->titulo = $dados['titulo'];
                $produto->sub_titulo = $dados['sub_titulo'];
                $produto->data_aquisicao = $dados['data_aquisicao'];
                $produto->numero_serie = $dados['numero_serie'];
                $produto->estado_atual = $produto->estado_atual;
                $produto->localizacao = $dados['localizacao'];
                $produto->fabricante = $dados['fabricante'];
                $produto->modelo = $dados['modelo'];
                $produto->valor_aquisicao = $dados['valor_aquisicao'];
                $produto->data_ultima_manutencao = $dados['data_ultima_manutencao'];
                $produto->proxima_manutencao = $dados['proxima_manutencao'];
                //$produto->patrimonio = $this->gerarPatrimonioUnico();
                $produto->texto = $dados['texto']; //descricao
                $produto->indicacoes = $dados['indicacoes'];
                $produto->status = $dados['status'];
                $produto->texto_botao_1 = $dados['texto_botao_1'];
                $produto->texto_botao_2 = $dados['texto_botao_2'];
                $produto->texto_botao_3 = $dados['texto_botao_3'];
                $produto->atualizado_em = date('Y-m-d H:i:s');
                if (!empty($_POST['remover_fotos'])) {
                    foreach ($_POST['remover_fotos'] as $idFoto) {
                        $imagemDeletar = (new GaleriaModelo())->buscaPorId($idFoto);
                        $caminho_imagem = "uploads/produtos/{$imagemDeletar->foto}";
                        $caminho_thumb = "uploads/produtos/thumbs/{$imagemDeletar->foto}";

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
                            $foto->produto_id = $produto->id; // Relacionar a foto com o post
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

                //atualizar a capa no DB e no servidor, se um novo arquivo de imagem for enviado
                if (!empty($_FILES['capa']["name"])) {
                    if ($produto->capa && file_exists("uploads/imagens/{$produto->capa}")) {
                        unlink("uploads/produtos/{$produto->capa}");
                        unlink("uploads/produtos/thumbs/{$produto->capa}");
                    }
                    $produto->capa = $this->capa ?? null;
                }
                if (!empty($_FILES['capa_botao_1']["name"])) {
                    if ($produto->capa_botao_1 && file_exists("uploads/produtos/{$produto->capa_botao_1}")) {
                        unlink("uploads/produtos/{$produto->capa_botao_1}");
                        unlink("uploads/produtos/thumbs/{$produto->capa_botao_1}");
                    }
                    $produto->capa_botao_1 = $this->capa_botao_1 ?? null;
                }
                if (!empty($_FILES['capa_botao_2']["name"])) {
                    if ($produto->capa_botao_2 && file_exists("uploads/produtos/{$produto->capa_botao_2}")) {
                        unlink("uploads/produtos/{$produto->capa_botao_2}");
                        unlink("uploads/produtos/thumbs/{$produto->capa_botao_2}");
                    }
                    $produto->capa_botao_2 = $this->capa_botao_2 ?? null;
                }
                if (!empty($_FILES['capa_botao_3']["name"])) {
                    if ($produto->capa_botao_3 && file_exists("uploads/produtos/{$produto->capa_botao_3}")) {
                        unlink("uploads/produtos/{$produto->capa_botao_3}");
                        unlink("uploads/produtos/thumbs/{$produto->capa_botao_3}");
                    }
                    $produto->capa_botao_3 = $this->capa_botao_3 ?? null;
                }

                if ($produto->salvar()) {
                    $this->mensagem->sucesso('Produto atualizado com sucesso')->flash();
                    Helpers::redirecionar('admin/produtos/listar');
                } else {
                    $this->mensagem->erro($produto->erro())->flash();
                    Helpers::redirecionar('admin/produtos/listar');
                }
            }
        }

        echo $this->template->renderizar('produtos/formulario.html', [
            'produto'       => $produto,
            'categorias'    => (new CategoriaModelo())->busca('status = 1')->resultado(true),
            'fotos'         => $imagens // Passar as imagens antigas para exibição no formulário de edição
        ]);
    }


    /**
     * Valida os dados do formulário
     * @param array $dados
     * @return bool
     */
    public function validarDados(array $dados): bool
    {

        if (empty($dados['titulo'])) {
            Helpers::json('erro', 'Informe um nome para a ferramenta!');
        }
        if (empty($dados['texto'])) {
            $this->mensagem->alerta('Escreva um texto para o Post!')->flash();
            return false;
        }

        if (!empty($_FILES['capa'])) {
            $upload = new Upload($_FILES['capa'], 'pt_BR');
            if ($upload->uploaded) {
                $titulo = $upload->file_new_name_body = Helpers::slug($dados['titulo']);
                $upload->jpeg_quality = 90;
                $upload->image_convert = 'jpg';
                $upload->process('uploads/produtos/');

                if ($upload->processed) {
                    $this->capa = $upload->file_dst_name;
                    $upload->file_new_name_body = $titulo;
                    $upload->image_resize = true;
                    $upload->image_x = 540;
                    $upload->image_y = 304;
                    $upload->jpeg_quality = 70;
                    $upload->image_convert = 'jpg';
                    $upload->process('uploads/produtos/thumbs/');
                    $upload->clean();
                } else {
                    $this->mensagem->alerta($upload->error)->flash();
                    return false;
                }
            }
        }
        if (!empty($_FILES['capa_botao_1'])) {
            $upload = new Upload($_FILES['capa_botao_1'], 'pt_BR');
            if ($upload->uploaded) {
                $titulo = $upload->file_new_name_body = Helpers::slug($dados['titulo']);
                $upload->jpeg_quality = 90;
                $upload->image_convert = 'jpg';
                $upload->process('uploads/capa_botao_1/');

                if ($upload->processed) {
                    $this->capa_botao_1 = $upload->file_dst_name;
                    $upload->file_new_name_body = $titulo;
                    $upload->image_resize = true;
                    $upload->image_x = 540;
                    $upload->image_y = 304;
                    $upload->jpeg_quality = 70;
                    $upload->image_convert = 'jpg';
                    $upload->process('uploads/capa_botao_1/thumbs/');
                    $upload->clean();
                } else {
                    $this->mensagem->alerta($upload->error)->flash();
                    return false;
                }
            }
        }
        if (!empty($_FILES['capa_botao_2'])) {
            $upload = new Upload($_FILES['capa_botao_2'], 'pt_BR');
            if ($upload->uploaded) {
                $titulo = $upload->file_new_name_body = Helpers::slug($dados['titulo']);
                $upload->jpeg_quality = 90;
                $upload->image_convert = 'jpg';
                $upload->process('uploads/capa_botao_2/');

                if ($upload->processed) {
                    $this->capa_botao_2 = $upload->file_dst_name;
                    $upload->file_new_name_body = $titulo;
                    $upload->image_resize = true;
                    $upload->image_x = 540;
                    $upload->image_y = 304;
                    $upload->jpeg_quality = 70;
                    $upload->image_convert = 'jpg';
                    $upload->process('uploads/capa_botao_2/thumbs/');
                    $upload->clean();
                } else {
                    $this->mensagem->alerta($upload->error)->flash();
                    return false;
                }
            }
        }
        if (!empty($_FILES['capa_botao_3'])) {
            $upload = new Upload($_FILES['capa_botao_3'], 'pt_BR');
            if ($upload->uploaded) {
                $titulo = $upload->file_new_name_body = Helpers::slug($dados['titulo']);
                $upload->jpeg_quality = 90;
                $upload->image_convert = 'jpg';
                $upload->process('uploads/capa_botao_3/');

                if ($upload->processed) {
                    $this->capa_botao_3 = $upload->file_dst_name;
                    $upload->file_new_name_body = $titulo;
                    $upload->image_resize = true;
                    $upload->image_x = 540;
                    $upload->image_y = 304;
                    $upload->jpeg_quality = 70;
                    $upload->image_convert = 'jpg';
                    $upload->process('uploads/capa_botao_3/thumbs/');
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
