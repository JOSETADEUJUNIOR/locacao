<?php

namespace sistema\Controlador;

use sistema\Nucleo\Controlador;
use sistema\Modelo\PostModelo;
use sistema\Nucleo\Helpers;
use sistema\Modelo\CategoriaModelo;
use sistema\Biblioteca\Paginar;
use sistema\Modelo\BannerModelo;
use sistema\Modelo\ConfiguracaoModelo;
use sistema\Modelo\GaleriaModelo;
use sistema\Modelo\PopupModelo;
use sistema\Modelo\ProdutoModelo;
use sistema\Suporte\Email;

class SiteControlador extends Controlador
{

    public function __construct()
    {
        parent::__construct('templates/site/views');
    }

    /**
     * Home Page
     * @return void
     */
    public function index(): void
    {
        $posts = (new PostModelo())->busca("status = 1");
        $config = (new ConfiguracaoModelo())->busca()->resultado(true);
        $banner = (new BannerModelo())->busca()->resultado(true);
        echo $this->template->renderizar('index.html', [
            'posts' => [
                'slides' => $posts->ordem('id DESC')->limite(3)->resultado(true),
                'posts' => $posts->ordem('id DESC')->limite(3)->offset(0)->resultado(true),
                'maisLidos' => (new PostModelo())->busca("status = 1")->ordem('visitas DESC')->limite(5)->resultado(true),
            ],
            'categorias' => $this->categorias(),
            'banners'    => $banner,
            'config'     => $config[0],
            'rodape'     => htmlspecialchars_decode($config[0]->rodape)

        ]);
    }


    public function blog(): void
    {
        $posts = (new PostModelo())->busca("status = 1");
        $config = (new ConfiguracaoModelo())->busca()->resultado(true);
        echo $this->template->renderizar('blog.html', [
            'posts' => [
                'slides' => $posts->ordem('id DESC')->limite(3)->resultado(true),
                'posts' => $posts->ordem('id DESC')->limite(5)->offset(0)->resultado(true),
                'maisLidos' => (new PostModelo())->busca("status = 1")->ordem('visitas DESC')->limite(5)->resultado(true),
            ],
            'categorias' => $this->categorias(),
            'config'     => $config[0],
            'rodape'     => htmlspecialchars_decode($config[0]->rodape)

        ]);
    }

    /**
     * Busca posts 
     * @return void
     */
    public function buscar(): void
    {
        $busca = filter_input(INPUT_POST, 'busca', FILTER_DEFAULT);
        if (isset($busca)) {
            $posts = (new PostModelo())->busca("status = 1 AND titulo LIKE '%{$busca}%'")->resultado(true);
            if ($posts) {
                foreach ($posts as $post) {
                    echo "<li class='list-group-item fw-bold'><a href=" . Helpers::url('post/') . $post->categoria()->slug . '/' . $post->slug . ">$post->titulo</a></li>";
                }
            }
        }
    }

    /**
     * Busca post por ID
     * @param string $categoria apenas para o slug da categoria
     * @param string $slug
     * @return void
     */
    public function post(string $categoria, string $slug): void
    {
        $post = (new PostModelo())->buscaPorSlug($slug);
        if (!$post) {
            Helpers::redirecionar('404');
        }
        $post->salvarVisitas();

        echo $this->template->renderizar('post.html', [
            'post'       => $post,
            'categorias' => $this->categorias(),
            'fotos'      => (new GaleriaModelo())->busca("post_id = $post->id")->resultado(true)
        ]);
    }
    public function produto(string $slug): void
    {
        $produto = (new ProdutoModelo())->buscaPorSlug($slug);
        if (!$produto) {
            Helpers::redirecionar('404');
        }
        //r($produto);exit;
        $produto->salvarVisitas();
        $categoria = (new CategoriaModelo())->busca("id = $produto->categoria_id")->resultado(true);
        $fotos =  (new GaleriaModelo())->busca("produto_id = $produto->id")->resultado(true);
        echo $this->template->renderizar('produto.html', [
            'produto'       => $produto,
            'fotos'         => $fotos,
            'categoria'     => $categoria[0]
        ]);
    }

