import { HTTP_STATUS } from '../config/constants.js';

const DANGEROUS_KEYS = new Set(['__proto__', 'constructor', 'prototype']);

/**
 * Recursively strips prototype-pollution keys from an object.
 */
function stripDangerousKeys(obj) {
  if (obj === null || typeof obj !== 'object') return obj;
  if (Array.isArray(obj)) return obj.map(stripDangerousKeys);

  const clean = {};
  for (const key of Object.keys(obj)) {
    if (DANGEROUS_KEYS.has(key)) continue;
    clean[key] = stripDangerousKeys(obj[key]);
  }
  return clean;
}

/**
 * Validates that :id param is a positive integer.
 */
export function validateId(req, res, next) {
  const id = Number(req.params.id);
  if (!Number.isInteger(id) || id < 1) {
    return res.status(HTTP_STATUS.BAD_REQUEST).json({ error: 'Invalid ID parameter.' });
  }
  req.params.id = id;
  next();
}

/**
 * Validates that request body is a non-empty object and strips dangerous keys.
 */
export function validateBody(req, res, next) {
  if (!req.body || typeof req.body !== 'object' || Array.isArray(req.body)) {
    return res.status(HTTP_STATUS.BAD_REQUEST).json({ error: 'Request body must be a JSON object.' });
  }
  if (Object.keys(req.body).length === 0) {
    return res.status(HTTP_STATUS.BAD_REQUEST).json({ error: 'Request body must not be empty.' });
  }
  req.body = stripDangerousKeys(req.body);
  next();
}
