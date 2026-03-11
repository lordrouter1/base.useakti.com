<?php
/**
 * Validator — Akti
 *
 * Classe de validação encadeável que acumula erros.
 * Mensagens em português do Brasil.
 *
 * Uso:
 *   $v = new Validator();
 *   $v->required('name', $name, 'Nome')
 *     ->maxLength('name', $name, 191, 'Nome')
 *     ->required('email', $email, 'E-mail')
 *     ->email('email', $email, 'E-mail');
 *
 *   if ($v->fails()) {
 *       $_SESSION['errors'] = $v->errors();
 *       header('Location: ...');
 *       exit;
 *   }
 *
 * Compatível com PHP 7.4+
 *
 * @see PROJECT_RULES.md — Módulo: Sanitização e Validação
 */

namespace Akti\Utils;

class Validator
{
    /** @var array<string, string> campo => mensagem */
    private $errors = [];

    // ──────────────────────────────────────────────
    // Estado
    // ──────────────────────────────────────────────

    /**
     * Verifica se existem erros acumulados.
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Retorna verdadeiro se não houver erros.
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Retorna todos os erros: ['campo' => 'mensagem', ...]
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Retorna o primeiro erro de um campo específico (ou null).
     */
    public function error(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }

    /**
     * Adiciona um erro manualmente.
     */
    public function addError(string $field, string $message): self
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    /**
     * Limpa todos os erros acumulados.
     */
    public function reset(): self
    {
        $this->errors = [];
        return $this;
    }

    // ──────────────────────────────────────────────
    // Regras de validação (encadeáveis)
    // ──────────────────────────────────────────────

    /**
     * Campo obrigatório (não vazio).
     */
    public function required(string $field, $value, string $label = ''): self
    {
        if (isset($this->errors[$field])) return $this;
        $label = $label ?: $field;

        if ($value === null || $value === '' || $value === false || (is_array($value) && empty($value))) {
            $this->errors[$field] = "O campo {$label} é obrigatório.";
        }
        return $this;
    }

    /**
     * Comprimento mínimo.
     */
    public function minLength(string $field, $value, int $min, string $label = ''): self
    {
        if (isset($this->errors[$field])) return $this;
        $label = $label ?: $field;

        if ($value !== null && $value !== '' && mb_strlen((string) $value, 'UTF-8') < $min) {
            $this->errors[$field] = "O campo {$label} deve ter no mínimo {$min} caracteres.";
        }
        return $this;
    }

    /**
     * Comprimento máximo.
     */
    public function maxLength(string $field, $value, int $max, string $label = ''): self
    {
        if (isset($this->errors[$field])) return $this;
        $label = $label ?: $field;

        if ($value !== null && $value !== '' && mb_strlen((string) $value, 'UTF-8') > $max) {
            $this->errors[$field] = "O campo {$label} deve ter no máximo {$max} caracteres.";
        }
        return $this;
    }

