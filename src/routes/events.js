const express = require('express');
const { 
  eventValidation, 
  eventIdValidation 
} = require('../middleware/validation');
const { 
  authenticateToken, 
  requireOrganizer 
} = require('../middleware/auth');
const {
  getEvents,
  getEvent,
  createEvent,
  updateEvent,
  deleteEvent,
  archiveEvent,
  restoreEvent
} = require('../controllers/eventController');

const router = express.Router();

// All routes require authentication
router.use(authenticateToken);

// Public routes (authenticated users)
router.get('/', getEvents);
router.get('/:id', eventIdValidation, getEvent);

// Organizer-only routes
router.use(requireOrganizer);

router.post('/', eventValidation, createEvent);
router.put('/:id', eventIdValidation, eventValidation, updateEvent);
router.delete('/:id', eventIdValidation, deleteEvent);
router.post('/:id/archive', eventIdValidation, archiveEvent);
router.post('/:id/restore', eventIdValidation, restoreEvent);

module.exports = router;
