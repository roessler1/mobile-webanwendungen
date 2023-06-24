import {WebSocketServer} from 'ws';
import * as fs from 'fs';
import * as path from 'path';

const server = new WebSocketServer({port: 8080});
const mp3_pattern = [0x49, 0x44, 0x33];
const flac_pattern = [0x66, 0x4C, 0x61, 0x43];

let sockets = [];
let files = [];
let codecs = [];

server.on('connection', ws => {
    sockets.push(ws);
    ws.binaryType = "arraybuffer";

    ws.onmessage = url => {
        let idx = sockets.indexOf(ws);
        if(url.data.valueOf() == 'nc'.valueOf()) {
            if(codecs[idx].valueOf() == 'flac') {
                sendFlacChunk(idx, ws);
            }
            if(codecs[idx].valueOf() == 'mp3') {
                sendMP3Chunk(idx, ws);
            }
        } else {
            fs.readFile(path.resolve('./../public' + url.data), function (err, data) {
                files[idx] = new Uint8Array(data);
                if (isFlac(files[idx].subarray(0, 4))) {
                    codecs[idx] = 'flac';
                    sendFlacChunk(idx, ws);
                } else if (isMP3(files[idx].subarray(0, 3))) {
                    codecs[idx] = 'mp3';
                    sendMP3Chunk(idx, ws);
                } else {
                    return;
                }
            });
        }
    }

    ws.onclose = event => {
        sockets = sockets.filter(s => s !== ws);
    }
});

function findSyncBytes(data, syncBytes) {
    const syncBytesLength = syncBytes.length;
    const dataLength = data.length;

    for(let i = 0; i < dataLength - syncBytesLength +1; i++) {
        let found = true;
        for(let j = 0; j < syncBytesLength; j++) {
            if(data[i+j] !== syncBytes[j]) {
                found = false;
                break;
            }
        }

        if(found) {
            return i;
        }
    }

    return -1;
}

function isMP3(head) {
    return mp3_pattern.every((value, index) => head[index] === value);
}

function calculateMP3FrameSize(idx) {
    const version = (files[idx][1] >> 3) & 0x03;
    const bitrateIndex = (files[idx][2] >> 4) & 0x0F;
    const samplingRateIndex = (files[idx][2] >> 2) & 0x03;
    const padding = (files[idx][2] >> 1) & 0x01;

    const bitrateTable = [
        [0, 32, 64, 96, 128, 160, 192, 224, 256, 288, 320, 352, 384, 416, 448],
        [0, 32, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320, 384],
        [0, 32, 40, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320]
    ];

    const samplingRateTable = [
        [44100, 48000, 32000],
        [22050, 24000, 16000],
        [11025, 12000, 8000]
    ];

    const bitrate = bitrateTable[version-1][bitrateIndex];
    const samplingRate = samplingRateTable[version-1][samplingRateIndex];
    let frameSize;
    if (version === 3) {
        frameSize = Math.floor((144 * bitrate) / (samplingRate/1000)) + padding;
    } else {
        frameSize = Math.floor((144 * bitrate) / samplingRate) + padding * 2;
    }
    return frameSize;
}

async function sendMP3Chunk(idx, ws) {
    if(files[idx].length === 0) {
        ws.send(new Uint8Array(0).buffer);
        return;
    }

    const syncBytes = [0xFF, 0xFB];
    let syncIndex = findSyncBytes(files[idx], syncBytes);

    if(syncIndex === -1) {
        return;
    }

    if(syncIndex !== 0) {
        ws.send(files[idx].subarray(0, 3));
        files[idx] = files[idx].subarray(syncIndex);
    }

    const frameSize = calculateMP3FrameSize(idx, syncBytes[1]) * 150;

    if(frameSize === -1) {
        return;
    }

    syncIndex = findSyncBytes(files[idx].subarray(frameSize), syncBytes);
    let chunk;
    if(syncIndex === -1) {
        chunk = files[idx].subarray(0);
        files[idx] = files[idx].subarray(files[idx].length);
    } else {
        chunk = files[idx].subarray(0, (syncIndex + frameSize));
        files[idx] = files[idx].subarray(syncIndex + frameSize);
    }
    ws.send(chunk);
}

function isFlac(head) {
    return flac_pattern.every((value, index) => head[index] === value);
}

function calculateFlacFrameSize(idx) {
    const sampleRate = (files[idx][2] & 0x0F);
    const sampleRateTable = [0, 88200, 176400, 192000, 8000, 16000, 22050, 24000, 32000, 44100, 48000, 96000];

    if (sampleRate < sampleRateTable.length) {
        return ((files[idx][1] & 0x01) << 16) | (files[idx][2] << 8) | files[idx][3];
    }

    return -1;
}

async function sendFlacChunk(idx, ws) {
    if(files[idx].length === 0) {
        ws.send(new Uint8Array(0).buffer);
        return;
    }

    let syncIndex = findSyncBytes(files[idx], [0xFF, 0xF8]);

    if(syncIndex === -1) {
        return;
    }

    if(syncIndex !== 0) {
        ws.send(files[idx].subarray(0, 4));
        files[idx] = files[idx].subarray(syncIndex);
    }

    const syncBytes = [0xFF, 0xF8, files[idx][2], files[idx][3]];
    const approximatedFrameSize = calculateFlacFrameSize(idx) * 150;

    if(approximatedFrameSize === -1) {
        return;
    }

    syncIndex = findFlacHeader(files[idx].subarray(approximatedFrameSize), syncBytes);
    let chunk;
    if (syncIndex === -1) {
        chunk = files[idx].subarray(0);
        files[idx] = files[idx].subarray(files[idx].length);
    } else {
        chunk = files[idx].subarray(0, syncIndex + approximatedFrameSize);
        files[idx] = files[idx].subarray(syncIndex + approximatedFrameSize);
    }
    ws.send(chunk);
}

function findFlacHeader(data, syncBytes) {
    const syncBytesLength = syncBytes.length;
    const dataLength = data.length;

    for(let i = 0; i < dataLength - syncBytesLength +1; i++) {
        let found = true;
        for(let j = 0; j < syncBytesLength; j++) {
            if(data[i+j] !== syncBytes[j]) {
                found = false;
                break;
            }
        }

        if(found) {
            for(let k = 5; k < 11; k++) {
                let crc = calculateCRC(data.subarray(i, i+k));
                if(crc === data[i+k]) {
                    return i;
                }
            }
        }
    }

    return -1;
}

function calculateCRC(header) {
    const polynomial = 0x107;
    let crc = 0;

    for(let i = 0; i < header.length; i++) {
        crc ^= header[i];

        for(let j = 0; j < 8; j++) {
            if((crc & 0x80) !== 0) {
                crc = (crc << 1) ^ polynomial;
            } else {
                crc <<= 1;
            }
        }
    }

    return crc & 0xFF;
}