    /**
     * E-mail válido.
     */
    public function email(string $field, $value, string $label = ''): self
    {
        if (isset($this->errors[$field])) return $this;
        $label = $label ?: $field;

        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "O {$label} informado é inválido.";
        }
        return $this;
    }

    /**
     * Valor deve ser inteiro válido.
     */
    public function integer(string $field, $value, string $label = ''): self
    {
        if (isset($this->errors[$field])) return $this;
        $label = $label ?: $field;

        if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->errors[$field] = "O campo {$label} deve ser um número inteiro.";
        }
        return $this;
    }

    /**
     * Valor deve ser numérico.
     */
    public function numeric(string $field, $value, string $label = ''): self
    {
        if (isset($this->errors[$field])) return $this;
        $label = $label ?: $field;

        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->errors[$field] = "O campo {$label} deve ser um valor numérico.";
        }
        return $this;
    }

    /**
     * Valor mínimo (numérico).
     */
    public function min(string $field, $value, float $min, string $label = ''): self
    {
        if (isset($this->errors[$field])) return $this;
        $label = $label ?: $field;

        if ($value !== null && $value !== '' && is_numeric($value) && (float) $value < $min) {
            $this->errors[$field] = "O campo {$label} deve ser no mínimo {$min}.";
        }
        return $this;
    }

    /**
     * Valor máximo (numérico).
     */
    public function max(string $field, $value, float $max, string $label = ''): self
    {
        if (isset($this->errors[$field])) return $this;
        $label = $label ?: $field;

        if ($value !== null && $value !== '' && is_numeric($value) && (float) $value > $max) {
            $this->errors[$field] = "O campo {$label} deve ser no máximo {$max}.";
        }
        return $this;
    }

    /**
     * Valor deve estar na lista de opções.
     */
    public function inList(string $field, $value, array $allowed, string $label = ''): self
    {
        if (isset($this->errors[$field])) return $this;
        $label = $label ?: $field;

        if ($value !== null && $value !== '' && !in_array((string) $value, $allowed, true)) {
            $this->errors[$field] = "O valor selecionado para {$label} é inválido.";
        }
        return $this;
    }

    /**
     * Valor deve ser data válida (Y-m-d).
     */
    public function date(string $field, $value, string $label = ''): self
    {
        if (isset($this->errors[$field])) return $this;
        $label = $label ?: $field;

        if ($value !== null && $value !== '') {
            $dt = \DateTime::createFromFormat('Y-m-d', (string) $value);
            if (!$dt || $dt->format('Y-m-d') !== (string) $value) {
                $this->errors[$field] = "O campo {$label} deve ser uma data válida (AAAA-MM-DD).";
            }
        }
        return $this;
    }

    /**
     * URL válida.
     */
    public function url(string $field, $value, string $label = ''): self
    {
        if (isset($this->errors[$field])) return $this;
        $label = $label ?: $field;

        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field] = "O campo {$label} deve ser uma URL válida.";
        }
        return $this;
    }

    /**
     * Validação por regex customizada.
     */
    public function regex(string $field, $value, string $pattern, string $label = '', string $message = ''): self
    {
        if (isset($this->errors[$field])) return $this;
        $label = $label ?: $field;

        if ($value !== null && $value !== '' && !preg_match($pattern, (string) $value)) {
            $this->errors[$field] = $message ?: "O campo {$label} possui formato inválido.";
        }
        return $this;
    }

    // ──────────────────────────────────────────────
    // Validações específicas do Brasil
    // ──────────────────────────────────────────────

    /**
     * CPF válido (com dígito verificador).
     */
    public function cpf(string $field, $value, string $label = ''): self
    {
        if (isset($this->errors[$field])) return $this;
        $label = $label ?: $field;

        if ($value !== null && $value !== '') {
            $cpf = preg_replace('/\D/', '', (string) $value);
            if (!self::isValidCpf($cpf)) {
                $this->errors[$field] = "O {$label} informado é inválido.";
            }
        }
        return $this;
    }

    /**
     * CNPJ válido (com dígito verificador).
     */
    public function cnpj(string $field, $value, string $label = ''): self
    {
        if (isset($this->errors[$field])) return $this;
        $label = $label ?: $field;

        if ($value !== null && $value !== '') {
            $cnpj = preg_replace('/\D/', '', (string) $value);
            if (!self::isValidCnpj($cnpj)) {
                $this->errors[$field] = "O {$label} informado é inválido.";
            }
        }
        return $this;
    }

    /**
     * CPF ou CNPJ válido (detecta pelo tamanho).
     */
    public function cpfOrCnpj(string $field, $value, string $label = ''): self
    {
        if (isset($this->errors[$field])) return $this;
        $label = $label ?: $field;

        if ($value !== null && $value !== '') {
            $digits = preg_replace('/\D/', '', (string) $value);
            if (strlen($digits) === 11) {
                return $this->cpf($field, $value, $label);
            } elseif (strlen($digits) === 14) {
                return $this->cnpj($field, $value, $label);
            } else {
                $this->errors[$field] = "O {$label} deve ser um CPF (11 dígitos) ou CNPJ (14 dígitos) válido.";
            }
        }
        return $this;
    }

    // ──────────────────────────────────────────────
    // Helpers estáticos de validação
    // ──────────────────────────────────────────────

    /**
     * Valida CPF com verificação de dígitos.
     */
    public static function isValidCpf(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        if (strlen($cpf) !== 11) {
            return false;
        }

        // Rejeita sequências iguais (ex: 111.111.111-11)
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        // Primeiro dígito
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $cpf[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : (11 - $remainder);
        if ((int) $cpf[9] !== $digit1) {
            return false;
        }

        // Segundo dígito
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int) $cpf[$i] * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : (11 - $remainder);
        return (int) $cpf[10] === $digit2;
    }

    /**
     * Valida CNPJ com verificação de dígitos.
     */
    public static function isValidCnpj(string $cnpj): bool
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        if (strlen($cnpj) !== 14) {
            return false;
        }

        // Rejeita sequências iguais
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        // Primeiro dígito
        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $cnpj[$i] * $weights1[$i];
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : (11 - $remainder);
        if ((int) $cnpj[12] !== $digit1) {
            return false;
        }

        // Segundo dígito
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += (int) $cnpj[$i] * $weights2[$i];
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : (11 - $remainder);
        return (int) $cnpj[13] === $digit2;
    }
}
