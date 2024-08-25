<?php

namespace sistema\Controlador\Admin;

use sistema\Modelo\BannerModelo;
use sistema\Nucleo\Sessao;
use sistema\Nucleo\Helpers;
use sistema\Modelo\PostModelo;
use sistema\Modelo\UsuarioModelo;
use sistema\Modelo\CategoriaModelo;
use sistema\Modelo\ConfiguracaoModelo;
use sistema\Modelo\CongregacaoModelo;
use sistema\Modelo\LocacaoModelo;
use sistema\Modelo\PopupModelo;
use sistema\Modelo\ProdutoModelo;
use sistema\Modelo\SolicitacaoModelo;

/**
 * Classe AdminDashboard
 *
 * @author Jose Tadeu
 */
class AdminDashboard extends AdminControlador
{

    /**
     * Home do admin
     * @return void
     */
    public function dashboard(): void
    {
        $posts          = new PostModelo();
        $usuarios       = new UsuarioModelo();
        $categorias     = new CategoriaModelo();
        $congregacoes   = new CongregacaoModelo();
        $banner         = new BannerModelo();
        $produto        = new ProdutoModelo();
        $popup          = new PopupModelo();
        $config         = (new ConfiguracaoModelo())->busca()->resultado(true);
        $solicitacao    = new SolicitacaoModelo();
        $locacoes       = new LocacaoModelo();

        echo $this->template->renderizar('dashboard.html', [
            'posts' => [
                'posts' => $posts->busca()->ordem('id DESC')->limite(5)->resultado(true),
                'total' => $posts->busca(null,'COUNT(id)','id')->total(),
                'ativo' => $posts->busca('status = :s','s=1 COUNT(status)','status')->total(),
                'inativo' => $posts->busca('status = :s','s=0 COUNT(status)','status')->total()
            ],
            'categorias' => [
                'categorias' => $categorias->busca()->ordem('id DESC')->limite(5)->resultado(true),
                'total' => $categorias->busca()->total(),
                'categoriasAtiva' => $categorias->busca('status = 1')->total(),
                'categoriasInativa' => $categorias->busca('status = 0')->total(),
            ],
            'congregacoes' => [
                'congregacoes' => $congregacoes->busca()->ordem('id DESC')->limite(5)->resultado(true),
                'total' => $congregacoes->busca()->total(),
                'congregacoesAtiva' => $congregacoes->busca('status = 1')->total(),
                'congregacoesInativa' => $congregacoes->busca('status = 0')->total(),
            ],
            'locacoes' => [
               'locacoes' => $locacoes->busca()->ordem('id DESC')->resultado(true),
               'total' => $locacoes->busca()->total(),
               'locacoesAtiva' => $locacoes->busca('status = "ativa"')->total(),
               'locacoesPendente' => $locacoes->busca('status = "pendente"')->total(),
               'locacoesFinalizado' => $locacoes->busca('status = "finalizada"')->total(),
            ],
            'devolucaoLocacoes' => [
               'devolucaoLocacoes' => $locacoes->busca('status != "finalizada"')->ordem('data_devolucao DESC')->limite(5)->resultado(true),
               'devolucaoProdutos' => $produto->busca("estado_atual = 2")->ordem('data_devolucao DESC')->resultado(true),
               'devolucaoCongregacoes' => $congregacoes->busca()->ordem('nome DESC')->resultado(true),
               'devolucaoResponsavelLocacao'  => $usuarios->busca()->resultado(true),
            ],
            'banners' => [
                'banners' => $banner->busca()->ordem('id DESC')->limite(5)->resultado(true),
                'total' => $banner->busca()->total(),
                'bannerAtiva' => $banner->busca('status = 1')->total(),
                'bannerInativa' => $banner->busca('status = 0')->total(),
            ],
            'produtos' => [
                'produtos' => $produto->busca()->ordem('id DESC')->limite(5)->resultado(true),
                'total' => $produto->busca()->total(),
                'produtoAtivo' => $produto->busca('status = 1')->total(),
                'produtoInativo' => $produto->busca('status = 0')->total(),
            ],
            'solicitacao' => [
                'horasTrabalhadas' => $solicitacao->busca('horas_trabalhadas != 0.00')->totalHoras(),
                'valor_total_hora' => $solicitacao->busca('valor_total_hora != 0.00')->valorTotalHoras(),
            ],
            'popup' => [
                'popup' => $popup->busca()->ordem('id DESC')->limite(1)->resultado(true),
                'total' => $popup->busca()->total(),
            ],
            'usuarios' => [
                'logins' => $usuarios->busca()->ordem('ultimo_login DESC')->limite(5)->resultado(true),
                'usuarios' => $usuarios->busca('level != 3')->total(),
                'usuariosAtivo' => $usuarios->busca('status = 1 AND level != 3')->total(),
                'usuariosInativo' => $usuarios->busca('status = 0 AND level != 3')->total(),
                'admin' => $usuarios->busca('level = 3')->total(),
                'adminAtivo' => $usuarios->busca('status = 1 AND level = 3')->total(),
                'adminInativo' => $usuarios->busca('status = 0 AND level = 3')->total()
            ],
            'config' => $config[0]
        ]);
    }

    /**
     * Faz logout do usuário
     * @return void
     */
    public function sair(): void
    {
        $sessao = new Sessao();
        $sessao->limpar('usuarioId');

        $this->mensagem->informa('Você saiu do painel de controle!')->flash();
        Helpers::redirecionar('admin/login');
    }

}
