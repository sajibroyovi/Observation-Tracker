const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const express = require('express');
const app = express();
const port = 3000;

// Initialize WhatsApp Client
const client = new Client({
    authStrategy: new LocalAuth({
        dataPath: './.wwebjs_auth'
    }),
    puppeteer: {
        headless: true,
        args: [
            '--no-sandbox', 
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-accelerated-2d-canvas',
            '--no-first-run',
            '--no-zygote',
            '--disable-gpu'
        ],
        // Increase timeout for slow connections
        timeout: 60000 
    }
});

client.on('qr', (qr) => {
    console.log('SCAN THIS QR CODE WITH YOUR WHATSAPP:');
    qrcode.generate(qr, { small: true });
});

client.on('ready', () => {
    console.log('WhatsApp Bridge is READY!');
});

client.on('authenticated', () => {
    console.log('AUTHENTICATED');
});

client.on('auth_failure', msg => {
    console.error('AUTHENTICATION FAILURE', msg);
});

client.on('disconnected', (reason) => {
    console.log('WhatsApp was disconnected:', reason);
    client.initialize(); // Attempt to re-initialize
});

client.initialize();

// Express Middleware
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// API Endpoint to send message
app.post('/send', async (req, res) => {
    const { phone, message } = req.body;

    if (!phone || !message) {
        return res.status(400).json({ status: 'error', message: 'Missing phone or message' });
    }

    try {
        // Format phone number (ensure it ends with @c.us)
        let formattedPhone = phone.includes('@c.us') ? phone : `${phone}@c.us`;
        
        // Remove any non-numeric characters from the start of the ID
        formattedPhone = formattedPhone.replace('+', '');

        const response = await client.sendMessage(formattedPhone, message);
        res.json({ status: 'success', response });
    } catch (error) {
        console.error('Failed to send message:', error);
        res.status(500).json({ status: 'error', message: error.message });
    }
});

// Health check
app.get('/status', (req, res) => {
    res.json({ status: 'online' });
});

app.listen(port, () => {
    console.log(`WhatsApp Bridge API listening at http://localhost:${port}`);
});
