module.exports = {
  apps: [{
    name: 'event-manager-api',
    script: 'src/server.js',
    cwd: '/opt/event-manager/event-manager-api',
    instances: 'max',
    exec_mode: 'cluster',
    env: {
      NODE_ENV: 'production',
      PORT: 3000
    },
    env_development: {
      NODE_ENV: 'development',
      PORT: 3000
    },
    error_file: '/var/log/pm2/event-manager-api-error.log',
    out_file: '/var/log/pm2/event-manager-api-out.log',
    log_file: '/var/log/pm2/event-manager-api.log',
    time: true,
    max_memory_restart: '1G',
    node_args: '--max-old-space-size=1024',
    watch: false,
    ignore_watch: ['node_modules', 'logs'],
    restart_delay: 4000,
    max_restarts: 10,
    min_uptime: '10s'
  }]
}
