import swaggerJSDoc from 'swagger-jsdoc';

/**
 * Swagger/OpenAPI configuration for Akti REST API.
 *
 * Scans JSDoc annotations in route files and generates an OpenAPI 3.0 spec.
 * Served via swagger-ui-express at /api/docs.
 *
 * @see https://swagger.io/specification/
 */
const options = {
  definition: {
    openapi: '3.0.0',
    info: {
      title: 'Akti API',
      version: '1.0.0',
      description: 'API REST do sistema Akti — Gestão em Produção',
    },
    servers: [
      {
        url: '/api/v1',
        description: 'API v1 (autenticada)',
      },
    ],
    components: {
      securitySchemes: {
        bearerAuth: {
          type: 'http',
          scheme: 'bearer',
          bearerFormat: 'JWT',
        },
      },
    },
    security: [{ bearerAuth: [] }],
  },
  apis: ['./src/routes/*.js'],
};

const swaggerSpec = swaggerJSDoc(options);

export default swaggerSpec;
