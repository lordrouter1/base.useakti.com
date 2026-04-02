import { BaseController } from './BaseController.js';
import { FinancialService } from '../services/FinancialService.js';

/**
 * FinancialController — API REST para módulo financeiro.
 * FEAT-012: Expansão da API REST
 */
export class FinancialController extends BaseController {
    getService(req) {
        const { FinancialTransaction } = req.models;
        return new FinancialService(FinancialTransaction);
    }

    /**
     * GET /summary — resumo financeiro (receitas, despesas, saldo)
     */
    summary = async (req, res, next) => {
        try {
            const service = this.getService(req);
            const startDate = req.query.start_date || null;
            const endDate = req.query.end_date || null;
            const result = await service.getSummary({ startDate, endDate });
            return res.json({ data: result });
        } catch (err) {
            return next(err);
        }
    };
}
