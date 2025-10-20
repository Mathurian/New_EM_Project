import Joi from 'joi'

// Validation schemas
export const loginSchema = Joi.object({
  email: Joi.string().email().normalize().required(),
  password: Joi.string().min(6).required()
})

export const registerSchema = Joi.object({
  email: Joi.string().email().normalize().required(),
  password: Joi.string().min(6).required(),
  first_name: Joi.string().min(1).max(100).required(),
  last_name: Joi.string().min(1).max(100).required(),
  role: Joi.string().valid('organizer', 'judge', 'contestant', 'emcee', 'tally_master', 'auditor', 'board').required()
})

export const profileUpdateSchema = Joi.object({
  first_name: Joi.string().min(1).max(100).optional(),
  last_name: Joi.string().min(1).max(100).optional(),
  preferred_name: Joi.string().max(100).optional(),
  phone: Joi.string().max(20).optional(),
  bio: Joi.string().max(1000).optional(),
  pronouns: Joi.string().max(50).optional(),
  gender: Joi.string().valid('male', 'female', 'non-binary', 'prefer-not-to-say', 'other').optional()
})

export const passwordChangeSchema = Joi.object({
  current_password: Joi.string().required(),
  new_password: Joi.string().min(6).required()
})

// Validation middleware factory
export const validate = (schema) => {
  return (req, res, next) => {
    const { error, value } = schema.validate(req.body, { 
      abortEarly: false,
      stripUnknown: true 
    })
    
    if (error) {
      const details = error.details.map(detail => ({
        field: detail.path.join('.'),
        message: detail.message,
        value: detail.context.value
      }))
      
      return res.status(400).json({ 
        error: 'Validation failed', 
        details 
      })
    }
    
    // Replace req.body with validated and sanitized data
    req.body = value
    next()
  }
}

// Individual validation functions for backward compatibility
export const validateLogin = validate(loginSchema)
export const validateRegister = validate(registerSchema)
export const validateProfileUpdate = validate(profileUpdateSchema)
export const validatePasswordChange = validate(passwordChangeSchema)
