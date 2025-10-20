const express = require('express');
const { 
  contestValidation, 
  contestIdValidation,
  eventIdParamValidation 
} = require('../middleware/validation');
const { 
  authenticateToken, 
  requireOrganizer 
} = require('../middleware/auth');
const {
  getContests,
  getContest,
  createContest,
  updateContest,
  deleteContest,
  addContestant,
  removeContestant,
  addJudge,
  removeJudge
} = require('../controllers/contestController');

const router = express.Router();

// All routes require authentication
router.use(authenticateToken);

// Public routes (authenticated users)
router.get('/event/:eventId', eventIdParamValidation, getContests);
router.get('/:id', contestIdValidation, getContest);

// Organizer-only routes
router.use(requireOrganizer);

router.post('/event/:eventId', eventIdParamValidation, contestValidation, createContest);
router.put('/:id', contestIdValidation, contestValidation, updateContest);
router.delete('/:id', contestIdValidation, deleteContest);

// Contestant management
router.post('/:id/contestants', contestIdValidation, addContestant);
router.delete('/:id/contestants/:contestantId', contestIdValidation, removeContestant);

// Judge management
router.post('/:id/judges', contestIdValidation, addJudge);
router.delete('/:id/judges/:judgeId', contestIdValidation, removeJudge);

module.exports = router;
