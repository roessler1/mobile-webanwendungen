import {WebSocketServer} from 'ws';
import * as fs from 'fs';
import * as path from 'path';

const server = new WebSocketServer({port: 8080});

let sockets = [];

server.on('connection', ws => {
    sockets.push(ws);
    ws.binaryType = "arraybuffer";

    ws.onmessage = url => {
        fs.readFile(path.resolve('./../public'+url.data), function(err, nb) {
            ws.send(new Uint8Array(nb).buffer);
        });
    }

    ws.onclose = event => {
        sockets = sockets.filter(s => s !== ws);
    }
});