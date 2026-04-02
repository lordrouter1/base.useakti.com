import { BaseService } from './BaseService.js';
import { Op } from 'sequelize';

/**
 * CustomerService — lógica de negócio para clientes.
 * FEAT-012: Expansão da API REST
 */
export class CustomerService extends BaseService {
    constructor(Customer) {
        super(Customer);
    }

    /**
     * Busca clientes por nome, email ou telefone.
     */
    async search(q, { limit = 10 } = {}) {
        if (!q) return [];
        return this.model.findAll({
            where: {
                [Op.or]: [
                    { name: { [Op.like]: `%${q}%` } },
                    { email: { [Op.like]: `%${q}%` } },
                    { phone: { [Op.like]: `%${q}%` } },
                ],
            },
            limit,
            order: [['name', 'ASC']],
        });
    }
}
