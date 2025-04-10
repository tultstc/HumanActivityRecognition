import { WebSocketServer } from "ws";
import { spawn } from "child_process";
import { parse } from "url";

const PORT = 9999;

const wss = new WebSocketServer({ port: PORT });

console.log("WebSocket Relay Server running on port", PORT);

wss.on("connection", function (ws, req) {
    console.log("New WebSocket Connection");

    const parameters = parse(req.url, true);
    const rtspUrl = parameters.query.url;

    if (!rtspUrl) {
        ws.close();
        return;
    }

    console.log("RTSP URL:", rtspUrl);

    const ffmpeg = spawn("ffmpeg", [
        "-rtsp_transport",
        "tcp",
        "-probesize",
        "10000000",
        "-analyzeduration",
        "10000000",
        "-i",
        rtspUrl,
        "-f",
        "mpegts",
        "-codec:v",
        "mpeg1video",
        "-s",
        "800x600",
        "-b:v",
        "1000k",
        "-bf",
        "0",
        "-muxdelay",
        "0.001",
        "-r",
        "30",
        "-q:v",
        "5",
        "-preset",
        "ultrafast",
        "-tune",
        "zerolatency",
        "-flush_packets",
        "1",
        "pipe:1",
    ]);

    ffmpeg.on("error", (err) => {
        console.error("FFmpeg process error:", err);
        ws.close();
    });

    ffmpeg.stdout.on("data", function (data) {
        if (ws.readyState === ws.OPEN) {
            try {
                ws.send(data);
            } catch (error) {
                console.error("Error sending data:", error);
            }
        }
    });

    ffmpeg.stderr.on("data", function (data) {
        const stderr = data.toString();
        if (stderr.includes("Error") || stderr.includes("error")) {
            console.error("FFmpeg stderr:", stderr);
        }
    });

    ws.on("error", function (error) {
        console.error("WebSocket error:", error);
        ffmpeg.kill();
    });

    ws.on("close", function () {
        console.log("Client disconnected. Killing FFmpeg process.");
        ffmpeg.kill("SIGKILL");
    });
});

process.on("SIGINT", () => {
    console.log("Shutting down server...");
    wss.close(() => {
        process.exit(0);
    });
});
