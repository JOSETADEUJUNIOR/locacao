<?php

namespace sistema\Controlador\Admin;


use sistema\Nucleo\Helpers;
use Verot\Upload\Upload;
use TCPDF;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use sistema\Modelo\SetorModelo;

/**
 * Classe AdminCategorias
 *
 * @author Jose Tadeu
 */
class AdminSetores extends AdminControlador
{

    /**
     * Lista de setores
     * @return void
     */

    public function listar(): void
    {
        $setores = new SetorModelo();

        echo $this->template->renderizar('setores/listar.html', [
            'setores' => $setores->busca()->ordem('nome ASC')->resultado(true),
            'total' => [
                'setores' => $setores->total(),
                'setoresAtivo' => $setores->busca('status = 1')->total(),
                'setoresInativo' => $setores->busca('status = 0')->total(),
            ]
        ]);
    }

    /**
     * Cadastra um setor
     * @return void
     */
    public function cadastrar(): void
    {
        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        
        if (isset($dados)) {
            
            if ($this->validarDados($dados)) {
                $setor = new SetorModelo();

                $setor->usuario_id          = $this->usuario->id;
                $setor->nome                = $dados['nome'];
                $setor->status              = $dados['status'];
                if ($setor->salvar()) {
                    $this->mensagem->sucesso('Setor cadastrado com sucesso')->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/setores/listar'));
                } else {
                    $this->mensagem->erro($setor->erro())->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/setores/listar'));
                }
            }
        }

        echo $this->template->renderizar('setores/formulario.html', [
            'setor' => $dados
        ]);
    }

    /**
     * Edita uma categoria pelo ID
     * @param int $id
     * @return void
     */
    public function editar(int $id): void
    {
        $setor = (new SetorModelo())->buscaPorId($id);

        $dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($dados)) {
            if ($this->validarDados($dados)) {
                $setor->usuario_id          = $this->usuario->id;
                $setor->nome                = $dados['nome'];
                $setor->status              = $dados['status'];
                if ($setor->salvar()) {
                    $this->mensagem->sucesso('Setor atualizado com sucesso')->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/setores/listar'));
                } else {
                    $this->mensagem->erro($setor->erro())->flash();
                    Helpers::json('redirecionar', Helpers::url('admin/setores/listar'));
                }
            }
        }

        echo $this->template->renderizar('setores/formulario.html', [
            'setor' => $setor
        ]);
    }

    /**
     * Valida os dados do formulário
     * @param array $dados
     * @return bool
     */
    private function validarDados(array $dados): bool
    {
        if (empty(trim($dados['nome']))) {
            Helpers::json('erro', 'Informe um nome para o setor!');
          
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
