import { BaseService } from './BaseService.js';
import { Op } from 'sequelize';

/**
 * OrderService — lógica de negócio para pedidos.
 * FEAT-012: Expansão da API REST
 */
export class OrderService extends BaseService {
    constructor(Order, OrderItem) {
        super(Order);
        this.OrderItem = OrderItem;
    }

    /**
     * Busca pedidos por número ou nome do cliente.
     */
    async search(q, { limit = 10 } = {}) {
        if (!q) return [];
        return this.model.findAll({
            where: {
                [Op.or]: [
                    { order_number: { [Op.like]: `%${q}%` } },
                    { customer_name: { [Op.like]: `%${q}%` } },
                ],
            },
            limit,
            order: [['created_at', 'DESC']],
        });
    }

    /**
     * Retorna itens de um pedido.
     */
    async findItems(orderId) {
        return this.OrderItem.findAll({
            where: { order_id: orderId },
            order: [['id', 'ASC']],
        });
    }
}
