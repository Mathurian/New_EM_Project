const { PrismaClient } = require('@prisma/client');
const { validationResult } = require('express-validator');
const logger = require('../utils/logger');

const prisma = new PrismaClient();

// Submit scores for a contestant in a category
const submitScores = async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { categoryId, contestantId } = req.params;
    const { scores, comment } = req.body;
    const userId = req.user.userId;

    // Verify user is a judge for this category
    const categoryJudge = await prisma.categoryJudge.findFirst({
      where: {
        categoryId,
        judge: {
          users: {
            some: { id: userId }
          }
        }
      },
      include: {
        judge: true,
        category: {
          include: {
            contest: {
              include: {
                event: {
                  select: {
                    name: true
                  }
                }
              }
            }
          }
        }
      }
    });

    if (!categoryJudge) {
      return res.status(403).json({ error: 'You are not authorized to score this category' });
    }

    // Check if contestant is in this category
    const categoryContestant = await prisma.categoryContestant.findUnique({
      where: {
        categoryId_contestantId: {
          categoryId,
          contestantId
        }
      }
    });

    if (!categoryContestant) {
      return res.status(404).json({ error: 'Contestant not found in this category' });
    }

    // Validate scores
    const criteria = await prisma.criterion.findMany({
      where: { categoryId }
    });

    if (Object.keys(scores).length !== criteria.length) {
      return res.status(400).json({ error: 'All criteria must be scored' });
    }

    // Validate each score
    for (const criterionId of Object.keys(scores)) {
      const criterion = criteria.find(c => c.id === criterionId);
      if (!criterion) {
        return res.status(400).json({ error: `Invalid criterion: ${criterionId}` });
      }

      const score = parseFloat(scores[criterionId]);
      if (isNaN(score) || score < 0 || score > criterion.maxScore) {
        return res.status(400).json({ 
          error: `Score for ${criterion.name} must be between 0 and ${criterion.maxScore}` 
        });
      }
    }

    // Start transaction
    await prisma.$transaction(async (tx) => {
      // Delete existing scores for this judge and contestant
      await tx.score.deleteMany({
        where: {
          categoryId,
          contestantId,
          judgeId: categoryJudge.judge.id
        }
      });

      // Insert new scores
      for (const criterionId of Object.keys(scores)) {
        await tx.score.create({
          data: {
            categoryId,
            contestantId,
            judgeId: categoryJudge.judge.id,
            criterionId,
            score: parseFloat(scores[criterionId])
          }
        });
      }

      // Update or create comment
      if (comment && comment.trim()) {
        await tx.judgeComment.upsert({
          where: {
            categoryId_contestantId_judgeId: {
              categoryId,
              contestantId,
              judgeId: categoryJudge.judge.id
            }
          },
          update: {
            comment: comment.trim()
          },
          create: {
            categoryId,
            contestantId,
            judgeId: categoryJudge.judge.id,
            comment: comment.trim()
          }
        });
      }
    });

    // Log activity
    await prisma.activityLog.create({
      data: {
        userId,
        userName: req.user.userName,
        userRole: req.user.role,
        action: 'submit_scores',
        resourceType: 'category',
        resourceId: categoryId,
        details: `Submitted scores for contestant ${contestantId} in category ${categoryJudge.category.name}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('Scores submitted', { 
      categoryId, 
      contestantId, 
      judgeId: categoryJudge.judge.id, 
      userId 
    });

    res.json({ message: 'Scores submitted successfully' });
  } catch (error) {
    logger.error('Submit scores error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Get scores for a contestant in a category
const getScores = async (req, res) => {
  try {
    const { categoryId, contestantId } = req.params;
    const userId = req.user.userId;

    // Get category with contest and event info
    const category = await prisma.category.findUnique({
      where: { id: categoryId },
      include: {
        contest: {
          include: {
            event: {
              select: {
                name: true
              }
            }
          }
        },
        criteria: true
      }
    });

    if (!category) {
      return res.status(404).json({ error: 'Category not found' });
    }

    // Get contestant info
    const contestant = await prisma.contestant.findUnique({
      where: { id: contestantId }
    });

    if (!contestant) {
      return res.status(404).json({ error: 'Contestant not found' });
    }

    // Get all scores for this contestant in this category
    const scores = await prisma.score.findMany({
      where: {
        categoryId,
        contestantId
      },
      include: {
        judge: {
          select: {
            id: true,
            name: true,
            isHeadJudge: true
          }
        },
        criterion: {
          select: {
            id: true,
            name: true,
            maxScore: true
          }
        }
      }
    });

    // Get comments
    const comments = await prisma.judgeComment.findMany({
      where: {
        categoryId,
        contestantId
      },
      include: {
        judge: {
          select: {
            id: true,
            name: true
          }
        }
      }
    });

    // Get certifications
    const certifications = await prisma.judgeCertification.findMany({
      where: { categoryId },
      include: {
        judge: {
          select: {
            id: true,
            name: true
          }
        }
      }
    });

    const tallyMasterCertification = await prisma.tallyMasterCertification.findFirst({
      where: { categoryId }
    });

    const auditorCertification = await prisma.auditorCertification.findFirst({
      where: { categoryId }
    });

    // Organize scores by judge
    const scoresByJudge = {};
    scores.forEach(score => {
      if (!scoresByJudge[score.judge.id]) {
        scoresByJudge[score.judge.id] = {
          judge: score.judge,
          scores: {},
          total: 0,
          certified: certifications.some(c => c.judgeId === score.judge.id)
        };
      }
      scoresByJudge[score.judge.id].scores[score.criterion.id] = {
        criterion: score.criterion,
        score: score.score
      };
      scoresByJudge[score.judge.id].total += score.score;
    });

    res.json({
      category: {
        id: category.id,
        name: category.name,
        description: category.description,
        scoreCap: category.scoreCap,
        contest: category.contest,
        criteria: category.criteria
      },
      contestant: {
        id: contestant.id,
        name: contestant.name,
        contestantNumber: contestant.contestantNumber,
        imagePath: contestant.imagePath
      },
      scoresByJudge,
      comments,
      certifications: {
        judges: certifications,
        tallyMaster: tallyMasterCertification,
        auditor: auditorCertification
      }
    });
  } catch (error) {
    logger.error('Get scores error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Certify scores (for judges)
const certifyScores = async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { categoryId } = req.params;
    const { signatureName } = req.body;
    const userId = req.user.userId;

    // Verify user is a judge for this category
    const categoryJudge = await prisma.categoryJudge.findFirst({
      where: {
        categoryId,
        judge: {
          users: {
            some: { id: userId }
          }
        }
      },
      include: {
        judge: true,
        category: {
          include: {
            contest: {
              include: {
                event: {
                  select: {
                    name: true
                  }
                }
              }
            }
          }
        }
      }
    });

    if (!categoryJudge) {
      return res.status(403).json({ error: 'You are not authorized to certify scores for this category' });
    }

    // Check if already certified
    const existingCertification = await prisma.judgeCertification.findFirst({
      where: {
        categoryId,
        judgeId: categoryJudge.judge.id
      }
    });

    if (existingCertification) {
      return res.status(400).json({ error: 'Scores already certified for this category' });
    }

    // Create certification
    const certification = await prisma.judgeCertification.create({
      data: {
        categoryId,
        judgeId: categoryJudge.judge.id,
        signatureName: signatureName.trim()
      }
    });

    // Log activity
    await prisma.activityLog.create({
      data: {
        userId,
        userName: req.user.userName,
        userRole: req.user.role,
        action: 'certify_scores',
        resourceType: 'category',
        resourceId: categoryId,
        details: `Certified scores for category ${categoryJudge.category.name}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('Scores certified', { 
      categoryId, 
      judgeId: categoryJudge.judge.id, 
      userId 
    });

    res.json({ 
      message: 'Scores certified successfully',
      certification 
    });
  } catch (error) {
    logger.error('Certify scores error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Certify totals (for tally masters)
const certifyTotals = async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { categoryId } = req.params;
    const { signatureName } = req.body;
    const userId = req.user.userId;

    // Verify user is a tally master
    const user = await prisma.user.findUnique({
      where: { id: userId }
    });

    if (user.role !== 'TALLY_MASTER') {
      return res.status(403).json({ error: 'Only tally masters can certify totals' });
    }

    // Check if category exists
    const category = await prisma.category.findUnique({
      where: { id: categoryId },
      include: {
        contest: {
          include: {
            event: {
              select: {
                name: true
              }
            }
          }
        }
      }
    });

    if (!category) {
      return res.status(404).json({ error: 'Category not found' });
    }

    // Check if all judges have certified
    const judges = await prisma.categoryJudge.findMany({
      where: { categoryId },
      include: {
        judge: true
      }
    });

    const certifications = await prisma.judgeCertification.findMany({
      where: { categoryId }
    });

    if (certifications.length !== judges.length) {
      return res.status(400).json({ 
        error: 'All judges must certify their scores before totals can be certified' 
      });
    }

    // Check if already certified
    const existingCertification = await prisma.tallyMasterCertification.findFirst({
      where: { categoryId }
    });

    if (existingCertification) {
      return res.status(400).json({ error: 'Totals already certified for this category' });
    }

    // Create certification
    const certification = await prisma.tallyMasterCertification.create({
      data: {
        categoryId,
        signatureName: signatureName.trim()
      }
    });

    // Log activity
    await prisma.activityLog.create({
      data: {
        userId,
        userName: req.user.userName,
        userRole: req.user.role,
        action: 'certify_totals',
        resourceType: 'category',
        resourceId: categoryId,
        details: `Certified totals for category ${category.name}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('Totals certified', { categoryId, userId });

    res.json({ 
      message: 'Totals certified successfully',
      certification 
    });
  } catch (error) {
    logger.error('Certify totals error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Perform final certification (for auditors)
const performFinalCertification = async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { categoryId } = req.params;
    const { signatureName } = req.body;
    const userId = req.user.userId;

    // Verify user is an auditor
    const user = await prisma.user.findUnique({
      where: { id: userId }
    });

    if (user.role !== 'AUDITOR') {
      return res.status(403).json({ error: 'Only auditors can perform final certification' });
    }

    // Check if category exists
    const category = await prisma.category.findUnique({
      where: { id: categoryId },
      include: {
        contest: {
          include: {
            event: {
              select: {
                name: true
              }
            }
          }
        }
      }
    });

    if (!category) {
      return res.status(404).json({ error: 'Category not found' });
    }

    // Check if tally master has certified
    const tallyMasterCertification = await prisma.tallyMasterCertification.findFirst({
      where: { categoryId }
    });

    if (!tallyMasterCertification) {
      return res.status(400).json({ 
        error: 'Tally master must certify totals before final certification' 
      });
    }

    // Check if already certified
    const existingCertification = await prisma.auditorCertification.findFirst({
      where: { categoryId }
    });

    if (existingCertification) {
      return res.status(400).json({ error: 'Final certification already completed for this category' });
    }

    // Create certification
    const certification = await prisma.auditorCertification.create({
      data: {
        categoryId,
        signatureName: signatureName.trim()
      }
    });

    // Log activity
    await prisma.activityLog.create({
      data: {
        userId,
        userName: req.user.userName,
        userRole: req.user.role,
        action: 'final_certification',
        resourceType: 'category',
        resourceId: categoryId,
        details: `Performed final certification for category ${category.name}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('Final certification completed', { categoryId, userId });

    res.json({ 
      message: 'Final certification completed successfully',
      certification 
    });
  } catch (error) {
    logger.error('Final certification error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Get certification status for a category
const getCertificationStatus = async (req, res) => {
  try {
    const { categoryId } = req.params;

    // Get category info
    const category = await prisma.category.findUnique({
      where: { id: categoryId },
      include: {
        contest: {
          include: {
            event: {
              select: {
                name: true
              }
            }
          }
        }
      }
    });

    if (!category) {
      return res.status(404).json({ error: 'Category not found' });
    }

    // Get judges
    const judges = await prisma.categoryJudge.findMany({
      where: { categoryId },
      include: {
        judge: {
          select: {
            id: true,
            name: true,
            isHeadJudge: true
          }
        }
      }
    });

    // Get certifications
    const judgeCertifications = await prisma.judgeCertification.findMany({
      where: { categoryId },
      include: {
        judge: {
          select: {
            id: true,
            name: true
          }
        }
      }
    });

    const tallyMasterCertification = await prisma.tallyMasterCertification.findFirst({
      where: { categoryId }
    });

    const auditorCertification = await prisma.auditorCertification.findFirst({
      where: { categoryId }
    });

    // Organize certification status
    const certificationStatus = {
      category: {
        id: category.id,
        name: category.name,
        contest: category.contest
      },
      judges: judges.map(judge => ({
        judge: judge.judge,
        certified: judgeCertifications.some(c => c.judgeId === judge.judge.id),
        certification: judgeCertifications.find(c => c.judgeId === judge.judge.id)
      })),
      tallyMaster: {
        certified: !!tallyMasterCertification,
        certification: tallyMasterCertification
      },
      auditor: {
        certified: !!auditorCertification,
        certification: auditorCertification
      },
      summary: {
        totalJudges: judges.length,
        certifiedJudges: judgeCertifications.length,
        tallyMasterCertified: !!tallyMasterCertification,
        auditorCertified: !!auditorCertification,
        allJudgesCertified: judges.length === judgeCertifications.length && judges.length > 0,
        readyForTallyMaster: judges.length === judgeCertifications.length && judges.length > 0,
        readyForAuditor: !!tallyMasterCertification
      }
    };

    res.json(certificationStatus);
  } catch (error) {
    logger.error('Get certification status error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

module.exports = {
  submitScores,
  getScores,
  certifyScores,
  certifyTotals,
  performFinalCertification,
  getCertificationStatus
};
