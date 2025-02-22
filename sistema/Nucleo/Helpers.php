<?php

namespace sistema\Nucleo;

use DateTime;
use Exception;
use sistema\Nucleo\Sessao;

/**
 * Classe Helper - Classe auxiliar responsável por prover métodos estáticos para manipular e validar dados no sistema.
 *
 * @author Jose Tadeu <ceo@unset.com.br>
 * @copyright 2022 UnSet
 */
class Helpers
{


    public static function gerarToken(int $tamanho = 16): string
    {
        return bin2hex(random_bytes($tamanho));
    }

    public static function generateCsrfToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = '6LcuPQEqAAAAAN3du9dyTgaK2tGpTifd2TnYa36X';
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Cria retorno json
     * @param string $chave
     * @param string $valor
     * @return void
     */
    public static function json(string $chave, string $valor): void
    {
        header('Content-Type: application/json');

        $json[$chave] = $valor;
        echo json_encode($json);

        exit();
    }

    /**
     * Valida a senha
     * @param string $senha
     * @return bool
     */
    public static function validarSenha(string $senha): bool
    {
        if (mb_strlen($senha) >= 6 && mb_strlen($senha) <= 50) {
            return true;
        }

        return false;
    }

    /**
     * Gera senha segura
     * @param string $senha
     * @return string
     */
    public static function gerarSenha(string $senha): string
    {
        return password_hash($senha, PASSWORD_DEFAULT, ['cost' => 10]);
    }

    /**
     * Verifica a senha
     * @param string $senha
     * @param string $hash
     * @return bool
     */
    public static function verificarSenha(string $senha, string $hash): bool
    {
        return password_verify($senha, $hash);
    }

    /**
     * Instancia e retorna as mensagens flash por sessão
     * @return string|null
     */
    public static function flash(): ?string
    {
        $sessao = new Sessao();

        $flash = $sessao->flash();

        if ($flash) {
            echo $flash;
        }
        return null;
    }

    /**
     * Redireciona para a url informada
     * @param string $url
     * @return void
     */
    public static function redirecionar(string $url = null): void
    {
        header('HTTP/1.1 302 Found');

        $local = ($url ? self::url($url) : self::url());

        header("Location: {$local} ");
        exit();
    }

