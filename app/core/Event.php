<?php
namespace Akti\Core;

/**
 * Event — Value Object para dados de eventos do sistema.
 *
 * Cada evento carrega:
 * - Nome do evento (convenção: camada.entidade.acao)
 * - Dados arbitrários (array associativo)
 * - Timestamp automático
 * - ID do usuário da sessão (se autenticado)
 * - Nome do banco do tenant (se resolvido)
 *
 * Compatível com PHP 7.4+
 *
 * @package Akti\Core
 * @see     EventDispatcher
 * @see     PROJECT_RULES.md — Sistema de Eventos
 */
class Event
{
    /** @var string Nome do evento (ex: model.order.created) */
    public $name;

    /** @var array Dados do evento */
    public $data;

    /** @var int Timestamp Unix de criação */
    public $timestamp;

    /** @var int|null ID do usuário autenticado */
    public $userId;

    /** @var string|null Nome do banco do tenant */
    public $tenantDb;

    /**
     * @param string $name Nome do evento
     * @param array  $data Dados do evento
     */
    public function __construct(string $name, array $data = [])
    {
        $this->name      = $name;
        $this->data      = $data;
        $this->timestamp = time();
        $this->userId    = $_SESSION['user_id'] ?? null;
        $this->tenantDb  = $_SESSION['db_name'] ?? null;
    }

    /**
     * Retorna os dados do evento.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}
