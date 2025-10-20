const express = require('express');
const { 
  categoryValidation, 
  categoryIdValidation,
  contestIdParamValidation 
} = require('../middleware/validation');
const { 
  authenticateToken, 
  requireOrganizer 
} = require('../middleware/auth');
const {
  getCategories,
  getCategory,
  createCategory,
  updateCategory,
  deleteCategory,
  addContestant,
  removeContestant,
  addJudge,
  removeJudge
} = require('../controllers/categoryController');

const router = express.Router();

// All routes require authentication
router.use(authenticateToken);

// Public routes (authenticated users)
router.get('/contest/:contestId', contestIdParamValidation, getCategories);
router.get('/:id', categoryIdValidation, getCategory);

// Organizer-only routes
router.use(requireOrganizer);

router.post('/contest/:contestId', contestIdParamValidation, categoryValidation, createCategory);
router.put('/:id', categoryIdValidation, categoryValidation, updateCategory);
router.delete('/:id', categoryIdValidation, deleteCategory);

// Contestant management
router.post('/:id/contestants', categoryIdValidation, addContestant);
router.delete('/:id/contestants/:contestantId', categoryIdValidation, removeContestant);

// Judge management
router.post('/:id/judges', categoryIdValidation, addJudge);
router.delete('/:id/judges/:judgeId', categoryIdValidation, removeJudge);

module.exports = router;
