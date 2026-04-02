import { BaseService } from './BaseService.js';
import { Op, fn, col, literal } from 'sequelize';

/**
 * FinancialService — lógica de negócio para módulo financeiro.
 * FEAT-012: Expansão da API REST
 */
export class FinancialService extends BaseService {
    constructor(FinancialTransaction) {
        super(FinancialTransaction);
    }

    /**
     * Resumo financeiro com receitas, despesas e saldo.
     */
    async getSummary({ startDate, endDate } = {}) {
        const where = {};
        if (startDate && endDate) {
            where.date = { [Op.between]: [startDate, endDate] };
        } else if (startDate) {
            where.date = { [Op.gte]: startDate };
        } else if (endDate) {
            where.date = { [Op.lte]: endDate };
        }

        const income = await this.model.sum('amount', {
            where: { ...where, type: 'income' },
        }) || 0;

        const expense = await this.model.sum('amount', {
            where: { ...where, type: 'expense' },
        }) || 0;

        return {
            income,
            expense,
            balance: income - expense,
        };
    }
}