    /**
     * Categorias
     * @return array|null
     */
    public function categorias(): ?array
    {
        return (new CategoriaModelo())->busca("status = 1")->resultado(true);
    }

    /**
     * Lista posts por categoria
     * @param string $slug
     * @return void
     */
    public function categoria(string $slug, int $pagina = null): void
    {
        $categoria = (new CategoriaModelo())->buscaPorSlug($slug);
        if (!$categoria) {
            Helpers::redirecionar('404');
        }
        $categoria->salvarVisitas();

        $posts = (new PostModelo());
        $total = $posts->busca('categoria_id = :c AND status = :s', "c={$categoria->id}&s=1 COUNT(id)", 'id')->total();

        $paginar = new Paginar(Helpers::url('categoria/' . $slug), ($pagina ?? 1), 10, 3, $total);

        echo $this->template->renderizar('categoria.html', [
            'posts' => $posts->busca("categoria_id = {$categoria->id} AND status = 1")->limite($paginar->limite())->offset($paginar->offset())->resultado(true),
            'paginacao' => $paginar->renderizar(),
            'paginacaoInfo' => $paginar->info(),
            'categorias' => $this->categorias(),
        ]);
    }


    public function popup()
    {
        $popup = (new PopupModelo())->buscaPorId(1);
        $popup->salvarVisitas();
        return 1;
    }

    /**
     * Sobre
     * @return void
     */
    public function sobre(): void
    {
        echo $this->template->renderizar('sobre.html', [
            'titulo' => 'Sobre nós',
            'categorias' => $this->categorias(),
        ]);
    }
    public function arthrom(): void
    {
        echo $this->template->renderizar('arthrom.html', [
            'titulo' => 'Arthrom',
        ]);
    }
    public function compliance(): void
    {
        session_start();
        $token = Helpers::generateCsrfToken();

        echo $this->template->renderizar('compliance.html', [
            'titulo' => 'Compliance',
            'csrf_token' => $token,
        ]);
    }

