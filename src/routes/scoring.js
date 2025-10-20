const express = require('express');
const { 
  submitScoresValidation,
  certifyScoresValidation,
  certifyTotalsValidation,
  finalCertificationValidation,
  categoryIdValidation 
} = require('../middleware/validation');
const { 
  authenticateToken, 
  requireJudge,
  requireTallyMaster,
  requireAuditor
} = require('../middleware/auth');
const {
  submitScores,
  getScores,
  certifyScores,
  certifyTotals,
  performFinalCertification,
  getCertificationStatus
} = require('../controllers/scoringController');

const router = express.Router();

// All routes require authentication
router.use(authenticateToken);

// Public routes (authenticated users)
router.get('/category/:categoryId/contestant/:contestantId', getScores);
router.get('/category/:categoryId/certification-status', categoryIdValidation, getCertificationStatus);

// Judge-only routes
router.post('/category/:categoryId/contestant/:contestantId', submitScoresValidation, submitScores);
router.post('/category/:categoryId/certify', certifyScoresValidation, certifyScores);

// Tally Master-only routes
router.post('/category/:categoryId/certify-totals', certifyTotalsValidation, certifyTotals);

// Auditor-only routes
router.post('/category/:categoryId/final-certification', finalCertificationValidation, performFinalCertification);

module.exports = router;
