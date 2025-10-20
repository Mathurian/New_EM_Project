const express = require('express');
const { 
  updateSystemSettingsValidation,
  paginationValidation 
} = require('../middleware/validation');
const { 
  authenticateToken, 
  requireOrganizer 
} = require('../middleware/auth');
const {
  getSystemStats,
  getActivityLogs,
  getSystemSettings,
  updateSystemSettings,
  getDatabaseStats,
  clearCache,
  getActiveUsers,
  exportData
} = require('../controllers/adminController');

const router = express.Router();

// All routes require authentication and organizer role
router.use(authenticateToken);
router.use(requireOrganizer);

// System statistics and monitoring
router.get('/stats', getSystemStats);
router.get('/active-users', getActiveUsers);
router.get('/database-stats', getDatabaseStats);

// Activity logs
router.get('/logs', paginationValidation, getActivityLogs);

// System settings
router.get('/settings', getSystemSettings);
router.put('/settings', updateSystemSettingsValidation, updateSystemSettings);

// System maintenance
router.post('/clear-cache', clearCache);
router.post('/export/:type', exportData);

module.exports = router;
