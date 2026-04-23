// PM2 Configuration for GaweGateway Node Service
// Local: pm2 start ecosystem.config.js
// Production: pm2 start ecosystem.config.js --env production

module.exports = {
    apps: [{
        name: "gawegateway-wa",
        script: "index.js",
        cwd: __dirname,

        // Auto-restart jika crash
        autorestart: true,
        watch: false,
        max_memory_restart: "512M",

        // Restart delay jika crash berulang
        restart_delay: 5000,
        max_restarts: 10,

        // 1. DEFAULT (Environment Lokal)
        env: {
            NODE_ENV: "development",
            PORT: 3001,
            LARAVEL_URL: "http://localhost:8000",
            CORS_ORIGIN: "*",
            MAX_RETRIES: 5,
            MAX_QR_RETRIES: 3,
            LOG_LEVEL: "info"
        },

        // 2. PRODUCTION (Environment aaPanel)
        // Jalankan: pm2 start ecosystem.config.js --env production
        env_production: {
            NODE_ENV: "production",
            PORT: 3001,
            LARAVEL_URL: "http://localhost", 
            CORS_ORIGIN: "*", // Ganti ke domain frontend Anda nantinya
            MAX_RETRIES: 5,
            MAX_QR_RETRIES: 3,
            LOG_LEVEL: "warn"
        },

        // Logging
        error_file: "./logs/error.log",
        out_file: "./logs/output.log",
        merge_logs: true,
        log_date_format: "YYYY-MM-DD HH:mm:ss",

        // Graceful shutdown
        kill_timeout: 10000,
        listen_timeout: 5000,
    }]
};
