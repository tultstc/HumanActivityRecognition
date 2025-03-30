import express from 'express';
import { createServer } from 'http';
import WebSocket from 'ws';
import rtspRelay from 'rtsp-relay';
import fetch from 'node-fetch';

const app = express();
const server = createServer(app);
const wss = new WebSocket.Server({ server });

const { proxy } = rtspRelay(app);

async function getCameraUrlById(id) {
    try {
        const response = await fetch(`http://localhost:8000/cameras/${id}/rtsp-url`, {
            method: 'GET',
        });

        if (!response.ok) {
            throw new Error('Camera not found or inactive');
        }

        const data = await response.json();
        return data.rtspUrl;
    } catch (error) {
        console.error(`Error fetching camera URL: ${error.message}`);
        return null;
    }
}

wss.on('connection', async (ws, req) => {
    const cameraId = req.url.split('/').pop();
    const cameraUrl = await getCameraUrlById(cameraId);
    
    if (cameraUrl) {
        proxy({
            url: cameraUrl,
            verbose: true,
            transport: 'tcp',
            bufferSize: 512 * 1024,      // Increase buffer size 
            timeout: 30 * 1000           // Timeout in milliseconds
        })(ws);
    } else {
        ws.close();
    }
});

wss.on('error', (error) => {
    console.error('WebSocket server error:', error);
});

server.listen(3000, () => {
    console.log('RTSP Relay Server is running on port 3000');
});