const { body, param, query } = require('express-validator');

// Authentication validation rules
const registerValidation = [
  body('name')
    .trim()
    .isLength({ min: 2, max: 100 })
    .withMessage('Name must be between 2 and 100 characters'),
  body('email')
    .isEmail()
    .normalizeEmail()
    .withMessage('Must be a valid email address'),
  body('password')
    .isLength({ min: 8 })
    .withMessage('Password must be at least 8 characters long'),
  body('role')
    .isIn(['ORGANIZER', 'JUDGE', 'CONTESTANT', 'EMCEE', 'TALLY_MASTER', 'AUDITOR', 'BOARD'])
    .withMessage('Invalid role'),
  body('preferredName')
    .optional()
    .trim()
    .isLength({ max: 100 })
    .withMessage('Preferred name must be less than 100 characters'),
  body('gender')
    .optional()
    .isIn(['male', 'female', 'non-binary', 'prefer-not-to-say', 'other'])
    .withMessage('Invalid gender option'),
  body('pronouns')
    .optional()
    .trim()
    .isLength({ max: 50 })
    .withMessage('Pronouns must be less than 50 characters')
];

const loginValidation = [
  body('email')
    .isEmail()
    .normalizeEmail()
    .withMessage('Must be a valid email address'),
  body('password')
    .notEmpty()
    .withMessage('Password is required')
];

const changePasswordValidation = [
  body('currentPassword')
    .notEmpty()
    .withMessage('Current password is required'),
  body('newPassword')
    .isLength({ min: 8 })
    .withMessage('New password must be at least 8 characters long')
];

// Event validation rules
const eventValidation = [
  body('name')
    .trim()
    .isLength({ min: 2, max: 200 })
    .withMessage('Event name must be between 2 and 200 characters'),
  body('startDate')
    .isISO8601()
    .withMessage('Start date must be a valid date'),
  body('endDate')
    .isISO8601()
    .withMessage('End date must be a valid date')
    .custom((value, { req }) => {
      if (new Date(value) <= new Date(req.body.startDate)) {
        throw new Error('End date must be after start date');
      }
      return true;
    })
];

const eventIdValidation = [
  param('id')
    .isUUID()
    .withMessage('Invalid event ID')
];

// Contest validation rules
const contestValidation = [
  body('name')
    .trim()
    .isLength({ min: 2, max: 200 })
    .withMessage('Contest name must be between 2 and 200 characters'),
  body('description')
    .optional()
    .trim()
    .isLength({ max: 1000 })
    .withMessage('Description must be less than 1000 characters')
];

const contestIdValidation = [
  param('id')
    .isUUID()
    .withMessage('Invalid contest ID')
];

const eventIdParamValidation = [
  param('eventId')
    .isUUID()
    .withMessage('Invalid event ID')
];

// Category validation rules
const categoryValidation = [
  body('name')
    .trim()
    .isLength({ min: 2, max: 200 })
    .withMessage('Category name must be between 2 and 200 characters'),
  body('description')
    .optional()
    .trim()
    .isLength({ max: 1000 })
    .withMessage('Description must be less than 1000 characters'),
  body('scoreCap')
    .optional()
    .isFloat({ min: 0, max: 1000 })
    .withMessage('Score cap must be between 0 and 1000')
];

const categoryIdValidation = [
  param('id')
    .isUUID()
    .withMessage('Invalid category ID')
];

const contestIdParamValidation = [
  param('contestId')
    .isUUID()
    .withMessage('Invalid contest ID')
];

// User validation rules
const userValidation = [
  body('name')
    .trim()
    .isLength({ min: 2, max: 100 })
    .withMessage('Name must be between 2 and 100 characters'),
  body('email')
    .isEmail()
    .normalizeEmail()
    .withMessage('Must be a valid email address'),
  body('role')
    .isIn(['ORGANIZER', 'JUDGE', 'CONTESTANT', 'EMCEE', 'TALLY_MASTER', 'AUDITOR', 'BOARD'])
    .withMessage('Invalid role'),
  body('preferredName')
    .optional()
    .trim()
    .isLength({ max: 100 })
    .withMessage('Preferred name must be less than 100 characters'),
  body('gender')
    .optional()
    .isIn(['male', 'female', 'non-binary', 'prefer-not-to-say', 'other'])
    .withMessage('Invalid gender option'),
  body('pronouns')
    .optional()
    .trim()
    .isLength({ max: 50 })
    .withMessage('Pronouns must be less than 50 characters')
];

const userIdValidation = [
  param('id')
    .isUUID()
    .withMessage('Invalid user ID')
];

// Scoring validation rules
const submitScoresValidation = [
  param('categoryId')
    .isUUID()
    .withMessage('Invalid category ID'),
  param('contestantId')
    .isUUID()
    .withMessage('Invalid contestant ID'),
  body('scores')
    .isObject()
    .withMessage('Scores must be an object'),
  body('comment')
    .optional()
    .trim()
    .isLength({ max: 1000 })
    .withMessage('Comment must be less than 1000 characters')
];

const certifyScoresValidation = [
  param('categoryId')
    .isUUID()
    .withMessage('Invalid category ID'),
  body('signatureName')
    .trim()
    .isLength({ min: 2, max: 100 })
    .withMessage('Signature name must be between 2 and 100 characters')
];

const certifyTotalsValidation = [
  param('categoryId')
    .isUUID()
    .withMessage('Invalid category ID'),
  body('signatureName')
    .trim()
    .isLength({ min: 2, max: 100 })
    .withMessage('Signature name must be between 2 and 100 characters')
];

const finalCertificationValidation = [
  param('categoryId')
    .isUUID()
    .withMessage('Invalid category ID'),
  body('signatureName')
    .trim()
    .isLength({ min: 2, max: 100 })
    .withMessage('Signature name must be between 2 and 100 characters')
];

// Admin validation rules
const updateSystemSettingsValidation = [
  body('settings')
    .isObject()
    .withMessage('Settings must be an object')
];

// Pagination validation
const paginationValidation = [
  query('page')
    .optional()
    .isInt({ min: 1 })
    .withMessage('Page must be a positive integer'),
  query('limit')
    .optional()
    .isInt({ min: 1, max: 100 })
    .withMessage('Limit must be between 1 and 100'),
  query('search')
    .optional()
    .trim()
    .isLength({ max: 100 })
    .withMessage('Search term must be less than 100 characters')
];

// File upload validation
const fileUploadValidation = [
  body('title')
    .optional()
    .trim()
    .isLength({ max: 200 })
    .withMessage('Title must be less than 200 characters'),
  body('description')
    .optional()
    .trim()
    .isLength({ max: 1000 })
    .withMessage('Description must be less than 1000 characters')
];

module.exports = {
  // Authentication
  registerValidation,
  loginValidation,
  changePasswordValidation,
  
  // Events
  eventValidation,
  eventIdValidation,
  
  // Contests
  contestValidation,
  contestIdValidation,
  eventIdParamValidation,
  
  // Categories
  categoryValidation,
  categoryIdValidation,
  contestIdParamValidation,
  
  // Users
  userValidation,
  userIdValidation,
  
  // Scoring
  submitScoresValidation,
  certifyScoresValidation,
  certifyTotalsValidation,
  finalCertificationValidation,
  
  // Admin
  updateSystemSettingsValidation,
  
  // Common
  paginationValidation,
  fileUploadValidation
};