    /**
     * Válida um número de CPF
     * @param string $cpf
     * @return bool
     */
    public static function validarCpf(string $cpf): bool
    {
        $cpf = self::limparNumero($cpf);

        if (mb_strlen($cpf) != 11 or preg_match('/(\d)\1{10}/', $cpf)) {
            throw new Exception('O CPF precisa ter 11 digitos');
        }
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                throw new Exception('CPF Inválido');
            }
        }
        return true;
    }

    /**
     * Limpa todos os caracteres não numéricos
     * @param string $numero
     * @return string
     */
    public static function limparNumero(string $numero): string
    {
        return preg_replace('/[^0-9]/', '', $numero);
    }

    /**
     * Gera url amigável
     * @param string $string
     * @return string slug
     */
    public static function slug(string $string): string
    {
        // Transliteração de caracteres especiais
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);

        // Substituição de caracteres especiais por seus equivalentes
        $slug = preg_replace('/[^a-zA-Z0-9\s-]/', '', $slug);

        // Remoção de espaços extras e substituição por hífens
        $slug = preg_replace('/[\s-]+/', ' ', $slug);
        $slug = preg_replace('/\s/', '-', $slug);

        // Conversão para minúsculas
        return strtolower(trim($slug, '-'));
    }


    /**
     * Data atual formatada 
     * @return string
     */
    public static function dataAtual(): string
    {
        $diaMes = date('d');
        $diaSemana = date('w');
        $mes = date('n') - 1;
        $ano = date('Y');

        $nomesDiasDaSemana = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sabádo'];

        $nomesDosMeses = [
            'janeiro',
            'fevereiro',
            'março',
            'abril',
            'maio',
            'junho',
            'julho',
            'agosto',
            'setembro',
            'outubro',
            'novembro',
            'dezembro'
        ];

        $dataFormatada = $nomesDiasDaSemana[$diaSemana] . ', ' . $diaMes . ' de ' . $nomesDosMeses[$mes] . ' de ' . $ano;

        return $dataFormatada;
    }

    /**
     * Monta url de acordo com o ambiente
     * @param string $url parte da url ex. admin
     * @return string url completa
     */
    public static function url(string $url = null): string
    {
        $servidor = filter_input(INPUT_SERVER, 'SERVER_NAME');
        $ambiente = ($servidor == 'localhost' ? URL_DESENVOLVIMENTO : URL_PRODUCAO);

        if (!empty($url)) {
            if (str_starts_with($url, '/')) {
                return $ambiente . $url;
            }
        }
        return $ambiente . '/' . $url;
    }


    function sanitizeSlug($slug)
    {
        $slug = strtolower($slug);
        $slug = preg_replace('/[^\w\s-]/', '', $slug); // Remove caracteres especiais
        $slug = preg_replace('/[\s-]+/', '-', $slug);  // Substitui espaços e hifens múltiplos por um único hífen
        return trim($slug, '-');                       // Remove hífens no início e no final
    }
    /**
     * Checa se o servidor é localhost
     * @return bool
     */
    public static function localhost(): bool
    {
        $servidor = filter_input(INPUT_SERVER, 'SERVER_NAME');

        if ($servidor == 'localhost') {
            return true;
        }
        return false;
    }

    /**
     * Valida uma url
     * @param string $url
     * @return bool
     */
    public static function validarUrl(string $url): bool
    {
        if (mb_strlen($url) < 10) {
            return false;
        }
        if (!str_contains($url, '.')) {
            return false;
        }
        if (str_contains($url, 'http://') or str_contains($url, 'https://')) {
            return true;
        }
        return false;
    }

    public static function validarUrlComFiltro(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * Valida um endereço de e-mail
     * @param string $email
     * @return bool
     */
    public static function validarEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Conta o tempo decorrido de uma data
     * @param string $data
     * @return string
     */
    public static function contarTempo(string $data): string
    {
        $agora = strtotime(date('Y-m-d H:i:s'));
        $tempo = strtotime($data);
        $diferenca = $agora - $tempo;

        $segundos = $diferenca;
        $minutos = round($diferenca / 60);
        $horas = round($diferenca / 3600);
        $dias = round($diferenca / 86400);
        $semanas = round($diferenca / 604800);
        $meses = round($diferenca / 2419200);
        $anos = round($diferenca / 29030400);

        if ($segundos <= 60) {
            return 'agora';
        } elseif ($minutos <= 60) {
            return $minutos == 1 ? 'há 1 minuto' : 'há ' . $minutos . ' minutos';
        } elseif ($horas <= 24) {
            return $horas == 1 ? 'há 1 hora' : 'há ' . $horas . ' horas';
        } elseif ($dias <= 7) {
            return $dias == 1 ? 'ontem' : 'há ' . $dias . ' dias';
        } elseif ($semanas <= 4) {
            return $semanas == 1 ? 'há 1 semana' : 'há ' . $semanas . ' semanas';
        } elseif ($meses <= 12) {
            return $meses == 1 ? 'há 1 mês' : 'há ' . $meses . ' meses';
        } else {
            return $anos == 1 ? 'há 1 ano' : 'há ' . $anos . ' anos';
        }
    }

    public static function calcularTempoRestante(string $dataDevolucao): int
    {
        $dataAtual = new DateTime();
        $dataDevolucao = new DateTime($dataDevolucao);
        $intervalo = $dataAtual->diff($dataDevolucao);

        // Retorna a diferença em dias
        return (int)$intervalo->format('%r%a'); // %r%a retorna o número de dias com sinal, positivo ou negativo
    }

    /**
     * Conta o tempo faltante de uma data
     * @param string $data
     * @return string
     */
    public static function calcularTempoDevolucao(string $dataDevolucao): string
    {
        $agora = strtotime(date('Y-m-d H:i:s'));
        $devolucao = strtotime($dataDevolucao);
        $diferenca = $devolucao - $agora;

        if ($diferenca <= 0) {
            return 'A data de devolução já passou';
        }

        $segundos = $diferenca;
        $minutos = round($diferenca / 60);
        $horas = round($diferenca / 3600);
        $dias = round($diferenca / 86400);
        $semanas = round($diferenca / 604800);
        $meses = round($diferenca / 2419200);
        $anos = round($diferenca / 29030400);

        if ($segundos <= 60) {
            return 'instantes';
        } elseif ($minutos <= 60) {
            return $minutos == 1 ? '1 minuto' : '' . $minutos . ' minutos';
        } elseif ($horas <= 24) {
            return $horas == 1 ? '1 hora' : '' . $horas . ' horas';
        } elseif ($dias <= 7) {
            return $dias == 1 ? '1 dia' : '' . $dias . ' dias';
        } elseif ($semanas <= 4) {
            return $semanas == 1 ? '1 semana' : '' . $semanas . ' semanas';
        } elseif ($meses <= 12) {
            return $meses == 1 ? '1 mês' : '' . $meses . ' meses';
        } else {
            return $anos == 1 ? '1 ano' : '' . $anos . ' anos';
        }
    }


    /**
     * Formata um valor com ponto e virgula
     * @param float $valor
     * @return string
     */
    public static function formatarValor(float $valor = null): string
    {
        return number_format(($valor ? $valor : 0), 2, ',', '.');
    }

    /**
     * Formata um número com pontos
     * @param int $numero
     * @return string
     */
    public static function formatarNumero(int $numero = null): string
    {
        return number_format($numero ?: 0, 0, '.', '.');
    }

    /**
     * Saudação de acordo com o horário
     * @return string saudação
     */
    public static function saudacao(): string
    {
        $hora = date('H');

        $saudacao = match (true) {
            $hora >= 0 and $hora <= 5 => 'Boa madrugada',
            $hora >= 6 and $hora <= 12 => 'Bom dia',
            $hora >= 13 and $hora <= 18 => 'Boa tarde',
            default => 'Boa noite'
        };

        return $saudacao;
    }


    public static function formatarDataParaBanco($data)
    {
        // Converte a data do formato "dd/mm/yyyy" para um objeto DateTime
        $dataObjeto = DateTime::createFromFormat('d/m/Y', $data);

        // Formata a data para o formato "yyyy-mm-dd"
        $dataFormatada = $dataObjeto->format('Y-m-d');

        return $dataFormatada;
    }


    public static function formatarDataBrasil(string $data)
    {
        // Converte o formato da data de yyyy-mm-dd para dd/mm/yyyy
        $dataFormatada = date('d/m/Y', strtotime($data));
        return $dataFormatada;
    }
    /**
     * Resume um texto para um limite de caracteres.
     *
     * @param string $texto O texto a ser resumido.
     * @param int $limite O limite de caracteres para o resumo.
     * @param string $continue O texto que será adicionado ao final do resumo (opcional, padrão: '...').
     * @return string O texto resumido.
     */
    public static function resumirTexto(string $texto, int $limite, string $continue = '...'): string
    {
        $textoLimpo = trim(strip_tags($texto));
        if (mb_strlen($textoLimpo) <= $limite) {
            return $textoLimpo;
        }

        $resumirTexto = mb_substr($textoLimpo, 0, mb_strrpos(mb_substr($textoLimpo, 0, $limite), ''));

        return $resumirTexto . $continue;
    }
}
