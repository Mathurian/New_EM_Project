import React from 'react'
import { useForm } from 'react-hook-form'
import { useAuth } from '../hooks/useAuth'
import { 
  UserIcon,
  EnvelopeIcon,
  EyeIcon,
  EyeSlashIcon,
  PencilIcon
} from '@heroicons/react/24/outline'
import LoadingSpinner from '../components/LoadingSpinner'
import toast from 'react-hot-toast'

interface ProfileFormData {
  name: string
  preferredName: string
  email: string
  gender: string
  pronouns: string
}

interface PasswordFormData {
  currentPassword: string
  newPassword: string
  confirmPassword: string
}

const Profile: React.FC = () => {
  const { user, updateProfile, changePassword } = useAuth()
  const [isEditing, setIsEditing] = React.useState(false)
  const [isChangingPassword, setIsChangingPassword] = React.useState(false)
  const [showCurrentPassword, setShowCurrentPassword] = React.useState(false)
  const [showNewPassword, setShowNewPassword] = React.useState(false)
  const [showConfirmPassword, setShowConfirmPassword] = React.useState(false)
  const [isLoading, setIsLoading] = React.useState(false)

  const {
    register: registerProfile,
    handleSubmit: handleSubmitProfile,
    formState: { errors: profileErrors },
    reset: resetProfile
  } = useForm<ProfileFormData>({
    defaultValues: {
      name: user?.name || '',
      preferredName: user?.preferredName || '',
      email: user?.email || '',
      gender: user?.gender || '',
      pronouns: user?.pronouns || ''
    }
  })

  const {
    register: registerPassword,
    handleSubmit: handleSubmitPassword,
    formState: { errors: passwordErrors },
    reset: resetPassword,
    watch
  } = useForm<PasswordFormData>()

  const newPassword = watch('newPassword')

  const onSubmitProfile = async (data: ProfileFormData) => {
    setIsLoading(true)
    try {
      await updateProfile(data)
      setIsEditing(false)
      resetProfile(data)
    } catch (error) {
      // Error handling is done in the auth context
    } finally {
      setIsLoading(false)
    }
  }

  const onSubmitPassword = async (data: PasswordFormData) => {
    if (data.newPassword !== data.confirmPassword) {
      toast.error('New passwords do not match')
      return
    }

    setIsLoading(true)
    try {
      await changePassword(data.currentPassword, data.newPassword)
      setIsChangingPassword(false)
      resetPassword()
      toast.success('Password changed successfully!')
    } catch (error) {
      // Error handling is done in the auth context
    } finally {
      setIsLoading(false)
    }
  }

  const handleCancelEdit = () => {
    setIsEditing(false)
    resetProfile({
      name: user?.name || '',
      preferredName: user?.preferredName || '',
      email: user?.email || '',
      gender: user?.gender || '',
      pronouns: user?.pronouns || ''
    })
  }

  const handleCancelPassword = () => {
    setIsChangingPassword(false)
    resetPassword()
  }

  if (!user) {
    return <LoadingSpinner size="lg" className="flex justify-center items-center h-64" />
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div className="flex items-center space-x-4">
          <div className="h-16 w-16 rounded-full bg-blue-500 flex items-center justify-center">
            <span className="text-2xl font-medium text-white">
              {user.preferredName?.charAt(0) || user.name?.charAt(0) || 'U'}
            </span>
          </div>
          <div>
            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
              {user.preferredName || user.name}
            </h1>
            <p className="text-gray-600 dark:text-gray-400">
              {user.email}
            </p>
            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 mt-2">
              {user.role.toLowerCase().replace('_', ' ')}
            </span>
          </div>
        </div>
      </div>

      {/* Profile Information */}
      <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div className="flex justify-between items-center">
            <h2 className="text-lg font-medium text-gray-900 dark:text-white">
              Profile Information
            </h2>
            {!isEditing && (
              <button
                onClick={() => setIsEditing(true)}
                className="btn btn-outline btn-sm"
              >
                <PencilIcon className="h-4 w-4 mr-2" />
                Edit Profile
              </button>
            )}
          </div>
        </div>

        <div className="p-6">
          {isEditing ? (
            <form onSubmit={handleSubmitProfile(onSubmitProfile)} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label className="label">
                    Full Name *
                  </label>
                  <input
                    {...registerProfile('name', { required: 'Full name is required' })}
                    type="text"
                    className="input"
                    placeholder="Enter your full name"
                  />
                  {profileErrors.name && (
                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                      {profileErrors.name.message}
                    </p>
                  )}
                </div>

                <div>
                  <label className="label">
                    Preferred Name
                  </label>
                  <input
                    {...registerProfile('preferredName')}
                    type="text"
                    className="input"
                    placeholder="Enter your preferred name"
                  />
                </div>

                <div>
                  <label className="label">
                    Email Address *
                  </label>
                  <input
                    {...registerProfile('email', {
                      required: 'Email is required',
                      pattern: {
                        value: /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i,
                        message: 'Invalid email address',
                      },
                    })}
                    type="email"
                    className="input"
                    placeholder="Enter your email address"
                  />
                  {profileErrors.email && (
                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                      {profileErrors.email.message}
                    </p>
                  )}
                </div>

                <div>
                  <label className="label">
                    Gender
                  </label>
                  <select
                    {...registerProfile('gender')}
                    className="input"
                  >
                    <option value="">Select gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="non-binary">Non-binary</option>
                    <option value="prefer-not-to-say">Prefer not to say</option>
                    <option value="other">Other</option>
                  </select>
                </div>

                <div className="md:col-span-2">
                  <label className="label">
                    Pronouns
                  </label>
                  <input
                    {...registerProfile('pronouns')}
                    type="text"
                    className="input"
                    placeholder="e.g., he/him, she/her, they/them"
                  />
                </div>
              </div>

              <div className="flex justify-end space-x-3">
                <button
                  type="button"
                  onClick={handleCancelEdit}
                  className="btn btn-outline btn-md"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={isLoading}
                  className="btn btn-primary btn-md"
                >
                  {isLoading ? <LoadingSpinner size="sm" /> : 'Save Changes'}
                </button>
              </div>
            </form>
          ) : (
            <div className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label className="label">Full Name</label>
                  <p className="text-gray-900 dark:text-white">{user.name}</p>
                </div>

                <div>
                  <label className="label">Preferred Name</label>
                  <p className="text-gray-900 dark:text-white">{user.preferredName || 'Not set'}</p>
                </div>

                <div>
                  <label className="label">Email Address</label>
                  <p className="text-gray-900 dark:text-white">{user.email}</p>
                </div>

                <div>
                  <label className="label">Gender</label>
                  <p className="text-gray-900 dark:text-white">{user.gender || 'Not specified'}</p>
                </div>

                <div>
                  <label className="label">Pronouns</label>
                  <p className="text-gray-900 dark:text-white">{user.pronouns || 'Not specified'}</p>
                </div>

                <div>
                  <label className="label">Role</label>
                  <p className="text-gray-900 dark:text-white capitalize">
                    {user.role.toLowerCase().replace('_', ' ')}
                  </p>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Change Password */}
      <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div className="flex justify-between items-center">
            <h2 className="text-lg font-medium text-gray-900 dark:text-white">
              Change Password
            </h2>
            {!isChangingPassword && (
              <button
                onClick={() => setIsChangingPassword(true)}
                className="btn btn-outline btn-sm"
              >
                Change Password
              </button>
            )}
          </div>
        </div>

        <div className="p-6">
          {isChangingPassword ? (
            <form onSubmit={handleSubmitPassword(onSubmitPassword)} className="space-y-6">
              <div className="space-y-4">
                <div>
                  <label className="label">
                    Current Password *
                  </label>
                  <div className="relative">
                    <input
                      {...registerPassword('currentPassword', { required: 'Current password is required' })}
                      type={showCurrentPassword ? 'text' : 'password'}
                      className="input pr-10"
                      placeholder="Enter your current password"
                    />
                    <button
                      type="button"
                      className="absolute inset-y-0 right-0 pr-3 flex items-center"
                      onClick={() => setShowCurrentPassword(!showCurrentPassword)}
                    >
                      {showCurrentPassword ? (
                        <EyeSlashIcon className="h-5 w-5 text-gray-400" />
                      ) : (
                        <EyeIcon className="h-5 w-5 text-gray-400" />
                      )}
                    </button>
                  </div>
                  {passwordErrors.currentPassword && (
                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                      {passwordErrors.currentPassword.message}
                    </p>
                  )}
                </div>

                <div>
                  <label className="label">
                    New Password *
                  </label>
                  <div className="relative">
                    <input
                      {...registerPassword('newPassword', {
                        required: 'New password is required',
                        minLength: {
                          value: 8,
                          message: 'Password must be at least 8 characters',
                        },
                      })}
                      type={showNewPassword ? 'text' : 'password'}
                      className="input pr-10"
                      placeholder="Enter your new password"
                    />
                    <button
                      type="button"
                      className="absolute inset-y-0 right-0 pr-3 flex items-center"
                      onClick={() => setShowNewPassword(!showNewPassword)}
                    >
                      {showNewPassword ? (
                        <EyeSlashIcon className="h-5 w-5 text-gray-400" />
                      ) : (
                        <EyeIcon className="h-5 w-5 text-gray-400" />
                      )}
                    </button>
                  </div>
                  {passwordErrors.newPassword && (
                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                      {passwordErrors.newPassword.message}
                    </p>
                  )}
                </div>

                <div>
                  <label className="label">
                    Confirm New Password *
                  </label>
                  <div className="relative">
                    <input
                      {...registerPassword('confirmPassword', {
                        required: 'Please confirm your new password',
                        validate: value => value === newPassword || 'Passwords do not match'
                      })}
                      type={showConfirmPassword ? 'text' : 'password'}
                      className="input pr-10"
                      placeholder="Confirm your new password"
                    />
                    <button
                      type="button"
                      className="absolute inset-y-0 right-0 pr-3 flex items-center"
                      onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                    >
                      {showConfirmPassword ? (
                        <EyeSlashIcon className="h-5 w-5 text-gray-400" />
                      ) : (
                        <EyeIcon className="h-5 w-5 text-gray-400" />
                      )}
                    </button>
                  </div>
                  {passwordErrors.confirmPassword && (
                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                      {passwordErrors.confirmPassword.message}
                    </p>
                  )}
                </div>
              </div>

              <div className="flex justify-end space-x-3">
                <button
                  type="button"
                  onClick={handleCancelPassword}
                  className="btn btn-outline btn-md"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={isLoading}
                  className="btn btn-primary btn-md"
                >
                  {isLoading ? <LoadingSpinner size="sm" /> : 'Change Password'}
                </button>
              </div>
            </form>
          ) : (
            <div className="text-center py-8">
              <UserIcon className="mx-auto h-12 w-12 text-gray-400" />
              <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                Password Security
              </h3>
              <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Click "Change Password" to update your password.
              </p>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

export default Profile