    // Função do Helpers
    public static function generateCsrfToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Gere um token CSRF seguro
        }
        return $_SESSION['csrf_token'];
    }

    public function produtos(): void
    {
        $categorias = new CategoriaModelo();
        echo $this->template->renderizar('produtos.html', [
            'categorias' => $categorias->busca()->resultado(true),
            'titulo' => 'Produtos',
        ]);
    }

    /**
     * ERRO 404
     * @return void
     */
    public function erro404(): void
    {
        echo $this->template->renderizar('404.html', [
            'titulo' => 'Página não encontrada',
            'categorias' => $this->categorias(),
        ]);
    }

    /**
     * Contato
     * @return void
     */
    public function contato(): void
    {
        $dados = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
    
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validação do reCAPTCHA
            $recaptchaResponse = $_POST['g-recaptcha-response'];
            $secretKey = '6LfwRgEqAAAAALJni7hHP51tLWRcRieaJ6QzJKbz'; // Substitua pela chave secreta correta
            $recaptchaUrl = 'https://www.google.com/recaptcha/api/siteverify';
    
            $response = file_get_contents($recaptchaUrl . '?secret=' . $secretKey . '&response=' . $recaptchaResponse);
            $responseKeys = json_decode($response, true);
    
            if ($responseKeys["success"]) {
                // A verificação do reCAPTCHA foi bem-sucedida
               if (!Helpers::validarEmail($dados['email'])) {
                    $this->mensagem->sucesso('Preencha os campos obrigatórios')->flash();
                    Helpers::redirecionar('/contato');
                } else {
                    try {
                        $email = new Email();
    
                        $view = $this->template->renderizar('emails/contato.html', [
                            'dados' => $dados,
                        ]);
    
                        $email->criar(
                            'Contato via Site - ' . SITE_NOME,
                            $view,
                            EMAIL_REMETENTE['email'],
                            EMAIL_REMETENTE['nome'],
                            $dados['email'],
                            $dados['nome']
                        );
    
                        $anexos = $_FILES['anexos'] ?? "";
    
                        if ($anexos) {
                            foreach ($anexos['tmp_name'] as $indice => $anexo) {
                                if ($anexo == UPLOAD_ERR_OK) {
                                    $email->anexar($anexo, $anexos['name'][$indice]);
                                }
                            }
                        }
                        $email->enviar(EMAIL_REMETENTE['email'], EMAIL_REMETENTE['nome']);
    
                        $this->mensagem->sucesso('E-mail enviado com sucesso')->flash();
                        Helpers::redirecionar('/contato');
    
                    } catch (\PHPMailer\PHPMailer\Exception $ex) {
                        $this->mensagem->alerta($ex->getMessage())->flash();
                    }
                }
            } else {
                // Falha na verificação do reCAPTCHA
                $this->mensagem->alerta('Falha na verificação do reCAPTCHA')->flash();
                Helpers::redirecionar('/contato');
            }
        }
    
        echo $this->template->renderizar('contato.html', [
            'categorias' => $this->categorias(),
        ]);
    }

    public function denuncia(): void
    {
        $dados = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::validarEmail($dados['email'])) {
                $this->mensagem->sucesso('Preencha os campos obrigatórios')->flash();
                Helpers::redirecionar('/contato');
            } else {
                // Validação do reCAPTCHA
                $recaptchaResponse = $_POST['g-recaptcha-response'];
                $secretKey = '6LfwRgEqAAAAALJni7hHP51tLWRcRieaJ6QzJKbz'; // Substitua pela chave secreta correta
                $recaptchaUrl = 'https://www.google.com/recaptcha/api/siteverify';

                $response = file_get_contents($recaptchaUrl . '?secret=' . $secretKey . '&response=' . $recaptchaResponse);
                $responseKeys = json_decode($response, true);

                if ($responseKeys["success"]) {
                    try {
                        $email = new Email();

                        $view = $this->template->renderizar('emails/denuncia.html', [
                            'dados' => $dados,
                        ]);

                        $email->criar(
                            'Contato via Site em denúncia - ' . SITE_NOME,
                            $view,
                            EMAIL_REMETENTE['email'],
                            EMAIL_REMETENTE['nome'],
                            $dados['email'],
                            $dados['nome']
                        );

                        $anexos = $_FILES['file'] ?? null;

                        if ($anexos && is_array($anexos['tmp_name'])) {
                            foreach ($anexos['tmp_name'] as $indice => $tmpName) {
                                if ($anexos['error'][$indice] === UPLOAD_ERR_OK) {
                                    $email->anexar($tmpName, $anexos['name'][$indice]);
                                }
                            }
                        } elseif ($anexos && $anexos['error'] === UPLOAD_ERR_OK) {
                            // Caso seja apenas um arquivo
                            $email->anexar($anexos['tmp_name'], $anexos['name']);
                        }

                        $email->enviar(EMAIL_REMETENTE['email'], EMAIL_REMETENTE['nome']);

                        $this->mensagem->sucesso('E-mail enviado com sucesso')->flash();
                        Helpers::redirecionar('/compliance');
                    } catch (\PHPMailer\PHPMailer\Exception $ex) {
                        $this->mensagem->alerta($ex->getMessage())->flash();
                    }
                } else {
                    // Falha na verificação do reCAPTCHA
                    $this->mensagem->alerta('Falha na verificação do reCAPTCHA')->flash();
                    Helpers::redirecionar('/compliance');
                }
            }
        }

        echo $this->template->renderizar('compliance.html', [
            'categorias' => $this->categorias(),
        ]);
    }
}
