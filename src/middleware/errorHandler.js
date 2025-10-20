const logger = require('../utils/logger');

// Global error handler middleware
const errorHandler = (err, req, res, next) => {
  let error = { ...err };
  error.message = err.message;

  // Log error
  logger.error('Error occurred', {
    error: err.message,
    stack: err.stack,
    url: req.originalUrl,
    method: req.method,
    ip: req.ip,
    userId: req.user?.userId
  });

  // Mongoose bad ObjectId
  if (err.name === 'CastError') {
    const message = 'Resource not found';
    error = { message, statusCode: 404 };
  }

  // Mongoose duplicate key
  if (err.code === 11000) {
    const message = 'Duplicate field value entered';
    error = { message, statusCode: 400 };
  }

  // Mongoose validation error
  if (err.name === 'ValidationError') {
    const message = Object.values(err.errors).map(val => val.message).join(', ');
    error = { message, statusCode: 400 };
  }

  // Prisma errors
  if (err.code === 'P2002') {
    const message = 'Duplicate field value entered';
    error = { message, statusCode: 400 };
  }

  if (err.code === 'P2025') {
    const message = 'Record not found';
    error = { message, statusCode: 404 };
  }

  if (err.code === 'P2003') {
    const message = 'Foreign key constraint failed';
    error = { message, statusCode: 400 };
  }

  // JWT errors
  if (err.name === 'JsonWebTokenError') {
    const message = 'Invalid token';
    error = { message, statusCode: 401 };
  }

  if (err.name === 'TokenExpiredError') {
    const message = 'Token expired';
    error = { message, statusCode: 401 };
  }

  // Default error response
  const statusCode = error.statusCode || 500;
  const message = error.message || 'Internal Server Error';

  res.status(statusCode).json({
    success: false,
    error: message,
    ...(process.env.NODE_ENV === 'development' && { stack: err.stack })
  });
};

// Async error handler wrapper
const asyncHandler = (fn) => (req, res, next) => {
  Promise.resolve(fn(req, res, next)).catch(next);
};

// 404 handler
const notFound = (req, res, next) => {
  const error = new Error(`Not Found - ${req.originalUrl}`);
  error.statusCode = 404;
  next(error);
};

// Validation error handler
const handleValidationError = (errors) => {
  const formattedErrors = errors.map(error => ({
    field: error.path || error.param,
    message: error.msg || error.message,
    value: error.value
  }));

  return {
    message: 'Validation failed',
    errors: formattedErrors
  };
};

// Database error handler
const handleDatabaseError = (error) => {
  if (error.code === 'P2002') {
    return {
      message: 'Duplicate entry',
      field: error.meta?.target?.[0] || 'unknown'
    };
  }

  if (error.code === 'P2025') {
    return {
      message: 'Record not found'
    };
  }

  if (error.code === 'P2003') {
    return {
      message: 'Referenced record not found'
    };
  }

  return {
    message: 'Database error occurred'
  };
};

// File upload error handler
const handleFileUploadError = (error) => {
  if (error.code === 'LIMIT_FILE_SIZE') {
    return {
      message: 'File too large',
      maxSize: process.env.MAX_FILE_SIZE || '10MB'
    };
  }

  if (error.code === 'LIMIT_FILE_COUNT') {
    return {
      message: 'Too many files'
    };
  }

  if (error.code === 'LIMIT_UNEXPECTED_FILE') {
    return {
      message: 'Unexpected file field'
    };
  }

  return {
    message: 'File upload error'
  };
};

module.exports = {
  errorHandler,
  asyncHandler,
  notFound,
  handleValidationError,
  handleDatabaseError,
  handleFileUploadError
};
