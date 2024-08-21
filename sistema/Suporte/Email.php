<?php

namespace sistema\Suporte;

use PHPMailer\PHPMailer\PHPMailer;

final class Email
{
    private PHPMailer $mail;
    private array $anexos;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
        $this->mail->isSMTP();
        $this->mail->Host = EMAIL_HOST;
        $this->mail->SMTPAuth = true;
        $this->mail->Username = EMAIL_USUARIO;
        $this->mail->Password = EMAIL_SENHA;
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $this->mail->Port = EMAIL_PORTA;
        $this->mail->setLanguage('pt_br');
        $this->mail->CharSet = 'utf-8';
        $this->mail->isHTML(true);
        $this->anexos = [];
    }

    public function criar(
        string $assunto,
        string $conteudo,
        string $destinatarioEmail,
        string $destinatarioNome,
        ?string $responderEmail = null,
        ?string $responderNome = null
    ): static
    {
        $conteudo .= $this->rodape();
        $this->mail->Subject = $assunto;
        $this->mail->Body = $conteudo;
        $this->mail->isHTML(true);
        $this->mail->addAddress($destinatarioEmail, $destinatarioNome);
        if ($responderEmail !== null && $responderNome !== null) {
            $this->mail->addReplyTo($responderEmail, $responderNome);
        }
        return $this;
    }

    public function enviar(string $remetenteEmail, string $remetenteNome, string $copiaEmail = null, string $copiaNome = null): bool
    {
        try {
            $this->mail->setFrom($remetenteEmail, $remetenteNome);
            if ($copiaEmail !== null && $copiaNome !== null) {
                $this->mail->addCC($copiaEmail, $copiaNome);
            }
            foreach ($this->anexos as $anexo) {
                $this->mail->addAttachment($anexo['caminho'], $anexo['nome']);
            }
            $this->mail->send();
            return true;
        } catch (\Exception $ex) {
            $ex = $this->mail->ErrorInfo;
            return false;
        }
    }

    public function anexar(string $caminho, ?string $nome = null): static
    {
        $nome = $nome ?? basename($caminho);
        $this->anexos[] = [
            'caminho' => $caminho,
            'nome' => $nome
        ];
        return $this;
    }

    private function rodape(): string
    {
        return "<hr><small>Enviado por " . SITE_NOME . " em: " . date('d/m/Y') . " às " . date('H:i') . "</small>";
    }
}
