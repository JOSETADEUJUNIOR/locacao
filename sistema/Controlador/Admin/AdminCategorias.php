<?php

namespace sistema\Controlador\Admin;


use sistema\Modelo\CategoriaModelo;
use sistema\Nucleo\Helpers;
use sistema\Modelo\PostModelo;
use Verot\Upload\Upload;
use TCPDF;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Classe AdminCategorias
 *
 * @author Jose Tadeu
 */
class AdminCategorias extends AdminControlador
{

    /**
     * Lista categorias
     * @return void
     */

     private string $capa;
     private string $arquivo;

    public function listar(): void
    {
        $categorias = new CategoriaModelo();
        
        echo $this->template->renderizar('categorias/listar.html', [
            'categorias' => $categorias->busca()->ordem('titulo ASC')->resultado(true),
            'total' => [
                'categorias' => $categorias->total(),
                'categoriasAtiva' => $categorias->busca('status = 1')->total(),
                'categoriasInativa' => $categorias->busca('status = 0')->total(),
            ]
        ]);
    }

    /**
     * Cadastra uma categoria
     * @return void
     */
    public function cadastrar(): void
    {
       

        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {
            
            if ($this->validarDados($dados)) {
                $categoria = new CategoriaModelo();

                $categoria->usuario_id          = $this->usuario->id;
                $categoria->slug                = Helpers::slug($dados['titulo']);
                $categoria->titulo              = $dados['titulo'];
                $categoria->tipo_categoria      = $dados['tipo_categoria'];
                $categoria->status              = $dados['status'];
                $categoria->imagem              = $this->capa ?? null;
                $categoria->capa_ativa          = $dados['capa_ativa'];
                $categoria->arquivo             = $this->arquivo ?? null;
                $categoria->arquivo_ativo       = $dados['arquivo_ativo'];

                if ($categoria->salvar()) {
                    $this->mensagem->sucesso('Categoria cadastrada com sucesso')->flash();
                    Helpers::redirecionar('admin/categorias/listar');
                } else {
                    $this->mensagem->erro($categoria->erro())->flash();
                    Helpers::redirecionar('admin/categorias/listar');
                }
            }
        }

        echo $this->template->renderizar('categorias/formulario.html', [
            'categoria' => $dados
        ]);
    }

    /**
     * Edita uma categoria pelo ID
     * @param int $id
     * @return void
     */
    public function editar(int $id): void
    {
        $categoria = (new CategoriaModelo())->buscaPorId($id);

        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {
            if ($this->validarDados($dados)) {
                $categoria = (new CategoriaModelo())->buscaPorId($categoria->id);
                $categoria->usuario_id          = $this->usuario->id;
                $categoria->slug                = Helpers::slug($dados['titulo']);
                $categoria->titulo              = $dados['titulo'];
                $categoria->tipo_categoria      = $dados['tipo_categoria'];
                $categoria->status              = $dados['status'];
                $categoria->atualizado_em       = date('Y-m-d H:i:s');
                $categoria->imagem              = $this->capa ?? $categoria->imagem;
                $categoria->capa_ativa          = $dados['capa_ativa'];
                $categoria->arquivo             = $this->arquivo ?? $categoria->imagem;
                $categoria->arquivo_ativo       = $dados['arquivo_ativo'];
                

                //atualizar a capa no DB e no servidor, se um novo arquivo de imagem for enviado
                /*  if (!empty($_FILES['capa']["name"])) {
                    if ($categoria->capa && file_exists("uploads/categoria/{$categoria->capa}")) {
                        unlink("uploads/categoria/{$categoria->capa}");
                        unlink("uploads/categoria/thumbs/{$categoria->capa}");
                    }
                    $categoria->capa = $this->capa ?? null;
                } */
                /*
                if (!empty($_FILES['arquivo']["name"])) {
                    if ($categoria->arquivo && file_exists("uploads/categoria/{$categoria->arquivo}")) {
                        unlink("uploads/categoria/{$categoria->arquivo}");
                        unlink("uploads/categoria/thumbs/{$categoria->arquivo}");
                    }
                    $categoria->capa = $this->capa ?? null;
                } */

                if ($categoria->salvar()) {
                    $this->mensagem->sucesso('Categoria atualizada com sucesso')->flash();
                    Helpers::redirecionar('admin/categorias/listar');
                } else {
                    $this->mensagem->erro($categoria->erro())->flash();
                    Helpers::redirecionar('admin/categorias/listar');
                }
            }
        }

        echo $this->template->renderizar('categorias/formulario.html', [
            'categoria' => $categoria
        ]);
    }

    /**
     * Valida os dados do formulário
     * @param array $dados
     * @return bool
     */
    private function validarDados(array $dados): bool
    {
        if (empty($dados['titulo'])) {
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

     public function gerarPDF($searchTerm)
     {
         // Use o valor do searchTerm na consulta ao banco de dados para buscar apenas os registros filtrados
         if ($searchTerm=='todos') {
             $searchTerm = '';
         }else {
             $searchTerm;
         }
         $categorias = new CategoriaModelo();
        
         
         // Crie um novo objeto mPDF
         $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 0]);
     
         // Renderize o arquivo HTML e adicione ao PDF
         $html = $this->template->renderizar('categorias/pdf.html', [
             'categorias' => $categorias->busca("id LIKE '%{$searchTerm}%' OR titulo LIKE '%{$searchTerm}%' ")->resultado(true),
            'total' => [
                 'categorias' => $categorias->total(),
                 'categoriasAtivo' => $categorias->busca('status = 1')->total(),
                 'categoriasInativo' => $categorias->busca('status = 0')->total(),
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
