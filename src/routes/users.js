const express = require('express');
const { 
  userValidation, 
  userIdValidation,
  paginationValidation 
} = require('../middleware/validation');
const { 
  authenticateToken, 
  requireOrganizer 
} = require('../middleware/auth');
const {
  getUsers,
  getUser,
  createUser,
  updateUser,
  deleteUser,
  getContestants,
  getJudges
} = require('../controllers/userController');

const router = express.Router();

// All routes require authentication
router.use(authenticateToken);

// Public routes (authenticated users)
router.get('/', paginationValidation, getUsers);
router.get('/contestants', paginationValidation, getContestants);
router.get('/judges', paginationValidation, getJudges);
router.get('/:id', userIdValidation, getUser);

// Organizer-only routes
router.use(requireOrganizer);

router.post('/', userValidation, createUser);
router.put('/:id', userIdValidation, userValidation, updateUser);
router.delete('/:id', userIdValidation, deleteUser);

module.exports = router;
