<?php

use Pecee\SimpleRouter\SimpleRouter;
use sistema\Nucleo\Helpers;

try {
    //ROTAS SITE
    SimpleRouter::setDefaultNamespace('sistema\Controlador');

   /*  SimpleRouter::get(URL_SITE, 'SiteControlador@index');
    SimpleRouter::get(URL_SITE . 'index', 'SiteControlador@index');
    SimpleRouter::get(URL_SITE . 'blog', 'SiteControlador@blog');
    SimpleRouter::post(URL_SITE . 'popup', 'SiteControlador@popup');
    SimpleRouter::get(URL_SITE . 'sobre-nos', 'SiteControlador@sobre');
    SimpleRouter::get(URL_SITE . 'Arthrom', 'SiteControlador@arthrom');
    SimpleRouter::get(URL_SITE . 'produtos', 'SiteControlador@produtos');
    SimpleRouter::match(['get', 'post'], URL_SITE . 'compliance', 'SiteControlador@compliance');
    SimpleRouter::get(URL_SITE . 'post/{categoria}/{slug}', 'SiteControlador@post');
    SimpleRouter::get(URL_SITE . 'produto/{slug}', 'SiteControlador@produto');
    SimpleRouter::get(URL_SITE . 'categoria/{slug}/{pagina?}', 'SiteControlador@categoria');
    SimpleRouter::post(URL_SITE . 'buscar', 'SiteControlador@buscar');
    SimpleRouter::get(URL_SITE . '404', 'SiteControlador@erro404');
    SimpleRouter::match(['get', 'post'], URL_SITE . 'contato', 'SiteControlador@contato');
    SimpleRouter::match(['get', 'post'], URL_SITE . 'contato/denuncia', 'SiteControlador@denuncia');
 */

    //USUARIOS
    SimpleRouter::match(['get', 'post'], URL_SITE . 'cadastro', 'UsuarioControlador@cadastro');
    SimpleRouter::get(URL_SITE . 'logar', 'UsuarioControlador@logar');
    SimpleRouter::post(URL_SITE . 'login', 'UsuarioControlador@login');
    SimpleRouter::match(['get', 'post'], URL_SITE . 'usuario/confirmar/email/{token}', 'UsuarioControlador@confirmarEmail');

    // SAAS
    SimpleRouter::get(URL_SITE . 'saas', 'SaasControlador@index');
    SimpleRouter::get(URL_SITE . 'saas/cadastrar', 'SaasControlador@cadastrar');
    SimpleRouter::get(URL_SITE . 'saas/sair', 'SaasControlador@sair');
    SimpleRouter::get(URL_SITE . 'saas/chamados', 'SaasControlador@chamados');
    SimpleRouter::get(URL_SITE . 'saas/criarchamado', 'SaasControlador@criar');
    SimpleRouter::match(['get', 'post'], URL_SITE . 'saas/solicitacao/cadastrar', 'SaasControlador@cadastrar');
    SimpleRouter::match(['get', 'post'], URL_SITE . 'saas/solicitacao/editar/{id}', 'SaasControlador@editar');
    SimpleRouter::match(['get', 'post'], URL_SITE . 'saas/solicitacao/deletar/{id}', 'SaasControlador@deletar');
    SimpleRouter::post(URL_ADMIN . 'saas/solicitacao/datatable', 'SaasControlador@datatable');

    //ROTAS ADMIN
    SimpleRouter::group(['namespace' => 'Admin'], function () {

        //ADMIN LOGIN
        SimpleRouter::get(URL_ADMIN, 'AdminLogin@index');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'login', 'AdminLogin@login');

        //DASHBOAD
        SimpleRouter::get(URL_ADMIN . 'dashboard', 'AdminDashboard@dashboard');
        SimpleRouter::get(URL_ADMIN . 'sair', 'AdminDashboard@sair');

      /*   //BANNER
        SimpleRouter::get(URL_ADMIN . 'banner/listar', 'AdminBanner@listar');
        SimpleRouter::match(['get', 'post'],URL_ADMIN . 'banner/cadastrar', 'AdminBanner@cadastrar');
        SimpleRouter::match(['get', 'post'],URL_ADMIN . 'banner/editar/{id}', 'AdminBanner@editar');
        SimpleRouter::get(URL_ADMIN . 'banner/deletar/{id}', 'AdminBanner@deletar');
        SimpleRouter::get(URL_ADMIN . 'banner/gerar-pdf/{searchTerm}', 'AdminBanner@gerarPDF');
        SimpleRouter::get(URL_ADMIN . 'banner/gerar-excel/{searchTerm}', 'AdminBanner@gerarExcel'); */

        //CLIENTES
        SimpleRouter::get(URL_ADMIN . 'clientes/listar', 'AdminClientes@listar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'clientes/cadastrar', 'AdminClientes@cadastrar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'clientes/editar/{id}', 'AdminClientes@editar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'clientes/historicoAluguel', 'AdminClientes@historicoAluguel');
        SimpleRouter::get(URL_ADMIN . 'clientes/deletar/{id}', 'AdminClientes@deletar');
        SimpleRouter::get(URL_ADMIN . 'clientes/relatorioPdfCliente', 'AdminClientes@RelatorioClientePdf');

        //ADMIN USUARIOS
        SimpleRouter::get(URL_ADMIN . 'usuarios/listar', 'AdminUsuarios@listar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'usuarios/cadastrar', 'AdminUsuarios@cadastrar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'usuarios/editar/{id}', 'AdminUsuarios@editar');
        SimpleRouter::get(URL_ADMIN . 'usuarios/deletar/{id}', 'AdminUsuarios@deletar');
        SimpleRouter::post(URL_ADMIN . 'usuarios/datatable', 'AdminUsuarios@datatable');

        //ADMIN POSTS
        SimpleRouter::get(URL_ADMIN . 'posts/listar', 'AdminPosts@listar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'posts/cadastrar', 'AdminPosts@cadastrar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'posts/editar/{id}', 'AdminPosts@editar');
        SimpleRouter::get(URL_ADMIN . 'posts/deletar/{id}', 'AdminPosts@deletar');
        SimpleRouter::post(URL_ADMIN . 'posts/datatable', 'AdminPosts@datatable');

         //ADMIN PRODUTOS
         SimpleRouter::get(URL_ADMIN . 'produtos/listar', 'AdminProdutos@listar');
         SimpleRouter::match(['get', 'post'], URL_ADMIN . 'produtos/cadastrar', 'AdminProdutos@cadastrar');
         SimpleRouter::match(['get', 'post'], URL_ADMIN . 'produtos/importar-xml', 'AdminProdutos@importarXML');
         SimpleRouter::match(['get', 'post'], URL_ADMIN . 'produtos/duplicar', 'AdminProdutos@duplicar');
         SimpleRouter::match(['get', 'post'], URL_ADMIN . 'produtos/editar/{id}', 'AdminProdutos@editar');
         SimpleRouter::match(['get', 'post'], URL_ADMIN . 'produtos/duplicar/{id}', 'AdminProdutos@duplicar');
         SimpleRouter::get(URL_ADMIN . 'produtos/deletar/{id}', 'AdminProdutos@deletar');
         SimpleRouter::post(URL_ADMIN . 'produtos/datatable', 'AdminProdutos@datatable');
         SimpleRouter::get(URL_ADMIN . 'produtos/gerar-pdf/{searchTerm}', 'AdminProdutos@gerarPDF');
         SimpleRouter::get(URL_ADMIN . 'produtos/gerar-excel/{searchTerm}', 'AdminProdutos@gerarExcel');
 
        //ADMIN CATEGORIAS
        SimpleRouter::get(URL_ADMIN . 'categorias/listar', 'AdminCategorias@listar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'categorias/cadastrar', 'AdminCategorias@cadastrar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'categorias/editar/{id}', 'AdminCategorias@editar');
        SimpleRouter::get(URL_ADMIN . 'categorias/deletar/{id}', 'AdminCategorias@deletar');
        SimpleRouter::get(URL_ADMIN . 'categorias/gerar-pdf/{searchTerm}', 'AdminCategorias@gerarPDF');
        SimpleRouter::get(URL_ADMIN . 'categorias/gerar-excel/{searchTerm}', 'AdminCategorias@gerarExcel');

        // SETOR
        SimpleRouter::get(URL_ADMIN . 'setores/listar', 'AdminSetores@listar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'setores/cadastrar', 'AdminSetores@cadastrar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'setores/editar/{id}', 'AdminSetores@editar');

        //Locações
        SimpleRouter::get(URL_ADMIN . 'locacoes/listar', 'AdminLocacoes@listar');
        SimpleRouter::get(URL_ADMIN . 'produtos-locados/listar', 'AdminLocacoes@produtosLocados');
        SimpleRouter::get(URL_ADMIN . 'produtos-manutencao/listar', 'AdminLocacoes@produtosManutencao');
        SimpleRouter::post(URL_ADMIN . 'produtos/atualizar-status', 'AdminLocacoes@atualizarStatus');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'locacoes/cadastrar', 'AdminLocacoes@cadastrar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'locacoes/editar/{id}', 'AdminLocacoes@editar');
        SimpleRouter::get(URL_ADMIN . 'locacoes/gerar-pdf/{searchTerm}', 'AdminLocacoes@gerarPDF');
        SimpleRouter::get(URL_ADMIN . 'locacoes/congregacoes/{setorId}', 'AdminLocacoes@congregacoesPorSetor');
      
        // CONGREGACÕES
        SimpleRouter::get(URL_ADMIN . 'congregacoes/listar', 'AdminCongregacoes@listar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'congregacoes/cadastrar', 'AdminCongregacoes@cadastrar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'congregacoes/editar/{id}', 'AdminCongregacoes@editar');

        //ADMIN SOLICITAÇÔES
        SimpleRouter::get(URL_ADMIN . 'solicitacao/listar', 'AdminSolicitacao@listar');
        SimpleRouter::post(URL_ADMIN . 'solicitacao/datatable', 'AdminSolicitacao@datatable');
        SimpleRouter::post(URL_ADMIN . 'solicitacao/statusChamado/{id}', 'AdminSolicitacao@status');
        SimpleRouter::get(URL_ADMIN . 'solicitacao/deletar/{id}', 'AdminSolicitacao@deletar');

        //ADMIN CONFIGURAÇÔES
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'configuracao/listar', 'AdminConfiguracao@listar');
        SimpleRouter::match(['get', 'post'], URL_ADMIN . 'configuracao/editar/{id}', 'AdminConfiguracao@editar');
    });

    SimpleRouter::start();
} catch (Pecee\SimpleRouter\Exceptions\NotFoundHttpException $ex) {
    if (Helpers::localhost()) {
        echo $ex->getMessage();
    } else {
        Helpers::redirecionar('404');
    }
}
